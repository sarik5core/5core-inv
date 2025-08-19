<?php

namespace App\Http\Controllers\InventoryManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ShopifyApiInventoryController;
use App\Models\ShopifySku;
use App\Models\ProductMaster;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\ShopifyInventory;
use Illuminate\Support\Facades\DB;


class VerificationAdjustmentController extends Controller
{

    protected $shopifyDomain = '5-core.myshopify.com';
    protected $shopifyApiKey = '01f70fee8001931b5a25e3df24d6d749';
    protected $shopifyPassword = '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';

     protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory-management.verification-adjustment');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // public function getViewVerificationAdjustmentData(Request $request)
    // {

    //     $shopifyInventoryController = new ShopifyApiInventoryController();
    //     $inventoryResponse = $shopifyInventoryController->fetchInventoryWithCommitment();
    //     Log::info('Fetched inventory response:', $inventoryResponse);
    //     // $inventoryArray = json_decode($inventoryResponse->getContent(), true)['data'] ?? [];

    //     // $inventoryData = collect($inventoryResponse)->mapWithKeys(function ($item) {
    //     //     return [strtoupper(trim($item['sku'])) => $item];
    //     // });
    //     $inventoryData = collect($inventoryResponse);
    //     // $inventoryData = json_decode($inventoryResponse->getContent(), true)['data'];
    //     // $inventoryData = $shopifyInventoryController->getInventoryArray();
    //     // $inventoryData = []; 
    //     Log::info('Shopify Inventory Data:', $inventoryData->toArray());


    //     // Fetch data from the Google Sheet using the ApiController method
    //     $response = $this->apiController->fetchDataFromProductMasterGoogleSheet();
        
    //     // Check if the response is successful
    //     if ($response->getStatusCode() === 200) { 
    //         $data = $response->getData(); // Get the JSON data from the response

    //         // Get all non-PARENT SKUs from the data to fetch from ShopifySku model
    //         // $skus = collect($data->data)
    //         //     ->filter(function ($item) {
    //         //         $childSku = $item->{'SKU'} ?? '';
    //         //         return !empty($childSku) && stripos($childSku, 'PARENT') === false;
    //         //     })
    //         //     ->map(function ($item) {
    //         //     return strtoupper(trim($item->{'SKU'})); //  Normalize SKUs
    //         //     })
    //         //     ->unique()->toArray();
    //         $skus = collect($data->data)
    //             ->map(function ($item) {
    //                 return strtoupper(trim($item->{'SKU'} ?? ''));
    //             })
    //             ->filter()
    //             ->unique()
    //             ->toArray();

    //         // Fetch Shopify inventory data for non-PARENT SKUs
    //         $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy(function ($item) {
    //             return strtoupper(trim($item->sku)); //  Normalize DB SKUs
    //         });

    //         // Fetch saved Verified Stock data from DB
    //         //  $verifiedStockData = Inventory::get()->keyBy(function ($item) {
    //         //     return strtoupper(trim($item->sku)); // Normalize DB SKUs
    //         // });
    //         $verifiedStockData = Inventory::whereIn('sku', $skus)
    //         ->get()
    //         ->mapWithKeys(function ($item) {
    //             return [strtoupper(trim($item->sku)) => $item];
    //         });

    //         // Filter out rows where both Parent and (Child) sku are empty and process data
    //         $filteredData = array_filter($data->data, function ($item) {
    //             $parent = $item->Parent ?? '';
    //             $childSku = $item->{'SKU'} ?? '';

    //             // Keep the row if either Parent or (Child) sku is not empty
    //             return !(empty(trim($parent)) && empty(trim($childSku)));
    //         });

    //         // Process the data to include Shopify inventory values
    //         $mergedData = collect($filteredData)->map(function ($item) use ($shopifyData, $inventoryData, $verifiedStockData) {
    //             $childSku = $item->{'SKU'} ?? '';
    //             $normalizedSku = strtoupper(trim($childSku));

    //             $lp = isset($item->LP) && is_numeric($item->LP) ? floatval($item->LP) : 0;

    //             // Only update INV and L30 if this is not a PARENT SKU
    //             if (!empty($childSku) && stripos($childSku, 'PARENT') === false) {
    //                 if ($shopifyData->has($normalizedSku)) {
    //                     $item->INV = $shopifyData[$normalizedSku]->inv;
    //                     $item->L30 = $shopifyData[$normalizedSku]->quantity;
    //                 } else {
    //                     // Default to 0 if SKU not found in Shopify
    //                     $item->INV = 0;
    //                     $item->L30 = 0; 
    //                 }

