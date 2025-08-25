<?php

namespace App\Http\Controllers\MarketPlace;

use App\Models\EbayMetric;
use App\Models\ShopifySku;
use App\Models\EbayDataView;
use Illuminate\Http\Request;
use App\Services\EbayApiService;
use App\Models\EbayGeneralReport;
use App\Http\Controllers\Controller;
use App\Models\MarketplacePercentage;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\ApiController;
use App\Jobs\UpdateEbaySPriceJob;
use App\Models\ChannelMaster;
use App\Models\EbayPriorityReport;
use App\Models\ProductMaster; // Add this at the top with other use statements
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EbayGeneralReports;



class EbayController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }


    public function updateEbayPricing(Request $request)
    {

        $service = new EbayApiService();

        $itemID = $request["sku"];
        $newPrice = $request["price"];

        $result = UpdateEbaySPriceJob::dispatch($itemID, $newPrice);

        // $response = $service->reviseFixedPriceItem(
        //     itemId: $itemID,
        //     price: $newPrice,
        // );

        return response()->json(['status' => 200]);
    }

    public function ebayView(Request $request)
    {
        $mode = $request->query("mode");
        $demo = $request->query("demo");

        // Get percentage from cache or database
        $percentage = Cache::remember(
            "ebay_marketplace_percentage",
            now()->addDays(30),
            function () {
                $marketplaceData = MarketplacePercentage::where(
                    "marketplace",
                    "Ebay"
                )->first();
                return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
            }
        );

        return view("market-places.ebay", [
            "mode" => $mode,
            "demo" => $demo,
            "ebayPercentage" => $percentage,
        ]);
    }

    public function ebayPricingCVR(Request $request)
    {
        $mode = $request->query("mode");
        $demo = $request->query("demo");

        // Get percentage from cache or database
        $percentage = Cache::remember(
            "ebay_marketplace_percentage",
            now()->addDays(30),
            function () {
                $marketplaceData = MarketplacePercentage::where(
                    "marketplace",
                    "Ebay"
                )->first();
                return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
            }
        );

        return view("market-places.ebay_pricing_cvr", [
            "mode" => $mode,
            "demo" => $demo,
            "ebayPercentage" => $percentage,
        ]);
    }


    public function ebayPricingIncreaseDecrease(Request $request)
    {
        $mode = $request->query("mode");
        $demo = $request->query("demo");

        // Get percentage from cache or database
        $percentage = Cache::remember(
            "ebay_marketplace_percentage",
            now()->addDays(30),
            function () {
                $marketplaceData = MarketplacePercentage::where(
                    "marketplace",
                    "Ebay"
                )->first();
                return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
            }
        );

        return view("market-places.ebay_pricing_increase_decrease", [
            "mode" => $mode,
            "demo" => $demo,
            "ebayPercentage" => $percentage,
        ]);
    }
    public function ebayPricingIncrease(Request $request)
    {
        $mode = $request->query("mode");
        $demo = $request->query("demo");

        // Get percentage from cache or database
        $percentage = Cache::remember(
            "ebay_marketplace_percentage",
            now()->addDays(30),
            function () {
                $marketplaceData = MarketplacePercentage::where(
                    "marketplace",
                    "Ebay"
                )->first();
                return $marketplaceData ? $marketplaceData->percentage : 100; 
            }
        );

        return view("market-places.ebay_pricing_increase", [
            "mode" => $mode,
            "demo" => $demo,
            "ebayPercentage" => $percentage,
        ]);
    }
    public function updateFbaStatusEbay(Request $request)
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
        DB::table('ebay_data_view')
            ->where('sku', $sku)
            ->update(['fba' => $fbaStatus]);
        $updatedData = DB::table('ebay_data_view')
            ->where('sku', $sku)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'FBA status updated successfully.',
            'data' => $updatedData
        ]);
    }

   public function getViewEbayData(Request $request)
{
    // 1. Base ProductMaster fetch
    $productMasters = ProductMaster::orderBy("parent", "asc")
        ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
        ->orderBy("sku", "asc")
        ->get();

    // 2. SKU list
    $skus = $productMasters->pluck("sku")
        ->filter()
        ->unique()
        ->values()
        ->all();

    // 3. Related Models
    $shopifyData = ShopifySku::whereIn("sku", $skus)
        ->get()
        ->keyBy("sku");

    $ebayMetrics = EbayMetric::whereIn("sku", $skus)
        ->get()
        ->keyBy("sku");

    $nrValues = EbayDataView::whereIn("sku", $skus)->pluck("value", "sku");

    // Mapping arrays
    $itemIdToSku = $ebayMetrics->pluck('sku', 'item_id')->toArray();
    $campaignIdToSku = $ebayMetrics->pluck('sku', 'campaign_id')->toArray();

    // ✅ Fetch L30 Clicks from ebay_general_reports (listing_id → clicks)
    $extraClicksData = EbayGeneralReport::whereIn('listing_id', array_keys($itemIdToSku))
        ->where('report_range', 'L30')
        ->pluck('clicks', 'listing_id')
        ->toArray();

    // 4a. Fetch General Reports (listing_id → sku)
    $generalReports = EbayGeneralReport::whereIn('listing_id', array_keys($itemIdToSku))
        ->whereIn('report_range', ['L60', 'L30', 'L7'])
        ->get();

    // 4b. Fetch Priority Reports (campaign_id → sku)
    $priorityReports = EbayPriorityReport::whereIn('campaign_id', array_keys($campaignIdToSku))
        ->whereIn('report_range', ['L60', 'L30', 'L7'])
        ->get();

    $adMetricsBySku = [];

    // General Reports
    foreach ($generalReports as $report) {
        $sku = $itemIdToSku[$report->listing_id] ?? null;
        if (!$sku) continue;

        $range = strtoupper($report->report_range);

        $adMetricsBySku[$sku][$range]['GENERAL_SPENT'] =
            ($adMetricsBySku[$sku][$range]['GENERAL_SPENT'] ?? 0) + $this->extractNumber($report->ad_fees);

        $adMetricsBySku[$sku][$range]['Imp'] =
            ($adMetricsBySku[$sku][$range]['Imp'] ?? 0) + (int) $report->impressions;

        $adMetricsBySku[$sku][$range]['Clk'] =
            ($adMetricsBySku[$sku][$range]['Clk'] ?? 0) + (int) $report->clicks;

        $adMetricsBySku[$sku][$range]['Ctr'] =
            ($adMetricsBySku[$sku][$range]['Ctr'] ?? 0) + (float) $report->ctr;

        $adMetricsBySku[$sku][$range]['Sls'] =
            ($adMetricsBySku[$sku][$range]['Sls'] ?? 0) + (int) $report->sales;
    }

    // Priority Reports
    foreach ($priorityReports as $report) {
        $sku = $campaignIdToSku[$report->campaign_id] ?? null;
        if (!$sku) continue;

        $range = strtoupper($report->report_range);

        $adMetricsBySku[$sku][$range]['PRIORITY_SPENT'] =
            ($adMetricsBySku[$sku][$range]['PRIORITY_SPENT'] ?? 0) + $this->extractNumber($report->cpc_ad_fees_payout_currency);
    }

    // 5. Marketplace percentage
    $percentage = Cache::remember(
        "ebay_marketplace_percentage",
        now()->addDays(30),
        function () {
            return MarketplacePercentage::where("marketplace", "EBay")->value("percentage") ?? 100;
        }
    ) / 100;

    // 6. Build Result
    $result = [];

    foreach ($productMasters as $pm) {
        $sku = strtoupper($pm->sku);
        $parent = $pm->parent;

        $shopify = $shopifyData[$pm->sku] ?? null;
        $ebayMetric = $ebayMetrics[$pm->sku] ?? null;

        $row = [];
        $row["Parent"] = $parent;
        $row["(Child) sku"] = $pm->sku;
        $row['fba'] = $pm->fba;

        // Shopify
        $row["INV"] = $shopify->inv ?? 0;
        $row["L30"] = $shopify->quantity ?? 0;

        // eBay Metrics
        $row["eBay L30"] = $ebayMetric->ebay_l30 ?? 0;
        $row["eBay L60"] = $ebayMetric->ebay_l60 ?? 0;
        $row["eBay Price"] = $ebayMetric->ebay_price ?? 0;
        $row['eBay_item_id'] = $ebayMetric->item_id ?? null;

        $row["E Dil%"] = ($row["eBay L30"] && $row["INV"] > 0)
            ? round(($row["eBay L30"] / $row["INV"]), 2)
            : 0;

        // Ad Metrics
        $pmtData = $adMetricsBySku[$sku] ?? [];
        foreach (['L60', 'L30', 'L7'] as $range) {
            $metrics = $pmtData[$range] ?? [];
            foreach (['Imp', 'Clk', 'Ctr', 'Sls', 'GENERAL_SPENT', 'PRIORITY_SPENT'] as $suffix) {
                $key = "Pmt{$suffix}{$range}";
                $row[$key] = $metrics[$suffix] ?? 0;
            }
        }

        // ✅ Merge Extra Clicks (L30 only)
        if ($ebayMetric && isset($extraClicksData[$ebayMetric->item_id])) {
            $row["PmtClkL30"] += (int) $extraClicksData[$ebayMetric->item_id];
        }

        // Values: LP & Ship
        $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);
        $lp = 0;
        foreach ($values as $k => $v) {
            if (strtolower($k) === "lp") {
                $lp = floatval($v);
                break;
            }
        }
        if ($lp === 0 && isset($pm->lp)) {
            $lp = floatval($pm->lp);
        }

        $ship = isset($values["ship"]) ? floatval($values["ship"]) : (isset($pm->ship) ? floatval($pm->ship) : 0);

        // Price and units for calculations
        $price = floatval($row["eBay Price"] ?? 0);
        $units_ordered_l30 = floatval($row["eBay L30"] ?? 0);

        // Set PmtClkL30 from adMetrics data
        $row["PmtClkL30"] = $adMetricsBySku[$sku]['L30']['Clk'] ?? 0;
        
        // Add extra clicks from general reports if available
        if ($ebayMetric && isset($extraClicksData[$ebayMetric->item_id])) {
            $row["PmtClkL30"] += (int) $extraClicksData[$ebayMetric->item_id];
        }
        
        // Log for debugging
        \Illuminate\Support\Facades\Log::info("PmtClkL30 for SKU {$sku}: " . $row["PmtClkL30"]);

        // New Tacos Formula
        $generalSpent = $adMetricsBySku[$sku]['L30']['GENERAL_SPENT'] ?? 0;
        $prioritySpent = $adMetricsBySku[$sku]['L30']['PRIORITY_SPENT'] ?? 0;
        $denominator = ($price * $units_ordered_l30);
        $row["TacosL30"] = $denominator > 0 ? round((($generalSpent + $prioritySpent) / $denominator), 4) : 0;

        // Profit/Sales
        $row["Total_pft"] = round(($price * $percentage - $lp - $ship) * $units_ordered_l30, 2);
        $row["T_Sale_l30"] = round($price * $units_ordered_l30, 2);
        $row["PFT %"] = round(
            $price > 0 ? (($price * $percentage - $lp - $ship) / $price) : 0,
            2
        );
        $row["ROI%"] = round(
            $lp > 0 ? (($price * $percentage - $lp - $ship) / $lp) : 0,
            2
        );
        $row["percentage"] = $percentage;
        $row["LP_productmaster"] = $lp;
        $row["Ship_productmaster"] = $ship;

        // NR & Hide
        $row['NR'] = "";
        $row['SPRICE'] = null;
        $row['SPFT'] = null;
        $row['SROI'] = null;
        $row['Listed'] = null;
        $row['Live'] = null;
        $row['APlus'] = null;
        if (isset($nrValues[$pm->sku])) {
            $raw = $nrValues[$pm->sku];
            if (!is_array($raw)) {
                $raw = json_decode($raw, true);
            }
            if (is_array($raw)) {
                $row['NR'] = $raw['NR'] ?? null;
                $row['SPRICE'] = $raw['SPRICE'] ?? null;
                $row['SPFT'] = $raw['SPFT'] ?? null;
                $row['SROI'] = $raw['SROI'] ?? null;
                $row['Listed'] = isset($raw['Listed']) ? filter_var($raw['Listed'], FILTER_VALIDATE_BOOLEAN) : null;
                $row['Live'] = isset($raw['Live']) ? filter_var($raw['Live'], FILTER_VALIDATE_BOOLEAN) : null;
                $row['APlus'] = isset($raw['APlus']) ? filter_var($raw['APlus'], FILTER_VALIDATE_BOOLEAN) : null;
            }
        }

        // Image
        $row["image_path"] = $shopify->image_src ?? ($values["image_path"] ?? ($pm->image_path ?? null));

        $result[] = (object) $row;
    }

    return response()->json([
        "message" => "eBay Data Fetched Successfully",
        "data" => $result,
        "status" => 200,
    ]);
}


    // Helper function
    private function extractNumber($value)
    {
        if (empty($value)) return 0;
        return (float) preg_replace('/[^\d.]/', '', $value);
    }


    public function updateAllEbaySkus(Request $request)
    {
        try {
            $percent = $request->input("percent");

            if (!is_numeric($percent) || $percent < 0 || $percent > 100) {
                return response()->json(
                    [
                        "status" => 400,
                        "message" =>
                        "Invalid percentage value. Must be between 0 and 100.",
                    ],
                    400
                );
            }

            // Update database
            MarketplacePercentage::updateOrCreate(
                ["marketplace" => "Ebay"],
                ["percentage" => $percent]
            );

            // Store in cache
            Cache::put(
                "ebay_marketplace_percentage",
                $percent,
                now()->addDays(30)
            );

            return response()->json([
                "status" => 200,
                "message" => "Percentage updated successfully",
                "data" => [
                    "marketplace" => "Ebay",
                    "percentage" => $percent,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "status" => 500,
                    "message" => "Error updating percentage",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    // Save NR value for a SKU
    public function saveNrToDatabase(Request $request)
    {
        $skus = $request->input("skus");
        $hideValues = $request->input("hideValues"); // <-- add this
        $sku = $request->input("sku");
        $nr = $request->input("nr");
        $hide = $request->input("hide");

        // Decode hideValues if it's a JSON string
        if (is_string($hideValues)) {
            $hideValues = json_decode($hideValues, true);
        }

        // Bulk update with individual hide values
        if (is_array($skus) && is_array($hideValues)) {
            foreach ($skus as $skuItem) {
                $ebayDataView = EbayDataView::firstOrNew(["sku" => $skuItem]);
                $value = is_array($ebayDataView->value)
                    ? $ebayDataView->value
                    : (json_decode($ebayDataView->value, true) ?:
                        []);
                // Use the value from hideValues for each SKU
                $value["Hide"] = filter_var(
                    $hideValues[$skuItem] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
                $ebayDataView->value = $value;
                $ebayDataView->save();
            }
            return response()->json([
                "success" => true,
                "updated" => count($skus),
            ]);
        }

        // Bulk update if 'skus' is present and 'hide' is a single value (legacy)
        if (is_array($skus) && $hide !== null) {
            foreach ($skus as $skuItem) {
                $ebayDataView = EbayDataView::firstOrNew(["sku" => $skuItem]);
                $value = is_array($ebayDataView->value)
                    ? $ebayDataView->value
                    : (json_decode($ebayDataView->value, true) ?:
                        []);
                $value["Hide"] = filter_var($hide, FILTER_VALIDATE_BOOLEAN);
                $ebayDataView->value = $value;
                $ebayDataView->save();
            }
            return response()->json([
                "success" => true,
                "updated" => count($skus),
            ]);
        }

        // Single update (existing logic)
        if (!$sku || ($nr === null && $hide === null)) {
            return response()->json(
                ["error" => "SKU and at least one of NR or Hide is required."],
                400
            );
        }

        $ebayDataView = EbayDataView::firstOrNew(["sku" => $sku]);
        $value = is_array($ebayDataView->value)
            ? $ebayDataView->value
            : (json_decode($ebayDataView->value, true) ?:
                []);

        if ($nr !== null) {
            $value["NR"] = $nr;
        }

        $ebayDataView->value = $value;
        $ebayDataView->save();

        return response()->json(["success" => true, "data" => $ebayDataView]);
    }


    public function saveSpriceToDatabase(Request $request)
    {
        // LOG::info('Saving Shopify pricing data', $request->all());
        $sku = $request->input('sku');
        $spriceData = $request->only(['sprice', 'spft_percent', 'sroi_percent']);

        if (!$sku || !$spriceData['sprice']) {
            return response()->json(['error' => 'SKU and sprice are required.'], 400);
        }


        $ebayDataView = EbayDataView::firstOrNew(['sku' => $sku]);

        // Decode value column safely
        $existing = is_array($ebayDataView->value)
            ? $ebayDataView->value
            : (json_decode($ebayDataView->value, true) ?: []);

        // Merge new sprice data
        $merged = array_merge($existing, [
            'SPRICE' => $spriceData['sprice'],
            'SPFT' => $spriceData['spft_percent'],
            'SROI' => $spriceData['sroi_percent'],
        ]);

        $ebayDataView->value = $merged;
        $ebayDataView->save();

        return response()->json(['message' => 'Data saved successfully.']);
    }



    public function updateListedLive(Request $request)
    {
        $request->validate([
            'sku'   => 'required|string',
            'field' => 'required|in:Listed,Live',
            'value' => 'required|boolean' // validate as boolean
        ]);

        // Find or create the product without overwriting existing value
        $product = EbayDataView::firstOrCreate(
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

    public function saveLowProfit(Request $request)
    {
        $count = $request->input('count');

        $channel = ChannelMaster::where('channel', 'eBay')->first();

        if (!$channel) {
            return response()->json(['success' => false, 'message' => 'Channel not found'], 404);
        }

        $channel->red_margin = $count;
        $channel->save();

        return response()->json(['success' => true]);
    }
}
