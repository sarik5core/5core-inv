<?php

namespace App\Http\Controllers\MarketPlace;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\EbayDataView;
use App\Models\EbayGeneralReport;
use App\Models\EbayMetric;
use App\Models\MarketplacePercentage;
use App\Models\ShopifySku;
use App\Models\ProductMaster; // Add this at the top with other use statements
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EbayController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
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

    public function getViewEbayData(Request $request)
    {
        // 1. Base ProductMaster fetch
        $productMasters = ProductMaster::orderBy("parent", "asc")
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy("sku", "asc")
            ->get();

        // 2. SKU list
        $skus = $productMasters
            ->pluck("sku")
            ->filter()
            ->unique()
            ->values()
            ->all();

        // 3. Fetch eBay Sheet data
        $response = $this->apiController->fetchEbayListingData();
        $sheetData =
            $response->getStatusCode() === 200
            ? $response->getData()->data ?? []
            : [];

        // 4. Map sheet data by SKU
        $sheetSkuMap = [];
        foreach ($sheetData as $item) {
            $sku = isset($item->{'(Child) sku'})
                ? strtoupper(trim($item->{'(Child) sku'}))
                : null;
            if ($sku) {
                $sheetSkuMap[$sku] = $item;
            }
        }

        // 5. Related Models
        $shopifyData = ShopifySku::whereIn("sku", $skus)
            ->get()
            ->keyBy("sku");
        $ebayMetrics = EbayMetric::whereIn("sku", $skus)
            ->get()
            ->keyBy("sku");
        $nrValues = EbayDataView::whereIn("sku", $skus)->pluck("value", "sku");

        $itemIdToSku = $ebayMetrics->pluck('sku', 'item_id')->toArray();

        // Step 2: Fetch relevant general reports (for these item_ids and date ranges)
        $generalReports = EbayGeneralReport::whereIn('listing_id', array_keys($itemIdToSku))
            ->whereIn('report_range', ['L60', 'L30', 'L7'])
            ->get();

        $adMetricsBySku = [];

        foreach ($generalReports as $report) {
            $sku = $itemIdToSku[$report->listing_id] ?? null;
            if (!$sku) continue;

            $range = strtoupper($report->report_range); // e.g., L30

            $adMetricsBySku[$sku][$range] = [
                'Imp' => (int) $report->impressions,
                'Clk' => (int) $report->clicks,
                'Ctr' => (float) $report->ctr,
                'Spnd' => (float) $report->ad_fees,                
                'Sls' => (int) $report->sales,
                'Acos' => $report->sales == 0 ? 0 : (float) (((float)$report->ad_fees/(int)$report->sales)*1000),
            ];
        }

        // 6. Get marketplace percentage
        $percentage =
            Cache::remember(
                "ebay_marketplace_percentage",
                now()->addDays(30),
                function () {
                    return MarketplacePercentage::where(
                        "marketplace",
                        "EBay"
                    )->value("percentage") ?? 100;
                }
            ) / 100;

        // 7. Build Result
        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);
            $parent = $pm->parent;
            $shopify = $shopifyData[$pm->sku] ?? null;
            $ebayMetric = $ebayMetrics[$pm->sku] ?? null;
            $apiItem = $sheetSkuMap[$sku] ?? null;

            $row = [];
            $row["Parent"] = $parent;
            $row["(Child) sku"] = $pm->sku;

            // From Sheet
            if ($apiItem) {
                foreach ($apiItem as $k => $v) {
                    $row[$k] = $v;
                }
            }

            // Shopify
            $row["INV"] = $shopify->inv ?? 0;
            $row["L30"] = $shopify->quantity ?? 0;

            // Metrics
            $row["eBay L30"] = $ebayMetric->ebay_l30 ?? 0;
            $row["eBay L60"] = $ebayMetric->ebay_l60 ?? 0;
            $row["eBay Price"] = $ebayMetric->ebay_price ?? 0;
            $row['eBay_item_id'] = $ebayMetric->item_id ?? null;

            $pmtData = $adMetricsBySku[$sku] ?? [];

            foreach (['L60', 'L30', 'L7', 'L1'] as $range) {
                $metrics = $pmtData[$range] ?? [];
                foreach (['Imp', 'Clk', 'Spnd', 'Sls', 'Amt', 'Ctr', 'Cps'] as $suffix) {
                    $key = "Pmt{$suffix}{$range}";
                    $row[$key] = $metrics[$suffix] ?? 0;
                }
            }

            // Values: LP & Ship
            $values = is_array($pm->Values)
                ? $pm->Values
                : (is_string($pm->Values)
                    ? json_decode($pm->Values, true)
                    : []);
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
            $ship = isset($values["ship"])
                ? floatval($values["ship"])
                : (isset($pm->ship)
                    ? floatval($pm->ship)
                    : 0);

            // Price and units for calculations
            $price = floatval($row["eBay Price"] ?? 0);
            $units_ordered_l30 = floatval($row["eBay L30"] ?? 0);

            $row["Total_pft"] = round(
                ($price * $percentage - $lp - $ship) * $units_ordered_l30,
                2
            );
            $row["T_Sale_l30"] = round($price * $units_ordered_l30, 2);
            $row["PFT_percentage"] = round(
                $price > 0
                ? (($price * $percentage - $lp - $ship) / $price) * 100
                : 0,
                2
            );
            $row["ROI_percentage"] = round(
                $lp > 0
                ? (($price * $percentage - $lp - $ship) / $lp) * 100
                : 0,
                2
            );
            $row["T_COGS"] = round($lp * $units_ordered_l30, 2);

            $row["percentage"] = $percentage;
            $row["LP_productmaster"] = $lp;
            $row["Ship_productmaster"] = $ship;

            // NR & Hide

            $row['NR'] = "REQ";
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
                    $row['NR'] = $raw['NR'] ?? null;
                    $row['SPRICE'] = $raw['SPRICE'] ?? null;
                    $row['SPFT'] = $raw['SPFT'] ?? null;
                    $row['SROI'] = $raw['SROI'] ?? null;
                    $row['Listed'] = isset($raw['Listed']) ? filter_var($raw['Listed'], FILTER_VALIDATE_BOOLEAN) : null;
                    $row['APlus'] = isset($raw['APlus']) ? filter_var($raw['APlus'], FILTER_VALIDATE_BOOLEAN) : null;
                }


            }

            // Image
            $row["image_path"] =
                $shopify->image_src ??
                ($values["image_path"] ?? ($pm->image_path ?? null));

            $result[] = (object) $row;
        }

        return response()->json([
            "message" => "eBay Data Fetched Successfully",
            "data" => $result,
            "status" => 200,
        ]);
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
            $value["NR"] = $nr === 'NR' ? 'NR' : 'REQ';
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

    return redirect()->back()->with('success', 'Data fetched successfully.');
    }

}