    //                 if (isset($inventoryData[$normalizedSku])) {
    //                     $item->ON_HAND = $inventoryData[$normalizedSku]['on_hand'];
    //                     $item->COMMITTED = $inventoryData[$normalizedSku]['committed'];
    //                     $item->AVAILABLE_TO_SELL = $inventoryData[$normalizedSku]['available_to_sell'];

    //                     ShopifyInventory::updateOrCreate(
    //                         ['sku' => $normalizedSku],
    //                         [
    //                             'parent' => $item->Parent ?? null,
    //                             'on_hand' => $inventoryData[$normalizedSku]['on_hand'],
    //                             'committed' => $inventoryData[$normalizedSku]['committed'],
    //                             'available_to_sell' => $inventoryData[$normalizedSku]['available_to_sell'],
    //                         ]
    //                     );
                       
    //                 } else {
    //                     $item->ON_HAND = 'N/A';
    //                     $item->AVAILABLE_TO_SELL = 'N/A';
    //                     $item->COMMITTED  = 'N/A';
    //                 }

    //                 if ($verifiedStockData->has($normalizedSku)) {
    //                     $verifiedStockRow = $verifiedStockData[$normalizedSku];
    //                     $item->VERIFIED_STOCK = $verifiedStockRow->verified_stock ?? null;
    //                     $item->TO_ADJUST = $verifiedStockRow->to_adjust ?? null;
    //                     $item->REASON = $verifiedStockRow->reason ?? null;
    //                     $item->APPROVED = (bool) $verifiedStockRow->approved;
    //                     $item->APPROVED_BY = $verifiedStockRow->approved_by ?? null;

    //                 } else {
    //                     $item->VERIFIED_STOCK = null;
    //                     $item->TO_ADJUST = null;
    //                     $item->REASON = null;
    //                     $item->APPROVED = false;
    //                     $item->APPROVED_BY = null;
    //                 }

    //                 $adjustedQty = isset($item->TO_ADJUST) && is_numeric($item->TO_ADJUST) ? floatval($item->TO_ADJUST) : 0;
    //                 $item->LOSS_GAIN = round($adjustedQty * $lp, 2);
    //             }

    //             // For PARENT SKUs or when childSku is empty, keep original values

    //             return $item;
    //         });

    //         // Re-index the array after filtering
    //         // $processedData = array_values($processedData);
    //         $processedData = $mergedData->values();
    //         Log::info('Processed data count: ' . count($processedData));

    //         // Return the filtered data
    //         return response()->json([
    //             'message' => 'Data fetched successfully',
    //             'data' => $processedData,
    //             'status' => 200
    //         ]);
    //     } else {
    //         // Handle the error if the request failed
    //         return response()->json([
    //             'message' => 'Failed to fetch data from Google Sheet',
    //             'status' => $response->getStatusCode()
    //         ], $response->getStatusCode());
    //     }
    // }

