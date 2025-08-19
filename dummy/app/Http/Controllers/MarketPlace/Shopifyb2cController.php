<?php

namespace App\Http\Controllers\MarketPlace;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\MarketplacePercentage;
use App\Models\Shopifyb2cDataView;
use App\Models\ShopifySku;
use App\Models\ProductMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class Shopifyb2cController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    public function shopifyb2cView(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('shopifyb2c_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'ShopifyB2C')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.shopifyb2c', [
            'mode' => $mode,
            'demo' => $demo,
            'shopifyb2cPercentage' => $percentage
        ]);
    }





     public function shopifyPricingCvr(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('shopifyb2c_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'ShopifyB2C')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.shopify_pricing_cvr', [
            'mode' => $mode,
            'demo' => $demo,
            'shopifyb2cPercentage' => $percentage
        ]);
    }


     public function shopifyb2cViewPricingIncreaseDecrease(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('shopifyb2c_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'ShopifyB2C')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.shopifyb2c_pricing_increase_decrease', [
            'mode' => $mode,
            'demo' => $demo,
            'shopifyb2cPercentage' => $percentage
        ]);
    }
    public function getViewShopifyB2CData(Request $request)
    {

        
        $response = $this->apiController->fetchShopifyB2CListingData();

        if ($response->getStatusCode() === 200) {
            $data = $response->getData(); 

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

            $nrValues = Shopifyb2cDataView::pluck('value', 'sku');

            $filteredData = array_filter($data->data, function ($item) {
                $parent = $item->Parent ?? '';
                $childSku = $item->{'(Child) sku'} ?? '';
                return !(empty(trim($parent)) && empty(trim($childSku)));
            });

            $processedData = array_map(function ($item) use ($shopifyData, $nrValues) {
                $childSku = $item->{'(Child) sku'} ?? '';

                if (!empty($childSku) && stripos($childSku, 'PARENT') === false) {
                    if ($shopifyData->has($childSku)) {
                        $skuData = $shopifyData[$childSku];
                        $item->INV = $skuData->inv;
                        $item->L30 = $skuData->quantity;

                        $item->SPRICE = $skuData->SPRICE ?? null;
                        $item->SPFT   = $skuData->SPFT ?? null;
                        $item->SROI   = $skuData->SROI ?? null;

                        // LP & SHIP extraction
                        $values = is_array($skuData->Values)
                            ? $skuData->Values
                            : (is_string($skuData->Values) ? json_decode($skuData->Values, true) : []);

                        $lp = 0;
                        foreach ($values as $k => $v) {
                            if (strtolower($k) === 'lp') {
                                $lp = floatval($v);
                                break;
                            }
                        }
                        if ($lp === 0 && isset($skuData->lp)) {
                            $lp = floatval($skuData->lp);
                        }

                        $ship = isset($values['ship']) ? floatval($values['ship']) : (isset($skuData->ship) ? floatval($skuData->ship) : 0);

                        $item->LP_productmaster = $lp;
                        $item->Ship_productmaster = $ship;

                        // Profit Calculations
                        $price = floatval($item->SPRICE ?? 0);
                        $units_ordered_l30 = floatval($item->L30 ?? 0);
                        $percentage = 1; // default 100%

                        $item->Total_pft = round(($price * $percentage - $lp - $ship) * $units_ordered_l30, 2);
                        $item->T_Sale_l30 = round($price * $units_ordered_l30, 2);
                        $item->PFT_percentage = round(
                            $price > 0 ? (($price * $percentage - $lp - $ship) / $price) * 100 : 0,
                            2
                        );
                        $item->ROI_percentage = round(
                            $lp > 0 ? (($price * $percentage - $lp - $ship) / $lp) * 100 : 0,
                            2
                        );
                        $item->T_COGS = round($lp * $units_ordered_l30, 2);
                    } else {
                        $item->INV = 0;
                        $item->L30 = 0;
                        $item->SPRICE = null;
                        $item->SPFT = null;
                        $item->SROI = null;
                    }

                    // NR Handling
                    $item->NR = false;
                    if ($childSku && isset($nrValues[$childSku])) {
                        $val = $nrValues[$childSku];
                        if (is_array($val)) {
                            $item->NR = $val['NR'] ?? false;
                        } else {
                            $decoded = json_decode($val, true);
                            $item->NR = $decoded['NR'] ?? false;
                        }
                    }
                }

                return (array) $item;

            }, $filteredData);

            $processedData = array_values($processedData);

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

    public function updateAllShopifyB2CSkus(Request $request)
    {
        try {
            $percent = $request->input('percent');

            if (!is_numeric($percent) || $percent < 0 || $percent > 100) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid percentage value. Must be between 0 and 100.'
                ], 400);
            }

            MarketplacePercentage::updateOrCreate(
                ['marketplace' => 'ShopifyB2C'],
                ['percentage' => $percent]
            );

            Cache::put('shopifyb2c_marketplace_percentage', $percent, now()->addDays(30));

            return response()->json([
                'status' => 200,
                'message' => 'Percentage updated successfully',
                'data' => [
                    'marketplace' => 'ShopifyB2C',
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

    public function saveNrToDatabase(Request $request)
    {
        $sku = $request->input('sku');
        $nr = $request->input('nr');

        if (!$sku || $nr === null) {
            return response()->json(['error' => 'SKU and nr are required.'], 400);
        }

        $ebayDataView = Shopifyb2cDataView::firstOrNew(['sku' => $sku]);
        $value = is_array($ebayDataView->value) ? $ebayDataView->value : (json_decode($ebayDataView->value, true) ?: []);
        $value['NR'] = filter_var($nr, FILTER_VALIDATE_BOOLEAN);
        $ebayDataView->value = $value;
        $ebayDataView->save();

        return response()->json(['success' => true, 'data' => $ebayDataView]);
    }


 public function saveSpriceToDatabase(Request $request)
    {
        // LOG::info('Saving Shopify pricing data', $request->all());
        $sku = $request->input('sku');
        $spriceData = $request->only(['sprice', 'spft_percent', 'sroi_percent']);

        if (!$sku || !$spriceData['sprice']) {
            return response()->json(['error' => 'SKU and sprice are required.'], 400);
        }

        $shopifyDataView = Shopifyb2cDataView::firstOrNew(['sku' => $sku]);
        // Decode value column safely
        $existing = is_array($shopifyDataView->value)
            ? $shopifyDataView->value
            : (json_decode($shopifyDataView->value, true) ?: []);

        // Merge new sprice data
        $merged = array_merge($existing, [
            'SPRICE' => $spriceData['sprice'],
            'SPFT' => $spriceData['spft_percent'],
            'SROI' => $spriceData['sroi_percent'],
        ]);

        $shopifyDataView->value = $merged;
        $shopifyDataView->save();

    return redirect()->back()->with('success', 'Data fetched successfully.');
    }

}