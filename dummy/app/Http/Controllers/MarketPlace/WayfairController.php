<?php

namespace App\Http\Controllers\MarketPlace;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\MarketplacePercentage;
use App\Models\ShopifySku;
use App\Models\JungleScoutProductData;
use App\Models\WayfairDataView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WayfairController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    public function wayfairView(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('wayfair_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'Wayfair')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.Wayfair', [
            'mode' => $mode,
            'demo' => $demo,
            'wayfairPercentage' => $percentage

        ]);
    }

    public function getAllData()
    {
        $amazonDatas = $this->apiController->fetchExternalData2();
        return response()->json($amazonDatas);
    }

    public function getViewWayfairData(Request $request)
    {
        $response = $this->apiController->fetchDataFromWayfairMasterGoogleSheet();

        if ($response->getStatusCode() === 200) {
            $data = $response->getData();

            // Get JungleScout data with proper price handling
            $jungleScoutData = JungleScoutProductData::all()
                ->groupBy('parent')
                ->map(function ($group) {
                    // Get all valid numeric prices > 0
                    $validPrices = $group->filter(function ($item) {
                        $price = $item->data['price'] ?? null;
                        return is_numeric($price) && $price > 0;
                    })->pluck('data.price');

                    return [
                        'scout_parent' => $group->first()->parent,
                        'min_price' => $validPrices->isNotEmpty() ? $validPrices->min() : null,
                        'product_count' => $group->count(),
                        'all_data' => $group->map(function ($item) {
                            // Ensure price is properly formatted
                            $data = $item->data;
                            if (isset($data['price'])) {
                                $data['price'] = is_numeric($data['price']) ? (float) $data['price'] : null;
                            }
                            return $data;
                        })->toArray()
                    ];
                });

            $skus = collect($data->data)
                ->filter(function ($item) {
                    $childSku = $item->{'(Child) sku'} ?? '';
                    return !empty($childSku) && stripos($childSku, 'PARENT') === false;
                })
                ->pluck('(Child) sku')
                ->unique()
                ->toArray();

            $shopifyData = ShopifySku::whereIn('sku', $skus)
                ->get()
                ->keyBy('sku');
            
            // Fetch NR values before processing data
            $nrValues = WayfairDataView::pluck('value', 'sku');

            $filteredData = array_filter($data->data, function ($item) {
                $parent = $item->Parent ?? '';
                $childSku = $item->{'(Child) sku'} ?? '';
                return !(empty(trim($parent)) && empty(trim($childSku)));
            });

            $processedData = array_map(function ($item) use ($shopifyData, $jungleScoutData, $nrValues) {
                $childSku = $item->{'(Child) sku'} ?? '';
                $parentAsin = $item->Parent ?? '';

                // Add JungleScout data if parent ASIN matches
                if (!empty($parentAsin) && $jungleScoutData->has($parentAsin)) {
                    $scoutData = $jungleScoutData[$parentAsin];
                    $item->scout_data = [
                        'scout_parent' => $scoutData['scout_parent'],
                        'min_price' => $scoutData['min_price'],
                        'product_count' => $scoutData['product_count'],
                        'all_data' => $scoutData['all_data']
                    ];
                }

                if (!empty($childSku) && stripos($childSku, 'PARENT') === false) {
                    if ($shopifyData->has($childSku)) {
                        $item->INV = $shopifyData[$childSku]->inv;
                        $item->L30 = $shopifyData[$childSku]->quantity;
                    } else {
                        $item->INV = 0;
                        $item->L30 = 0;
                    }

                     // NR value
                    $item->NR = false;
                    if ($childSku && isset($nrValues[$childSku])) {
                        $val = $nrValues[$childSku];
                        if (is_array($val)) {
                            $item->NR = $val['NR'] ?? 'REQ';
                        } else {
                            $decoded = json_decode($val, true);
                            $item->NR = $decoded['NR'] ?? 'REQ';
                        }
                    }
                }

                return $item;
            }, $filteredData);

            $processedData = array_values($processedData);

            return response()->json([
                'message' => 'Data fetched successfully',
                'data' => $processedData,
                'status' => 200,
                'debug' => [
                    'jungle_scout_parents' => $jungleScoutData->keys()->take(5),
                    'matched_parents' => collect($processedData)
                        ->filter(fn($item) => isset($item->scout_data))
                        ->pluck('Parent')
                        ->unique()
                        ->values()
                ]
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to fetch data from Google Sheet',
                'status' => $response->getStatusCode()
            ], $response->getStatusCode());
        }
    } 


    public function updateAllWayfairSkus(Request $request)
    {
        try {
            $percent = $request->input('percent');

            if (!is_numeric($percent) || $percent < 0 || $percent > 100) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid percentage value. Must be between 0 and 100.'
                ], 400);
            }

            // Update database
            MarketplacePercentage::updateOrCreate(
                ['marketplace' => 'Wayfair'],
                ['percentage' => $percent]
            );

            // Store in cache
            Cache::put('wayfair_marketplace_percentage', $percent, now()->addDays(30));

            return response()->json([
                'status' => 200,
                'message' => 'Percentage updated successfully',
                'data' => [
                    'marketplace' => 'Wayfair',
                    'percentage' => $percent
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating percentage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Save NR value for a SKU
    public function saveNrToDatabase(Request $request)
    {
        $sku = $request->input('sku');
        $nr = $request->input('nr');

        if (!$sku || $nr === null) {
            return response()->json(['error' => 'SKU and nr are required.'], 400);
        }

        $dataView = WayfairDataView::firstOrNew(['sku' => $sku]);
        $value = is_array($dataView->value) ? $dataView->value : (json_decode($dataView->value, true) ?: []);
        if ($nr !== null) {
            $value["NR"] = $nr === 'NR' ? 'NR' : 'REQ';
        }
        $dataView->value = $value;
        $dataView->save();

        return response()->json(['success' => true, 'data' => $dataView]);
    }

}