    public function getViewVerificationAdjustmentData(Request $request)
    {
        $shopifyInventoryController = new ShopifyApiInventoryController();
        $inventoryResponse = $shopifyInventoryController->fetchInventoryWithCommitment();
        Log::info('Fetched inventory response:', $inventoryResponse);

        $inventoryData = collect($inventoryResponse); 
        Log::info('Shopify Inventory Data:', $inventoryData->toArray());

        $response = $this->apiController->fetchDataFromProductMasterGoogleSheet();

        if ($response->getStatusCode() === 200) {
            $data = $response->getData();

            // use trimmed SKUs only (no strtoupper)
            $skus = collect($data->data)
                ->map(function ($item) {
                    return trim($item->{'SKU'} ?? '');
                })
                ->filter()
                ->unique()
                ->toArray();

            // use exact-case SKU keys
            $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy(function ($item) {
                return trim($item->sku); 
            });

            $latestInventoryIds = Inventory::select(DB::raw('MAX(id) as latest_id'))
                ->whereIn('sku', $skus)
                ->groupBy('sku')
                ->pluck('latest_id');

            $latestInventoryData = Inventory::whereIn('id', $latestInventoryIds)->get();


            $verifiedStockData = $latestInventoryData
                ->filter(fn ($inv) => $inv->is_hide == 0)
                ->mapWithKeys(fn ($inv) => [trim($inv->sku) => $inv]);

            $hiddenSkuSet = $latestInventoryData
                ->filter(fn ($inv) => $inv->is_hide == 1)
                ->pluck('sku')
                ->map(fn ($sku) => trim($sku))
                ->toArray();

            // $verifiedStockData = Inventory::whereIn('sku', $skus)->get()
            //     ->mapWithKeys(function ($item) {
            //         return [trim($item->sku) => $item]; 
            //     });


            $filteredData = array_filter($data->data, function ($item) use ($hiddenSkuSet) {
                $sku = trim($item->SKU ?? '');
                return !(empty(trim($item->Parent ?? '')) && empty($sku)) && !in_array($sku, $hiddenSkuSet);
            });

            // $filteredData = array_filter($data->data, function ($item) {
            //     return !(empty(trim($item->Parent ?? '')) && empty(trim($item->{'SKU'} ?? '')));
            // });

            $mergedData = collect($filteredData)->map(function ($item) use ($shopifyData, $inventoryData, $verifiedStockData) {
                $childSku = trim($item->{'SKU'} ?? ''); 
                $lp = isset($item->LP) && is_numeric($item->LP) ? floatval($item->LP) : 0;

                if (!empty($childSku) && stripos($childSku, 'PARENT') === false) {
                    if ($shopifyData->has($childSku)) {
                        $item->INV = $shopifyData[$childSku]->inv;
                        $item->L30 = $shopifyData[$childSku]->quantity;
                        $item->IMAGE_URL = $shopifyData[$childSku]->image_url ?? null;
                    } else {
                        $item->INV = 0;
                        $item->L30 = 0;
                        $item->IMAGE_URL = null;
                    }

                    if (isset($inventoryData[$childSku])) {
                        $item->ON_HAND = $inventoryData[$childSku]['on_hand'];
                        $item->COMMITTED = $inventoryData[$childSku]['committed'];
                        $item->AVAILABLE_TO_SELL = $inventoryData[$childSku]['available_to_sell'];
                        $item->IMAGE_URL = $inventoryData[$childSku]['image_url'] ?? $item->IMAGE_URL;

                        ShopifyInventory::updateOrCreate(
                            ['sku' => $childSku],
                            [
                                'parent' => $item->Parent ?? null,
                                'on_hand' => $inventoryData[$childSku]['on_hand'],
                                'committed' => $inventoryData[$childSku]['committed'],
                                'available_to_sell' => $inventoryData[$childSku]['available_to_sell'],
                            ]
                        );
                    } else {
                        $item->ON_HAND = 'N/A';
                        $item->AVAILABLE_TO_SELL = 'N/A';
                        $item->COMMITTED = 'N/A';
                    }

                    if ($verifiedStockData->has($childSku)) {
                        $verifiedStockRow = $verifiedStockData[$childSku];
                        $item->VERIFIED_STOCK = $verifiedStockRow->verified_stock ?? null;
                        $item->TO_ADJUST = $verifiedStockRow->to_adjust ?? null;
                        $item->REASON = $verifiedStockRow->reason ?? null;
                        $item->REMARKS = $verifiedStockRow->REMARKS ?? null;
                        $item->APPROVED = (bool) $verifiedStockRow->approved;
                        $item->APPROVED_BY = $verifiedStockRow->approved_by ?? null;
                        $item->APPROVED_AT = $verifiedStockRow->approved_at ?? null;
                    } else {
                        $item->VERIFIED_STOCK = null;
                        $item->TO_ADJUST = null;
                        $item->REASON = null;
                        $item->REMARKS = null;
                        $item->APPROVED = false;
                        $item->APPROVED_BY = null;
                        $item->APPROVED_AT = null;
                    }

                    $adjustedQty = isset($item->TO_ADJUST) && is_numeric($item->TO_ADJUST) ? floatval($item->TO_ADJUST) : 0;
                    $item->LOSS_GAIN = round($adjustedQty * $lp, 2);
                }

                return $item;
            });

            $processedData = $mergedData->values();
            Log::info('Processed data count: ' . count($processedData));

            return response()->json([
                'message' => 'Data fetched successfully',
                'data' => $processedData,
                'status' => 200
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to fetch data from Google Sheet',
                'status' => $response->getStatusCode()
            ], $response->getStatusCode());
        }
    }



