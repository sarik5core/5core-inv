<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ShopifySku;

class ShopifyApiInventoryController extends Controller
{
    protected $shopifyApiKey;
    protected $shopifyPassword;
    protected $shopifyStoreUrl;


    protected $shopifyStoreUrlName = '5-core.myshopify.com';
    protected $shopifyAccessToken = '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';

    public function __construct()
    {
        $this->shopifyApiKey = config('services.shopify.api_key');
        $this->shopifyPassword = config('services.shopify.password');
        $this->shopifyStoreUrl = str_replace(
            ['https://', 'http://'],
            '',
            config('services.shopify.store_url')
        );
    }

    public function saveDailyInventory()
    {
        try {
            $startTime = microtime(true);
            Log::info('Starting Shopify inventory sync');

            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays(30)->startOfDay();

            // Get ALL SKUs (including paginated products)
            $inventoryData = $this->getAllInventoryData();
            Log::info('Fetched ' . count($inventoryData) . ' SKUs from products');

            // Fetch orders for the period
            $ordersData = $this->fetchAllPages($startDate, $endDate);
            Log::info('Fetched ' . count($ordersData['orders']) . ' order items');

            // Process and save data
            $simplifiedData = $this->processSimplifiedData($ordersData['orders'], $inventoryData);
            $this->saveSkus($simplifiedData);

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("Successfully synced " . count($simplifiedData) . " SKUs in {$duration}s");
            return true;

        } catch (\Exception $e) {
            Log::error('Shopify Inventory Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function getAllInventoryData(): array
    {
        $inventoryData = [];   
        $pageInfo = null;
        $hasMore = true;
        $pageCount = 0;

        while ($hasMore) {
            $pageCount++;
            $queryParams = ['limit' => 250, 'fields' => 'id,title,variants,image,images'];
            if ($pageInfo) {
                $queryParams['page_info'] = $pageInfo;
            }

            $response = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
                ->timeout(120)
                ->retry(3, 500)
                ->get("https://{$this->shopifyStoreUrl}/admin/api/2025-01/products.json", $queryParams);

            if (!$response->successful()) {
                Log::error("Failed to fetch products page {$pageCount}: " . $response->body());
                break;
            }

            $products = $response->json()['products'] ?? [];
            foreach ($products as $product) {
                foreach ($product['variants'] as $variant) {
                    if (!empty($variant['sku'])) {
                        $inventoryData[$variant['sku']] = [
                            'variant_id' => $variant['id'],
                            'inventory' => $variant['inventory_quantity'] ?? 0,
                            'product_title' => $product['title'] ?? '',
                            'sku' => $variant['sku'] ?? '',
                            'variant_title' => $variant['title'] ?? '',
                            'inventory_item_id' => $variant['inventory_item_id'],
                            'on_hand' => $variant['old_inventory_quantity'] ?? 0,          // old inventory qty = OnHand
                            'available_to_sell' => $variant['inventory_quantity'] ?? 0,    // inventory qty = AvailableToSell
                            'price' => $variant['price'],
                            'image_src' => $product['image']['src'] ?? (!empty($product['images']) ? $product['images'][0]['src'] : null),
                            // 'on_hand' => 0,
                            // 'committed' => 0,
                            // 'available_to_sell' => 0,

                        ];
                    } else {
                        Log::warning('Variant without SKU', [
                            'product_id' => $product['id'],
                            'variant_id' => $variant['id'], 
                            'on_hand' => $variant['old_inventory_quantity'] ?? 0,         
                            'available_to_sell' => $variant['inventory_quantity'] ?? 0,
                        ]);
                    }
                }
            }

            // Pagination handling
            $pageInfo = $this->getNextPageInfo($response);
            $hasMore = (bool) $pageInfo;
            
            // Avoid rate limiting
            if ($hasMore) {
                usleep(500000); // 0.5s delay between requests
            }
        }

        Log::info("Processed {$pageCount} product pages");
        return $inventoryData;
    }

    

    // public function fetchAllInventoryUsingRest()
    // {
    //     $shopUrl = 'https://5-core.myshopify.com';
    //     $token = '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';

    //     $allVariants = [];
    //     $pageInfo = null;

    //     do {
    //         $queryParams = [
    //             'limit' => 250,
    //             'fields' => 'id,title,variants',
    //         ];
    //         if ($pageInfo) {
    //             $queryParams['page_info'] = $pageInfo;
    //         }

    //         $response = Http::withHeaders([
    //             'X-Shopify-Access-Token' => $token,
    //         ])->get("$shopUrl/admin/api/2023-10/products.json", $queryParams);

    //         $products = $response->json('products') ?? [];

    //         foreach ($products as $product) {
    //             foreach ($product['variants'] as $variant) {
    //                 $allVariants[] = [
    //                     'sku' => trim($variant['sku']),
    //                     'inventory_item_id' => $variant['inventory_item_id'],
    //                     'inventory_quantity' => $variant['inventory_quantity'], //  on_hand
    //                     'product_title' => $product['title'],
    //                 ];
    //             }
    //         }

    //         // Pagination via Link header
    //         $linkHeader = $response->header('Link');
    //         $pageInfo = null;
    //         if ($linkHeader && preg_match('/<[^>]+page_info=([^&>]+)[^>]*>; rel="next"/', $linkHeader, $matches)) {
    //             $pageInfo = $matches[1];
    //         }
    //     } while ($pageInfo);

    //     // Collect inventory_item_ids
    //     $inventoryLevels = [];
    //     $chunks = array_chunk(array_column($allVariants, 'inventory_item_id'), 50);

    //     foreach ($chunks as $chunk) {
    //         $ids = implode(',', $chunk);

    //         $response = Http::withHeaders([
    //             'X-Shopify-Access-Token' => $token,
    //         ])->get("$shopUrl/admin/api/2023-10/inventory_levels.json", [
    //             'inventory_item_ids' => $ids,
    //         ]);

    //         $levels = $response->json('inventory_levels') ?? [];

    //         foreach ($levels as $level) {
    //             $inventoryLevels[$level['inventory_item_id']] = [
    //                 'location_id' => $level['location_id'],
    //                 'available' => $level['available'],
    //             ];
    //         }
    //     }

    //     // Merge inventory levels with variants
    //     $finalData = [];
    //     foreach ($allVariants as $variant) {
    //         $id = $variant['inventory_item_id'];
    //         $level = $inventoryLevels[$id] ?? ['available' => null, 'location_id' => null];

    //         $onHand = $variant['inventory_quantity'];
    //         $availableToSell = $level['available'];
    //         $availableToSell = is_numeric($availableToSell) ? (int)$availableToSell : null;
    //         $onHand = is_numeric($onHand) ? (int)$onHand : null;

    //         $committed = (!is_null($onHand) && !is_null($availableToSell))
    //             ? $onHand - $availableToSell
    //             : null;
    //         // $committed = (is_numeric($onHand) && is_numeric($availableToSell))
    //         //     ? $onHand - $availableToSell
    //         //     : null;

    //         $finalData[] = [
    //             'sku' => $variant['sku'],
    //             'product_title' => $variant['product_title'],
    //             'inventory_item_id' => $id,
    //             'on_hand' => $onHand,
    //             'available_to_sell' => $availableToSell,
    //             'location_id' => $level['location_id'],
    //             'committed' => $committed,
    //         ];
    //     }

    //     $finalData = array_filter($finalData, fn($item) => !empty($item['sku']) && $item['sku'] !== '');

    //     return response()->json([
    //         'data' => array_values($finalData),
    //     ]);
    // }




    // public function fetchInventoryWithCommitment(): array     // all location but for some values mismatched
    // {
    //     set_time_limit(60);
    //     $shopUrl = 'https://5-core.myshopify.com'; 
    //     $token = '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'; 

    //     // Step 1: Get all products and build sku -> inventory_item_id map
    //     $skuMap = [];
    //     $nextPageUrl = "$shopUrl/admin/api/2025-01/products.json?limit=250&fields=variants";

    //     do {
    //         $response = Http::withHeaders([
    //             'X-Shopify-Access-Token' => $token,
    //         ])->get($nextPageUrl);  

    //         if (!$response->successful()) {
    //             Log::error('Failed to fetch products');
    //             return [];
    //         }

    //         $products = $response->json('products');
    //         foreach ($products as $product) {
    //             foreach ($product['variants'] as $variant) {
    //                 $sku = strtoupper(trim($variant['sku']));
    //                 $iid = $variant['inventory_item_id'];
    //                 if (!empty($sku)) {
    //                     $skuMap[$sku] = $iid;
    //                 }
    //             }
    //         }

    //         $linkHeader = $response->header('Link');
    //         $nextPageUrl = null;
    //         if ($linkHeader && preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
    //             $nextPageUrl = $matches[1];
    //         }
    //     } while ($nextPageUrl);

    //     // Step 2: Batch inventory_item_ids into chunks of 50 and call /inventory_levels.json
    //     $availableByIid = [];
    //     $inventoryItemIds = array_values($skuMap);
    //     $chunks = array_chunk($inventoryItemIds, 50);

    //     foreach ($chunks as $chunk) {
    //         $response = Http::withHeaders([
    //             'X-Shopify-Access-Token' => $token,
    //         ])->get("$shopUrl/admin/api/2024-01/inventory_levels.json", [
    //             'inventory_item_ids' => implode(',', $chunk),
    //         ]);

    //         if (!$response->successful()) {
    //             Log::error('Failed to fetch inventory levels', [
    //                 'status' => $response->status(),
    //                 'body' => $response->body()
    //             ]);
    //             continue; // Continue to next chunk instead of breaking
    //         }

    //         $levels = $response->json('inventory_levels') ?? [];
    //         foreach ($levels as $level) {
    //             // $availableByIid[$level['inventory_item_id']] = $level['available'];
    //             $iid = $level['inventory_item_id'];
    //             $availableByIid[$iid] = ($availableByIid[$iid] ?? 0) + $level['available'];
    //         }
    //     }

    //     // Step 3: Get committed from unfulfilled orders
    //     $committedBySku = [];

    //     $response = Http::withHeaders([
    //         'X-Shopify-Access-Token' => $token,
    //     ])->get("$shopUrl/admin/api/2024-01/orders.json", [
    //         'status' => 'open',
    //         'fulfillment_status' => 'unfulfilled',
    //         'limit' => 250,
    //     ]);

    //     if (!$response->successful()) {
    //         Log::error('Failed to fetch orders');
    //         return [];
    //     }

    //     foreach ($response->json('orders') ?? [] as $order) {
    //         foreach ($order['line_items'] as $item) {
    //             $sku = strtoupper(trim($item['sku']));
    //             $qty = (int) $item['quantity'];
    //             $committedBySku[$sku] = ($committedBySku[$sku] ?? 0) + $qty;
    //         }
    //     }

    //     // Step 4: Merge data by SKU
    //     $final = [];

    //     foreach ($skuMap as $sku => $iid) {
    //         $available = $availableByIid[$iid] ?? 0;
    //         $committed = $committedBySku[$sku] ?? 0;
    //         $onHand = $available + $committed;

    //         $final[$sku] = [
    //             'available_to_sell' => $available,
    //             'committed' => $committed,
    //             'on_hand' => $onHand,
    //         ];
    //     }

    //     Log::info('Final Shopify Inventory:', $final);

    //     return $final;
    // }

    // public function fetchInventoryWithCommitment(): array      //250 correct data with ohio location
    // {
    //     set_time_limit(60);
    //     $shopUrl = 'https://5-core.myshopify.com'; 
    //     $token = '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'; 

    //     $locationId = null;
    //     $locationResponse = Http::withHeaders([
    //         'X-Shopify-Access-Token' => $token,
    //     ])->get("$shopUrl/admin/api/2025-01/locations.json");

    //     if ($locationResponse->successful()) {
    //         foreach ($locationResponse->json('locations') as $loc) {
    //             if (stripos($loc['name'], 'Ohio') !== false) {
    //                 $locationId = $loc['id'];

    //                 Log::info('Matched Shopify location:', [
    //                     'name' => $loc['name'],
    //                     'id' => $locationId 
    //                 ]);
    //                 break;
    //             }
    //         }
    //     }

    //     if (!$locationId) {
    //         Log::error('Ohio location not found.');
    //         return [];
    //     }

    //     // ✅ STEP 1: Get ONLY first page (limit 250)
    //     $skuMap = [];
    //     $response = Http::withHeaders([
    //         'X-Shopify-Access-Token' => $token,
    //     ])->get("$shopUrl/admin/api/2025-01/products.json", [
    //         'limit' => 250,
    //         'fields' => 'variants',
    //     ]);

    //     if (!$response->successful()) {
    //         Log::error('Failed to fetch first 250 products');
    //         return [];
    //     }

    //     $products = $response->json('products');
    //     foreach ($products as $product) {
    //         foreach ($product['variants'] as $variant) {
    //             $sku = trim($variant['sku']);
    //             $iid = $variant['inventory_item_id'];
    //             if (!empty($sku)) {
    //                 $skuMap[$sku] = $iid;
    //             }
    //         }
    //     }

    //     // ✅ STEP 2: Fetch inventory levels
    //     $availableByIid = [];
    //     $chunks = array_chunk(array_values($skuMap), 50);

    //     foreach ($chunks as $chunk) {
    //         $invResponse = Http::withHeaders([
    //             'X-Shopify-Access-Token' => $token,
    //         ])->get("$shopUrl/admin/api/2024-01/inventory_levels.json", [
    //             'inventory_item_ids' => implode(',', $chunk),
    //             'location_ids' => $locationId,
    //         ]);

    //         if (!$invResponse->successful()) {
    //             Log::error('Failed inventory_levels fetch', ['body' => $invResponse->body()]);
    //             continue;
    //         }

    //         foreach ($invResponse->json('inventory_levels') ?? [] as $level) {
    //             $iid = $level['inventory_item_id'];
    //             $availableByIid[$iid] = ($availableByIid[$iid] ?? 0) + $level['available'];
    //         }
    //     }

    //     // ✅ STEP 3: Get committed from orders
    //     $committedBySku = [];
    //     $orderResponse = Http::withHeaders([
    //         'X-Shopify-Access-Token' => $token,
    //     ])->get("$shopUrl/admin/api/2024-01/orders.json", [
    //         'status' => 'open',
    //         'fulfillment_status' => 'unfulfilled',
    //         'limit' => 250,
    //     ]);

    //     if ($orderResponse->successful()) {
    //         foreach ($orderResponse->json('orders') ?? [] as $order) {
    //             foreach ($order['line_items'] as $item) {
    //                 $sku = trim($item['sku']);
    //                 $qty = (int) $item['quantity'];
    //                 if (!empty($sku)) {
    //                     $committedBySku[$sku] = ($committedBySku[$sku] ?? 0) + $qty;
    //                 }
    //             }
    //         }
    //     } else {
    //         Log::error('Order fetch failed');
    //     }

    //     // ✅ STEP 4: Merge final inventory
    //     $final = [];
    //     foreach ($skuMap as $sku => $iid) {
    //         // $normalizedSku = strtoupper(trim($sku));
    //         $available = $availableByIid[$iid] ?? 0;
    //         $committed = $committedBySku[$sku] ?? 0;
    //         $onHand = $available + $committed;

    //         $final[$sku] = [
    //             'available_to_sell' => $available,
    //             'committed' => $committed,
    //             'on_hand' => $onHand,
    //         ];
    //     }

    //     Log::info('Final Inventory for 250 SKUs:', $final);
    //     return $final;
    // }


    public function fetchInventoryWithCommitment(): array
    {
        set_time_limit(60);
        $shopUrl = 'https://5-core.myshopify.com'; 
        // $token = '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'; 
        $token = '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'; 

        // Step 1: Get Ohio Location ID
        $locationId = null;
        $locationResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("$shopUrl/admin/api/2025-01/locations.json");

        if ($locationResponse->successful()) {
            foreach ($locationResponse->json('locations') as $loc) {
                if (stripos($loc['name'], 'Ohio') !== false) {
                    $locationId = $loc['id'];
                    Log::info('Matched Ohio location ID', ['id' => $locationId]);
                    break;
                }
            }
        }

        if (!$locationId) {
            Log::error('Ohio location not found.');
            return [];
        }

        // Step 2: Fetch ALL Products (with pagination)
        $skuMap = [];
        $imageMap = [];
        $nextPageUrl = "$shopUrl/admin/api/2025-01/products.json?limit=250&fields=variants,image";

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get($nextPageUrl);

            if (!$response->successful()) {
                Log::error('Failed to fetch products', ['url' => $nextPageUrl]);
                break;
            }

            $products = $response->json('products');
            foreach ($products as $product) {

                $mainImage = $product['image']['src'] ?? null; 

                foreach ($product['variants'] as $variant) {
                    $sku = trim($variant['sku']);
                    $iid = $variant['inventory_item_id'];

                    if (!empty($sku)) {
                        $skuMap[$sku] = $iid;
                        $imageMap[$sku] = $mainImage;

                    }
                }
            }

            $linkHeader = $response->header('Link');
            $nextPageUrl = null;
            if ($linkHeader && preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
                $nextPageUrl = $matches[1];
            }
        } while ($nextPageUrl);

        // Step 3: Fetch Inventory Levels (only from Ohio)
        $availableByIid = [];
        $chunks = array_chunk(array_values($skuMap), 50);

        foreach ($chunks as $chunk) {
            $invResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get("$shopUrl/admin/api/2024-01/inventory_levels.json", [
                'inventory_item_ids' => implode(',', $chunk),
                'location_ids' => $locationId,
            ]);

            if (!$invResponse->successful()) {
                Log::error('Failed to fetch inventory levels', ['body' => $invResponse->body()]);
                continue;
            }

            foreach ($invResponse->json('inventory_levels') ?? [] as $level) {
                $iid = $level['inventory_item_id'];
                $availableByIid[$iid] = ($availableByIid[$iid] ?? 0) + $level['available'];
            }
        }

        // Step 4: Fetch Committed Quantities from Orders
        $committedBySku = [];
        $orderResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("$shopUrl/admin/api/2024-01/orders.json", [
            'status' => 'open',
            'fulfillment_status' => 'unfulfilled',
            'limit' => 250,
        ]);

        if ($orderResponse->successful()) {
            foreach ($orderResponse->json('orders') ?? [] as $order) {
                foreach ($order['line_items'] as $item) {
                    $sku = trim($item['sku']);
                    $qty = (int) $item['quantity'];
                    if (!empty($sku)) {
                        $committedBySku[$sku] = ($committedBySku[$sku] ?? 0) + $qty;
                    }
                }
            }
        } else {
            Log::error('Failed to fetch orders');
        }

        // Step 5: Merge Final Inventory
        $final = [];
        foreach ($skuMap as $sku => $iid) {
            $available = $availableByIid[$iid] ?? 0;
            $committed = $committedBySku[$sku] ?? 0;
            $onHand = $available + $committed;

            $final[$sku] = [
                'available_to_sell' => $available,
                'committed' => $committed,
                'on_hand' => $onHand,
                'image_url' => $imageMap[$sku] ?? null, 
            ];
        }

        Log::info('Final inventory data (Ohio only):', $final);
        return $final;
    }



    public function getInventoryArray(): array
    {
        return $this->getAccurateInventoryCountsFromShopify();
    }


    protected function fetchAllPages(Carbon $startDate, Carbon $endDate, ?string $sku = null): array
    {
        $allOrders = [];
        $pageInfo = null;
        $hasMore = true;
        $attempts = 0;
        $pageCount = 0;

        while ($hasMore && $attempts < 3) {
            $pageCount++;
            $response = $this->makeApiRequest($startDate, $endDate, $sku, $pageInfo);

            if ($response->successful()) {
                $orders = $response->json()['orders'] ?? [];
                $filteredOrders = $this->filterOrders($orders, $sku);
                $allOrders = array_merge($allOrders, $filteredOrders);

                $pageInfo = $this->getNextPageInfo($response);
                $hasMore = (bool) $pageInfo;
                $attempts = 0;
                
                if ($hasMore) {
                    usleep(500000); // 0.5s delay
                }
            } else {
                $attempts++;
                Log::warning("Order fetch attempt {$attempts} failed: " . $response->body());
                sleep(1);
            }
        }

        Log::info("Fetched orders from {$pageCount} pages");
        return [
            'orders' => $allOrders,
            'totalResults' => count($allOrders)
        ];
    }

    protected function makeApiRequest(Carbon $startDate, Carbon $endDate, ?string $sku = null, ?string $pageInfo = null)
    {
        $queryParams = [
            'limit' => 250,
            'fields' => 'id,line_items,created_at'
        ];

        if ($pageInfo) {
            $queryParams['page_info'] = $pageInfo;
        } else {
            $queryParams = array_merge($queryParams, [
                'created_at_min' => $startDate->format('Y-m-d\TH:i:sP'),
                'created_at_max' => $endDate->format('Y-m-d\TH:i:sP'),
                'status' => 'any'
            ]);

            if ($sku) {
                $queryParams['line_items_sku'] = $sku;
            }
        }

        return Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
            ->timeout(120)
            ->retry(3, 500)
            ->get("https://{$this->shopifyStoreUrl}/admin/api/2025-01/orders.json", $queryParams);
    }

    protected function filterOrders(array $orders, ?string $sku): array
    {
        $filtered = [];

        foreach ($orders as $order) {
            foreach ($order['line_items'] ?? [] as $lineItem) {
                if (!empty($lineItem['sku']) && (!$sku || $lineItem['sku'] === $sku)) {
                    $filtered[] = [
                        'quantity' => $lineItem['quantity'],
                        'sku' => $lineItem['sku'],
                        'order_id' => $order['id'],
                        'created_at' => $order['created_at']
                    ];
                }
            }
        }

        return $filtered;
    }

    protected function getNextPageInfo($response): ?string
    {
        if ($response->hasHeader('Link') && str_contains($response->header('Link'), 'rel="next"')) {
            $links = explode(',', $response->header('Link'));
            foreach ($links as $link) {
                if (str_contains($link, 'rel="next"')) {
                    preg_match('/<(.*)>; rel="next"/', $link, $matches);
                    parse_str(parse_url($matches[1], PHP_URL_QUERY), $query);
                    return $query['page_info'] ?? null;
                }
            }
        }
        return null;
    }

    protected function processSimplifiedData(array $orders, array $inventoryData): array
    {
        $groupedData = [];

        // Initialize all SKUs with inventory data
        foreach ($inventoryData as $sku => $data) {
            $groupedData[$sku] = [
                'variant_id' => $data['variant_id'],
                'sku' => $sku,
                'quantity' => 0,
                'inventory' => $data['inventory'],
                'price' => $data['price'],
                'image_src' => $data['image_src'],
                'product_title' => $data['product_title'] ?? null,
                'variant_title' => $data['variant_title'] ?? null
            ];
        }

        // Update quantities for SKUs that had sales
        foreach ($orders as $order) {
            $sku = $order['sku'];
            if (isset($groupedData[$sku])) {
                $groupedData[$sku]['quantity'] += $order['quantity'];
            } else {
                Log::warning('Order SKU not found in products', ['sku' => $sku]);
            }
        }

        ksort($groupedData);
        return array_values($groupedData);
    }

    protected function saveSkus(array $simplifiedData)
    {
        DB::transaction(function () use ($simplifiedData) {
            // Reset quantities only
            ShopifySku::query()->update([
                'inv' => 0,
                'quantity' => 0,
                'price' => null,
            ]);

            // Batch processing for better performance
            foreach (array_chunk($simplifiedData, 1000) as $chunk) {
                foreach ($chunk as $item) {
                    ShopifySku::updateOrCreate(
                        ['sku' => $item['sku']],
                        [
                            'quantity' => $item['quantity'],
                            'variant_id' => $item['variant_id'],
                            'inv' => $item['inventory'],
                            'price' => $item['price'],
                            'image_src' => $item['image_src'],
                            'updated_at' => now()
                        ]
                    );
                }
            }
        });

        Cache::forget('shopify_skus_list');
    }

    protected function getInventoryLevels(array $inventoryItemIds): array
    {
        $inventoryLevels = [];
        $chunks = array_chunk($inventoryItemIds, 50); 

        foreach ($chunks as $chunk) {
            $query = http_build_query(['inventory_item_ids' => implode(',', $chunk)]);

            $response = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
                ->get("https://{$this->shopifyStoreUrl}/admin/api/2025-01/inventory_levels.json?$query");

            if ($response->successful()) {
                foreach ($response->json()['inventory_levels'] as $level) {
                    $inventoryLevels[$level['inventory_item_id']] = [
                        'available' => $level['available'],
                        'location_id' => $level['location_id'],
                    ];
                }
            }
        }

        return $inventoryLevels;
    }

    protected function getCommittedQuantities(): array
    {
        $committed = [];
        $pageInfo = null;
        $hasMore = true;

        while ($hasMore) {
            $queryParams = ['status' => 'open', 'limit' => 250, 'fields' => 'line_items'];
            if ($pageInfo) {
                $queryParams['page_info'] = $pageInfo;
            }

            $response = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
                ->get("https://{$this->shopifyStoreUrl}/admin/api/2025-01/orders.json", $queryParams);

            if (!$response->successful()) {
                break;
            }

            foreach ($response->json()['orders'] as $order) {
                foreach ($order['line_items'] as $item) {
                    $variantId = $item['variant_id'];
                    $committed[$variantId] = ($committed[$variantId] ?? 0) + $item['quantity'];
                }
            }

            $pageInfo = $this->getNextPageInfo($response);
            $hasMore = (bool) $pageInfo;
        }

        return $committed;
    }


}