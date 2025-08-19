<?php

namespace App\Http\Controllers\MarketPlace;

use App\Models\ShopifySku;
use Illuminate\Http\Request;
use App\Models\ProductMaster;
use App\Models\AmazonDataView;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\AmazonSpApiService;
use App\Models\MarketplacePercentage;
use Illuminate\Support\Facades\Cache;
use App\Models\JungleScoutProductData;
use App\Http\Controllers\ApiController;
use App\Jobs\UpdateAmazonSPriceJob;
use App\Models\AmazonDatasheet; // Add this at the top with other use statements
use App\Models\ChannelMaster;
use Illuminate\Support\Facades\DB;

class OverallAmazonController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    public function overallAmazon(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('amazon_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'Amazon')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.overallAmazon', [
            'mode' => $mode,
            'demo' => $demo,
            'amazonPercentage' => $percentage
        ]);
    }
public function updateFbaStatus(Request $request)
{
    $sku = $request->input('shopify_id');
    $fbaStatus = $request->input('fba');

    if (!$sku || !is_numeric($fbaStatus)) {
        return response()->json(['error' => 'SKU and FBA status are required.'], 400);
    }
    $amazonData = DB::table('amazon_data_view')
        ->where('sku', $sku)
        ->first();

    if (!$amazonData) {
        return response()->json(['error' => 'SKU not found.'], 404);
    }
    DB::table('amazon_data_view')
        ->where('sku', $sku)
        ->update(['fba' => $fbaStatus]);
    $updatedData = DB::table('amazon_data_view')
        ->where('sku', $sku)
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'FBA status updated successfully.',
        'data' => $updatedData
    ]);
}


    public function getViewAmazonData(Request $request)
    {

        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();

        $amazonDatasheetsBySku = AmazonDatasheet::whereIn('sku', $skus)->get()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');

        $response = $this->apiController->fetchDataFromAmazonGoogleSheet();
        $apiDataArr = ($response->getStatusCode() === 200) ? ($response->getData()->data ?? []) : [];
        $apiDataBySku = [];
        foreach ($apiDataArr as $item) {
            $sku = isset($item->{'(Child) sku'}) ? strtoupper(trim($item->{'(Child) sku'})) : null;
            if ($sku)
                $apiDataBySku[$sku] = $item;
        }

        $parents = $productMasters->pluck('parent')->filter()->unique()->map('strtoupper')->values()->all();
        // JungleScout Data
        $jungleScoutData = JungleScoutProductData::whereIn('parent', $parents)
            ->get()
            ->groupBy(function ($item) {
                return strtoupper(trim($item->parent));
            })
            ->map(function ($group) {
                $validPrices = $group->filter(function ($item) {
                    $data = is_array($item->data) ? $item->data : [];
                    $price = $data['price'] ?? null;
                    return is_numeric($price) && $price > 0;
                })->pluck('data.price');

                return [
                    'scout_parent' => $group->first()->parent,
                    'min_price' => $validPrices->isNotEmpty() ? $validPrices->min() : null,
                    'product_count' => $group->count(),
                    'all_data' => $group->map(function ($item) {
                        $data = is_array($item->data) ? $item->data : [];
                        if (isset($data['price'])) {
                            $data['price'] = is_numeric($data['price']) ? (float) $data['price'] : null;
                        }
                        return $data;
                    })->toArray()
                ];
            });

        $nrValues = AmazonDataView::whereIn('sku', $skus)->pluck('value', 'sku','fba');

        $percentage = Cache::remember('amazon_marketplace_percentage', now()->addDays(30), function () {
            return MarketplacePercentage::where('marketplace', 'Amazon')->value('percentage') ?? 100;
        });
        $percentage = $percentage / 100;

        $result = [];


        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);

            // Skip rows where SKU starts with "PARENT"
            if (str_starts_with($sku, 'PARENT ')) {
                continue;
            }

            $parent = $pm->parent;
            $apiItem = $apiDataBySku[$sku] ?? null;
            $amazonSheet = $amazonDatasheetsBySku[$sku] ?? null;
            $shopify = $shopifyData[$pm->sku] ?? null;

            $row = [];
            $row['Parent'] = $parent;
            $row['(Child) sku'] = $pm->sku;

            if ($apiItem) {
                foreach ($apiItem as $k => $v) {
                    $row[$k] = $v;
                }
            }

            if ($amazonSheet) {
                $row['A_L30'] = $row['A_L30'] ?? $amazonSheet->units_ordered_l30;
                $row['Sess30'] = $row['Sess30'] ?? $amazonSheet->sessions_l30;
                $row['price'] = $row['price'] ?? $amazonSheet->price;
                $row['sessions_l60'] = $row['sessions_l60'] ?? $amazonSheet->sessions_l60;
                $row['units_ordered_l60'] = $row['units_ordered_l60'] ?? $amazonSheet->units_ordered_l60;
            }

            $row['INV'] = $shopify->inv ?? 0;
            $row['L30'] = $shopify->quantity ?? 0;
            $row['fba'] = $pm->fba;

            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);
            $lp = 0;
            foreach ($values as $k => $v) {
                if (strtolower($k) === 'lp') {
                    $lp = floatval($v);
                    break;
                }
            }
            if ($lp === 0 && isset($pm->lp)) {
                $lp = floatval($pm->lp);
            }
            $ship = isset($values['ship']) ? floatval($values['ship']) : (isset($pm->ship) ? floatval($pm->ship) : 0);

            $price = isset($row['price']) ? floatval($row['price']) : 0;
            $units_ordered_l30 = isset($row['A_L30']) ? floatval($row['A_L30']) : 0;
            $row['Total_pft'] = round((($price * $percentage) - $lp - $ship) * $units_ordered_l30, 2);
            $row['T_Sale_l30'] = round($price * $units_ordered_l30, 2);
            $row['PFT_percentage'] = round($price > 0 ? ((($price * $percentage) - $lp - $ship) / $price) * 100 : 0, 2);
            $row['ROI_percentage'] = round($lp > 0 ? ((($price * $percentage) - $lp - $ship) / $lp) * 100 : 0, 2);
            $row['T_COGS'] = round($lp * $units_ordered_l30, 2);

            $parentKey = strtoupper($parent);
            if (!empty($parentKey) && $jungleScoutData->has($parentKey)) {
                $row['scout_data'] = $jungleScoutData[$parentKey];
            }

            $row['percentage'] = $percentage;
            $row['LP_productmaster'] = $lp;
            $row['Ship_productmaster'] = $ship;

            $row['NR'] = '';
            $row['FBA'] = null;
            $row['SPRICE'] = null;
            $row['Spft'] = null;
            $row['SROI'] = null;
            $row['Listed'] = null;
            $row['Live'] = null;
            $row['Spend'] = null;
            $row['APlus'] = null;
            $row['js_comp_manual_api_link'] = null;
            $row['js_comp_manual_link'] = null;

            if (isset($nrValues[$pm->sku])) {
                $raw = $nrValues[$pm->sku];

                if (!is_array($raw)) {
                    $raw = json_decode($raw, true);
                }

                if (is_array($raw)) {
                    // $row['NR'] = filter_var($raw['NR'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $row['NR'] = $raw['NR'] ?? null;
                    $row['FBA'] = $raw['FBA'] ?? null;
                    $row['shopify_id'] = $shopify->id ?? null;
                    $row['SPRICE'] = $raw['SPRICE'] ?? null;
                    $row['Spft%'] = $raw['SPFT'] ?? null;
                    $row['Spend'] = $raw['Spend'] ?? null;
                    $row['SROI'] = $raw['SROI'] ?? null;
                    $row['Listed'] = isset($raw['Listed']) ? filter_var($raw['Listed'], FILTER_VALIDATE_BOOLEAN) : null;
                    $row['Live'] = isset($raw['Live']) ? filter_var($raw['Live'], FILTER_VALIDATE_BOOLEAN) : null;
                    $row['APlus'] = isset($raw['APlus']) ? filter_var($raw['APlus'], FILTER_VALIDATE_BOOLEAN) : null;
                    $row['js_comp_manual_api_link'] = $raw['js_comp_manual_api_link'] ?? '';
                    $row['js_comp_manual_link'] = $raw['js_comp_manual_link'] ?? '';
                }
            }

            $row['image_path'] = $shopify->image_src ?? ($values['image_path'] ?? null);

            $result[] = (object) $row;
        }

        $groupedByParent = collect($result)->groupBy('Parent');
        $finalResult = [];

        foreach ($groupedByParent as $parent => $rows) {
            foreach ($rows as $row) {
                $finalResult[] = $row;
            }

            if (empty($parent)) {
                continue;
            }

            $sumRow = [
                '(Child) sku' => 'PARENT ' . $parent,
                'Parent' => $parent,
                'INV' => $rows->sum('INV'),
                'OV_L30' => $rows->sum('OV_L30'),
                'AVG_Price' => null,
                'MSRP' => null,
                'MAP' => null,
                'is_parent_summary' => true,
                // Add more fields if needed
            ];

            $finalResult[] = (object) $sumRow;
        }

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $finalResult,
            'status' => 200,
        ]);
    }


    public function updateAllAmazonSkus(Request $request)
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
                ['marketplace' => 'Amazon'],
                ['percentage' => $percent]
            );

            // Store in cache
            Cache::put('amazon_marketplace_percentage', $percent, now()->addDays(30));

            return response()->json([
                'status' => 200,
                'message' => 'Percentage updated successfully',
                'data' => [
                    'marketplace' => 'Amazon',
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


    // In your controller (e.g., AmazonController.php)
    // public function saveNrToDatabase(Request $request)
    // {
    //     $sku = $request->input('sku');
    //     $nrInput = $request->input('nr');      // Optional
    //     $spend = $request->input('spend');     // Optional

    //     if (!$sku) {
    //         return response()->json(['error' => 'SKU is required.'], 400);
    //     }

    //     // Decode NR JSON if present
    //     $nr = [];
    //     if ($nrInput) {
    //         $nr = is_array($nrInput) ? $nrInput : json_decode($nrInput, true);
    //         if (!is_array($nr) || !isset($nr['NR'])) {
    //             return response()->json(['error' => 'Invalid NR format.'], 400);
    //         }
    //     }

    //     // Fetch or create the record
    //     $amazonDataView = \App\Models\AmazonDataView::firstOrNew(['sku' => $sku]);

    //     // Decode existing value JSON
    //     $existing = is_array($amazonDataView->value)
    //         ? $amazonDataView->value
    //         : (json_decode($amazonDataView->value, true) ?? []);

    //     // Merge new data
    //     $merged = $existing;
    //     if (!empty($nr)) {
    //         $merged['NR'] = $nr['NR'];
    //     }
    //     if (!is_null($spend)) {
    //         $merged['Spend'] = $spend;
    //     }

    //     $amazonDataView->value = $merged;
    //     $amazonDataView->save();

    //     return response()->json(['success' => true, 'data' => $amazonDataView]);
    // }


    public function saveNrToDatabase(Request $request)
{
    $sku = $request->input('sku');
    $nrInput = $request->input('nr');   // Optional
    $fbaInput = $request->input('fba'); // Optional
    $spend = $request->input('spend');  // Optional

    if (!$sku) {
        return response()->json(['error' => 'SKU is required.'], 400);
    }

    // Fetch or create the record
    $amazonDataView = \App\Models\AmazonDataView::firstOrNew(['sku' => $sku]);

    // Decode existing value JSON
    $existing = is_array($amazonDataView->value)
        ? $amazonDataView->value
        : (json_decode($amazonDataView->value, true) ?? []);

    // Handle NR
    if ($nrInput) {
        $nr = is_array($nrInput) ? $nrInput : json_decode($nrInput, true);
        if (!is_array($nr) || !isset($nr['NR'])) {
            return response()->json(['error' => 'Invalid NR format.'], 400);
        }
        $existing['NR'] = $nr['NR'];
    }

    // Handle FBA
    if ($fbaInput) {
        $fba = is_array($fbaInput) ? $fbaInput : json_decode($fbaInput, true);
        if (!is_array($fba) || !isset($fba['FBA'])) {
            return response()->json(['error' => 'Invalid FBA format.'], 400);
        }
        $existing['FBA'] = $fba['FBA'];
    }

    // Handle spend
    if (!is_null($spend)) {
        $existing['Spend'] = $spend;
    }

    $amazonDataView->value = $existing;
    $amazonDataView->save();

    return response()->json(['success' => true, 'data' => $amazonDataView]);
}




    public function amazonPricingCVR(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();

        $amazonDatasheetsBySku = AmazonDatasheet::whereIn('sku', $skus)->get()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });
        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');

        $response = $this->apiController->fetchDataFromAmazonGoogleSheet();
        $apiDataArr = ($response->getStatusCode() === 200) ? ($response->getData()->data ?? []) : [];
        $apiDataBySku = [];
        foreach ($apiDataArr as $item) {
            $sku = isset($item->{'(Child) sku'}) ? strtoupper(trim($item->{'(Child) sku'})) : null;
            if ($sku)
                $apiDataBySku[$sku] = $item;
        }

        $parents = $productMasters->pluck('parent')->filter()->unique()->map('strtoupper')->values()->all();
        $jungleScoutData = JungleScoutProductData::whereIn('parent', $parents)
            ->get()
            ->groupBy(function ($item) {
                return strtoupper(trim($item->parent));
            });

        $nrValues = AmazonDataView::whereIn('sku', $skus)->pluck('value', 'sku');

        $percentage = MarketplacePercentage::where('marketplace', 'Amazon')->value('percentage') ?? 100;
        $percentage = $percentage / 100;

        $result = [];
        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);
            $parent = $pm->parent;
            $apiItem = $apiDataBySku[$sku] ?? null;
            $amazonSheet = $amazonDatasheetsBySku[$sku] ?? null;
            $shopify = $shopifyData[$pm->sku] ?? null;

            $row = [];
            $row['Parent'] = $parent;
            $row['(Child) sku'] = $pm->sku;

            if ($apiItem) {
                foreach ($apiItem as $k => $v) {
                    $row[$k] = $v;
                }
            }

            if ($amazonSheet) {
                $row['A_L30'] = $row['A_L30'] ?? $amazonSheet->units_ordered_l30;
                $row['Sess30'] = $row['Sess30'] ?? $amazonSheet->sessions_l30;
                $row['price'] = $row['price'] ?? $amazonSheet->price;
                $row['sessions_l60'] = $row['sessions_l60'] ?? $amazonSheet->sessions_l60;
                $row['units_ordered_l60'] = $row['units_ordered_l60'] ?? $amazonSheet->units_ordered_l60;
            }

            $row['INV'] = $shopify->inv ?? 0;
            $row['L30'] = $shopify->quantity ?? 0;

            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);
            $lp = 0;
            foreach ($values as $k => $v) {
                if (strtolower($k) === 'lp') {
                    $lp = floatval($v);
                    break;
                }
            }
            if ($lp === 0 && isset($pm->lp)) {
                $lp = floatval($pm->lp);
            }
            $ship = isset($values['ship']) ? floatval($values['ship']) : (isset($pm->ship) ? floatval($pm->ship) : 0);

            $price = isset($row['price']) ? floatval($row['price']) : 0;
            $units_ordered_l30 = isset($row['A_L30']) ? floatval($row['A_L30']) : 0;
            $row['Total_pft'] = round((($price * $percentage) - $lp - $ship) * $units_ordered_l30, 2);
            $row['T_Sale_l30'] = round($price * $units_ordered_l30, 2);
            $row['PFT_percentage'] = round($price > 0 ? ((($price * $percentage) - $lp - $ship) / $price) * 100 : 0, 2);
            $row['ROI_percentage'] = round($lp > 0 ? ((($price * $percentage) - $lp - $ship) / $lp) * 100 : 0, 2);
            $row['T_COGS'] = round($lp * $units_ordered_l30, 2);

            $parentKey = strtoupper($parent);
            if (!empty($parentKey) && $jungleScoutData->has($parentKey)) {
                $row['scout_data'] = $jungleScoutData[$parentKey];
            }

            $row['percentage'] = $percentage;
            $row['LP_productmaster'] = $lp;
            $row['Ship_productmaster'] = $ship;

            $row['NR'] = false;
            $row['SPRICE'] = null;
            $row['SPFT'] = null;
            $row['SROI'] = null;
            $row['Listed'] = null;
            $row['APlus'] = null;

            if (isset($nrValues[$pm->sku])) {
                $raw = $nrValues[$pm->sku];

                if (!is_array($raw)) {
                    $raw = json_decode($raw, true);
                }

                if (is_array($raw)) {
                    $row['NR'] = $raw['NR'] ?? 'REQ';
                    $row['SPRICE'] = $raw['SPRICE'] ?? null;
                    $row['SPFT'] = $raw['SPFT'] ?? null;
                    $row['SROI'] = $raw['SROI'] ?? null;
                    $row['Listed'] = isset($raw['Listed']) ? filter_var($raw['Listed'], FILTER_VALIDATE_BOOLEAN) : null;
                    $row['APlus'] = isset($raw['APlus']) ? filter_var($raw['APlus'], FILTER_VALIDATE_BOOLEAN) : null;
                }
            }


            $row['image_path'] = $shopify->image_src ?? ($values['image_path'] ?? null);

            $inv = floatval($row['INV'] ?? 0);
            $views = floatval($row['Sess30'] ?? 0);

            if ($mode === 'filtered') {
                if (($inv == 0 && $views == 0) || ($inv > 0 && $views == 0)) {
                    continue;
                }
            }


            $result[] = (object) $row;
        }

        return view('market-places.amazon_pricing_cvr', [
            'mode' => $mode,
            'demo' => $demo,
            'amazonPercentage' => $percentage * 100,
            'products' => $result

        ]);
    }

    public function saveSpriceToDatabase(Request $request)
    {
        $sku = $request->input('sku');
        $price = $request["sprice"];
        $sID = env('AMAZON_SELLER_ID');

        $spriceData = $request->only(['sprice', 'spft_percent', 'sroi_percent']);

        if (!$sku || !$spriceData['sprice']) {
            return response()->json(['error' => 'SKU and sprice are required.'], 400);
        }

        $amazonDataView = AmazonDataView::firstOrNew(['sku' => $sku]);

        // Decode value column safely
        $existing = is_array($amazonDataView->value) ? $amazonDataView->value : (json_decode($amazonDataView->value, true) ?: []);
        
        $changeAmzPrice = UpdateAmazonSPriceJob::dispatch($sID, $sku, $price)->delay(now()->addMinutes(3));

        // Merge new sprice data
        $merged = array_merge($existing, [
            'SPRICE' => $spriceData['sprice'],
            'SPFT' => $spriceData['spft_percent'],
            'SROI' => $spriceData['sroi_percent'],
        ]);

        $amazonDataView->value = $merged;
        $amazonDataView->save();

        return response()->json(['message' => 'Data saved successfully.', 'data' => $changeAmzPrice]);
    }



    public function amazonPriceIncreaseDecrease(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('amazon_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'Amazon')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.amazon_pricing_increase_decrease', [
            'mode' => $mode,
            'demo' => $demo,
            'amazonPercentage' => $percentage
        ]);
    }

    public function amazonPriceIncrease(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('amazon_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'Amazon')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.amazon_pricing_increase', [
            'mode' => $mode,
            'demo' => $demo,
            'amazonPercentage' => $percentage
        ]);
    }


    // API JUNGLE SCOUT. LINK 




    public function saveManualLink(Request $request)
    {
        $sku = $request->input('sku');
        $type = $request->input('type');
        $value = $request->input('value');

        if (!$sku || !$type) {
            return response()->json(['error' => 'SKU and type are required.'], 400);
        }

        $amazonDataView = AmazonDataView::firstOrNew(['sku' => $sku]);

        // Decode existing value array
        $existing = is_array($amazonDataView->value)
            ? $amazonDataView->value
            : (json_decode($amazonDataView->value, true) ?: []);

        $existing[$type] = $value;

        $amazonDataView->value = $existing;
        $amazonDataView->save();

        return response()->json(['message' => 'Manual link saved successfully.']);
    }

    public function saveLowProfit(Request $request)
    {
        $count = $request->input('count');
        
        $channel = ChannelMaster::where('channel', 'Amazon')->first();

        if (!$channel) {
            return response()->json(['success' => false, 'message' => 'Channel not found'], 404);
        }

        $channel->red_margin = $count;
        $channel->save();

        return response()->json(['success' => true]);
    }

    public function updateListedLive(Request $request)
    {
        $request->validate([
            'sku'   => 'required|string',
            'field' => 'required|in:Listed,Live',
            'value' => 'required|boolean' // validate as boolean
        ]);

        // Find or create the product without overwriting existing value
        $product = AmazonDataView::firstOrCreate(
            ['sku' => $request->sku],
            ['value' => []]
        );

        // Decode current value (ensure it's an array)
        $currentValue = is_array($product->value)
            ? $product->value
            : (json_decode($product->value, true) ?? []);

        // Store as actual boolean
        $currentValue[$request->field] = filter_var($request->value, FILTER_VALIDATE_BOOLEAN);

        // Save back to DB
        $product->value = $currentValue;
        $product->save();

        return response()->json(['success' => true]);
    }





}