    // public function getViewVerificationAdjustmentData(Request $request)  //with product data with database
    // {
    //     $shopifyInventoryController = new ShopifyApiInventoryController();
    //     $inventoryResponse = $shopifyInventoryController->fetchInventoryWithCommitment();
    //     Log::info('Fetched inventory response:', $inventoryResponse);

    //     $inventoryData = collect($inventoryResponse); // ✅ already has original-case SKUs
    //     Log::info('Shopify Inventory Data:', $inventoryData->toArray());

    //     // ✅ REPLACED GOOGLE SHEET CALL WITH DB CALL
    //     $productMaster = ProductMaster::select('sku', 'parent')
    //         ->get()
    //         ->keyBy(fn($item) => trim($item->sku));

    //     $shopifyData = ShopifySku::select('sku', 'inv', 'quantity')
    //         ->get()
    //         ->keyBy(fn($item) => trim($item->sku));

    //     $skus = array_unique(array_merge(
    //         $productMaster->keys()->toArray(),
    //         $shopifyData->keys()->toArray(),
    //         $inventoryData->keys()->toArray()
    //     ));

    //     $verifiedStockData = Inventory::whereIn('sku', $skus)->get()
    //         ->mapWithKeys(function ($item) {
    //             return [trim($item->sku) => $item];
    //         });

    //     $mergedData = collect($skus)->map(function ($sku) use ($productMaster, $shopifyData, $inventoryData, $verifiedStockData) {
    //         $sku = trim($sku);
    //         $inv = isset($shopifyData[$sku]) ? $shopifyData[$sku]->inv : 0;
    //         $l30 = isset($shopifyData[$sku]) ? $shopifyData[$sku]->quantity  : 0;
    //         $dil = $inv > 0 ? round($l30 / $inv, 2) : 0;
    //         $parent = $productMaster[$sku]->parent ?? '';

    //         $onHand = $inventoryData[$sku]['on_hand'] ?? 'N/A';
    //         $committed = $inventoryData[$sku]['committed'] ?? 'N/A';
    //         $available = $inventoryData[$sku]['available_to_sell'] ?? 'N/A';

    //         // ✅ Optional: store to DB
    //         if (is_numeric($onHand)) {
    //             ShopifyInventory::updateOrCreate(
    //                 ['sku' => $sku],
    //                 [
    //                     'parent' => $parent,
    //                     'on_hand' => $onHand,
    //                     'committed' => $committed,
    //                     'available_to_sell' => $available,
    //                 ]
    //             );
    //         }

    //         $verified = $verifiedStockData[$sku] ?? null;
    //         $verifiedStock = $verified->verified_stock ?? null;
    //         $toAdjust = $verified->to_adjust ?? null;
    //         $reason = $verified->reason ?? null;
    //         $approved = (bool)($verified->approved ?? false);
    //         $approvedBy = $verified->approved_by ?? null;

    //         $adjustedQty = is_numeric($toAdjust) ? floatval($toAdjust) : 0;
    //         $lp = isset($shopifyData[$sku]) && is_numeric($shopifyData[$sku]->quantity) ? floatval($shopifyData[$sku]->quantity) : 0; // placeholder
    //         $lossGain = round($adjustedQty * $lp);

    //         return (object) [
    //             'SKU' => $sku,
    //             'Parent' => $parent,
    //             'INV' => $inv,
    //             'L30' => $l30,
    //             'DIL' => $dil,
    //             'ON_HAND' => $onHand,
    //             'COMMITTED' => $committed,
    //             'AVAILABLE_TO_SELL' => $available,
    //             'VERIFIED_STOCK' => $verifiedStock,
    //             'TO_ADJUST' => $toAdjust,
    //             'REASON' => $reason,
    //             'APPROVED' => $approved,
    //             'APPROVED_BY' => $approvedBy,
    //             'LOSS_GAIN' => $lossGain,
    //         ];
    //     });

    //     return response()->json([
    //         'message' => 'Data fetched successfully',
    //         'data' => $mergedData->values(),
    //         'status' => 200
    //     ]);
    // }




    // public function updateVerifiedStock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'sku' => 'nullable|string',
    //         'verified_stock' => 'required|numeric',
    //         'on_hand' => 'nullable|numeric',
    //         'reason' => 'required|string',
    //         'is_approved' => 'required|boolean',
    //     ]);

    //     $record = new Inventory();
    //     $record->sku = $validated['sku'];
    //     $record->verified_stock = $validated['verified_stock'];
    //     $record->reason = $validated['reason'];
    //     $record->is_approved = $validated['is_approved'];
    //     $record->approved_by = $validated['is_approved'] ? Auth::user()->name : null;
    //     $record->approved_at = $validated['is_approved'] ? Carbon::now('America/New_York') : null;
    //     $record->save();

    //     // $onHand = $validated['on_hand'] ?? 0;
    //     // $record->to_adjust = $record->verified_stock - $onHand; 
    //     // $record->save();
        
    //     if ($validated['is_approved']) {
    //         $sku = $validated['sku'];
    //         $toAdjust = $record->to_adjust;

    //         $inventoryItemId = null;
    //         // $hasMore = true;
    //         $pageInfo = null;

    //         do {
    //             $queryParams = ['limit' => 250];
    //             if ($pageInfo) {
    //                 $queryParams['page_info'] = $pageInfo;
    //             }

    //             // 1. Fetch products to get inventory_item_id
    //             $response  = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
    //                 ->get("https://{$this->shopifyDomain}/admin/api/2025-01/products.json", $queryParams);

    //             $products = $response->json('products');

    //             foreach ($products as $product) {
    //                 foreach ($product['variants'] as $variant) {
    //                     if ($variant['sku'] === $sku) {
    //                         $inventoryItemId = $variant['inventory_item_id'];
    //                         break 2;
    //                     }
    //                 }
    //             }

    //             $linkHeader = $response->header('Link');
    //             $pageInfo = null;
    //             if ($linkHeader && preg_match('/<([^>]+page_info=([^&>]+)[^>]*)>; rel="next"/', $linkHeader, $matches)) {
    //                 $pageInfo = $matches[2];
    //             }

    //         } while (!$inventoryItemId && $pageInfo);

    //         // $products = $productResponse->json('products');
    //         // $inventoryItemId = null;

    //         // foreach ($products as $product) {
    //         //     foreach ($product['variants'] as $variant) {
    //         //         if ($variant['sku'] === $sku) {
    //         //             $inventoryItemId = $variant['inventory_item_id'];
    //         //             break 2;
    //         //         }
    //         //     }
    //         // }

    //         if (!$inventoryItemId) {
    //             return response()->json(['success' => false, 'message' => 'Inventory item ID not found for SKU.']);
    //         }

    //         // 2. Get location_id using inventory_levels.json
    //         $invLevelResponse = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
    //             ->get("https://{$this->shopifyDomain}/admin/api/2025-01/inventory_levels.json", [
    //                 'inventory_item_ids' => $inventoryItemId
    //             ]);

    //         $levels = $invLevelResponse->json('inventory_levels');
    //         $locationId = $levels[0]['location_id'] ?? null;
    //         $currentAvailable = $levels[0]['available'] ?? 0;

    //         if (!$locationId) {
    //             return response()->json(['success' => false, 'message' => 'Location ID not found for inventory item.']);
    //         }

    //         $record->to_adjust = $verifiedToAdd;
    //         $record->save();

    //         // 3. Adjust inventory in Shopify
    //         $adjustResponse = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
    //             ->post("https://{$this->shopifyDomain}/admin/api/2025-01/inventory_levels/adjust.json", [
    //                 'inventory_item_id' => $inventoryItemId,
    //                 'location_id' => $locationId,
    //                 'available_adjustment' => $toAdjust,
    //             ]);
    //             Log::info('Shopify Adjust Response:', $adjustResponse->json());


    //         if (!$adjustResponse->successful()) {
    //             return response()->json(['success' => false, 'message' => 'Failed to update Shopify inventory.']);
    //         }
    //     }

    //     return response()->json(['success' => true, 'data' => $record]);
    // }

    public function updateVerifiedStock(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string',
            'verified_stock' => 'required|numeric',
            'on_hand' => 'nullable|numeric',
            'reason' => 'required|string',
            'remarks' => 'nullable|string',
            'is_approved' => 'required|boolean',
        ]);

        $lp = 0;    
        $response = $this->apiController->fetchDataFromProductMasterGoogleSheet(); 
        if ($response->getStatusCode() === 200) { 
            $sheetData = $response->getData()->data; 
            foreach ($sheetData as $row) { 
                if (isset($row->SKU) && strtoupper(trim($row->SKU)) === strtoupper(trim($validated['sku']))) { 
                    $lp = isset($row->LP) && is_numeric($row->LP) ? floatval($row->LP) : 0; 
                    break; 
                }
            }
        }

        $toAdjust = $validated['verified_stock'] - ($validated['on_hand'] ?? 0);
        $lossGain = round($toAdjust * $lp, 2);


        // Save record in DB
        $record = new Inventory();
        $record->sku = $validated['sku'];
        $record->on_hand = $validated['on_hand'];
        $record->verified_stock = $validated['verified_stock'];
        $record->reason = $validated['reason'];
        $record->remarks = $validated['remarks'];
        $record->is_approved = $validated['is_approved'];
        $record->approved_by = $validated['is_approved'] ? Auth::user()->name : null;
        $record->approved_at = $validated['is_approved'] ? Carbon::now('America/New_York') : null;
        $record->to_adjust = $toAdjust;
        $record->loss_gain = $lossGain;
        $record->is_hide = 0;
        $record->save();

        if ($validated['is_approved']) {
            $sku = $validated['sku'];
            // $verifiedToAdd = $validated['verified_stock']; // This is the value to add

            // 1. Fetch all products (with pagination to ensure all SKUs are fetched)
            $inventoryItemId = null;
            $pageInfo = null;

            do {
                $queryParams = ['limit' => 250];
                if ($pageInfo) {
                    $queryParams['page_info'] = $pageInfo;
                }

                $response = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
                    ->get("https://{$this->shopifyDomain}/admin/api/2025-01/products.json", $queryParams);

                $products = $response->json('products');

                foreach ($products as $product) {
                    foreach ($product['variants'] as $variant) {
                        if ($variant['sku'] === $sku) {
                            $inventoryItemId = $variant['inventory_item_id'];
                            break 2;
                        }
                    }
                }

                // Handle pagination
                $linkHeader = $response->header('Link');
                $pageInfo = null;
                if ($linkHeader && preg_match('/<([^>]+page_info=([^&>]+)[^>]*)>; rel="next"/', $linkHeader, $matches)) {
                    $pageInfo = $matches[2];
                }

            } while (!$inventoryItemId && $pageInfo);

            if (!$inventoryItemId) {
                return response()->json(['success' => false, 'message' => 'Inventory item ID not found for SKU.']);
            }

            // 2. Get location ID and current available
            $invLevelResponse = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
                ->get("https://{$this->shopifyDomain}/admin/api/2025-01/inventory_levels.json", [
                    'inventory_item_ids' => $inventoryItemId
                ]);

            $levels = $invLevelResponse->json('inventory_levels');
            $locationId = $levels[0]['location_id'] ?? null;
            // $currentAvailable = $levels[0]['available'] ?? 0;

            if (!$locationId) {
                return response()->json(['success' => false, 'message' => 'Location ID not found for inventory item.']);
            }

            // 4. Send inventory adjustment to Shopify
            $adjustResponse = Http::withBasicAuth($this->shopifyApiKey, $this->shopifyPassword)
                ->post("https://{$this->shopifyDomain}/admin/api/2025-01/inventory_levels/adjust.json", [
                    'inventory_item_id' => $inventoryItemId,
                    'location_id' => $locationId,
                    'available_adjustment' => $toAdjust,
                ]);

            Log::info('Shopify Adjust Response:', $adjustResponse->json());

            if (!$adjustResponse->successful()) {
                return response()->json(['success' => false, 'message' => 'Failed to update Shopify inventory.']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sku' => $record->sku,
                'verified_stock' => $record->verified_stock,
                'reason' => $record->reason,
                'remarks' => $record->remarks,
                'is_approved' => $record->is_approved,
                'approved_by' => $record->approved_by,
                'approved_at' => optional($record->approved_at)->format('Y-m-d\TH:i:s.u\Z'),
                'created_at' => optional($record->created_at)->format('Y-m-d\TH:i:s.u\Z'),
                'updated_at' => optional($record->updated_at)->format('Y-m-d\TH:i:s.u\Z'),
                'to_adjust' => $record->to_adjust,
                'loss_gain' => $lossGain, // Only used in response, not stored
            ]
        ]);
    }


    public function getVerifiedStock()
    {
        $savedInventories = Inventory::all();


        // Format data to return in JSON with key 'data'
        $data = $savedInventories->map(function ($item) {

            return [
                'sku' => strtoupper(trim($item->sku)),
                'R&A' => (bool) $item->is_ra_checked,
                'verified_stock' => $item->verified_stock,
                'reason' => $item->reason,
                'is_approved' => (bool) $item->is_approved,
                'approved_by_ih' => (bool) $item->approved_by_ih,
                'approved_by' => $item->approved_by,
                'approved_at' =>  $item->approved_at,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function updateApprovedByIH(Request $request)
    {
        $request->validate([
            'sku' => 'required|string',
            'approved_by_ih' => 'required|boolean',
        ]);

        $inventory = Inventory::where('sku', $request->sku)->first();

        if (!$inventory) {
            return response()->json(['success' => false, 'message' => 'SKU not found.']);
        }

        $inventory->approved_by_ih = $request->approved_by_ih;
        $inventory->save();

        return response()->json(['success' => true]);
    }


    public function updateRAStatus(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string',
            'is_ra_checked' => 'required|boolean'
        ]);

        $inventory = Inventory::where('sku', $validated['sku'])->first();

        if ($inventory) {
            // SKU exists → Only update is_ra_checked
            $inventory->is_ra_checked = $validated['is_ra_checked'];
            $inventory->save();
        } else {
            //  SKU not found → Create new record
            $inventory = Inventory::create([
                'sku' => $validated['sku'],
                'is_ra_checked' => $validated['is_ra_checked'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function getVerifiedStockActivityLog()
    {
        $activityLogs = Inventory::where('type', null)->where('is_approved', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                return [
                    'sku' => $item->sku,
                    'verified_stock' => $item->verified_stock,
                    'to_adjust' => $item->to_adjust,
                    'loss_gain' => $item->loss_gain,
                    'reason' => $item->reason,
                    'remarks' => $item->remarks,
                    'approved_by' => $item->approved_by,
                    'approved_at' => Carbon::parse($item->created_at)->timezone('America/New_York')->format('d M Y, h:i A'),
                ];
            });
            
        return response()->json(['data' => $activityLogs]);
    }


    public function viewInventory()
    {
        return view('inventory-management.view-inventory');
    }

    public function getSkuWiseHistory(Request $request)
    {
        $sku = $request->input('sku');

        $query = Inventory::where('is_approved', true);

        if ($sku) {
            $query->where('sku', $sku);
        }

        $activityLogs = $query->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                return [
                    'sku' => $item->sku,
                    'verified_stock' => $item->verified_stock,
                    'to_adjust' => $item->to_adjust,
                    'on_hand' => $item->on_hand,
                    'reason' => $item->reason,
                    'approved_by' => $item->approved_by,
                    'approved_at' => Carbon::parse($item->created_at)->timezone('America/New_York')->format('d M Y, h:i A'),
                ];
            });

        return response()->json(['data' => $activityLogs]);
    }


    public function toggleHide(Request $request)
    {
        $latestRecord = Inventory::where('sku', $request->sku)->latest()->first();

        if ($latestRecord) {
            $latestRecord->update(['is_hide' => 1]);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Record not found.']);
    }


    public function getHiddenRows()
    {
        $latestHiddenIds = Inventory::select(DB::raw('MAX(id) as latest_id'))
            ->where('is_hide', 1)
            ->groupBy('sku')
            ->pluck('latest_id');

        $hiddenRecords = Inventory::whereIn('id', $latestHiddenIds)->get();

        $data = $hiddenRecords->map(function ($item) {
            return [
                'sku' => $item->sku,
                'verified_stock' => $item->verified_stock,
                'to_adjust' => $item->to_adjust,
                'loss_gain' => $item->loss_gain, // already stored in DB
                'reason' => $item->reason,
                'approved_by' => $item->approved_by,
                'approved_at' => $item->approved_at 
                    ? Carbon::parse($item->approved_at)->timezone('America/New_York')->format('Y-m-d H:i:s') 
                    : null,
                'remarks' => $item->remarks ?? '-',
            ];
        });

        return response()->json(['data' => $data]);
    }


    public function unhideMultipleRows(Request $request)
    {
        $skus = $request->skus ?? [];

        foreach ($skus as $sku) {
            $latest = Inventory::where('sku', $sku)->where('is_hide', 1)->latest()->first();
            if ($latest) {
                $latest->update(['is_hide' => 0]);
            }
        }

        return response()->json(['success' => true]);
    }

    
}
