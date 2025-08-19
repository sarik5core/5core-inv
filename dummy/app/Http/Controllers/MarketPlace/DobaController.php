<?php

namespace App\Http\Controllers\MarketPlace;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\DobaDataView;
use App\Models\DobaMetric;
use App\Models\MarketplacePercentage;
use App\Models\ShopifySku;
use App\Models\ProductMaster; // Add this at the top with other use statements
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Ensure you import Log for logging

class dobaController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    public function dobaView(Request $request)
    {
        $mode = $request->query("mode");
        $demo = $request->query("demo");

        // Get percentage from cache or database
        $percentage = Cache::remember(
            "doba_marketplace_percentage",
            now()->addDays(30),
            function () {
                $marketplaceData = MarketplacePercentage::where(
                    "marketplace",
                    "Doba"
                )->first();
                return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
            }
        );

        return view("market-places.doba-analytics", [
            "mode" => $mode,
            "demo" => $demo,
            "dobaPercentage" => $percentage,
        ]);
    }



    public function dobaPricingCVR(Request $request)
    {
        $mode = $request->query("mode");
        $demo = $request->query("demo");

        // Get percentage from cache or database
        $percentage = Cache::remember(
            "doba_marketplace_percentage",
            now()->addDays(30),
            function () {
                $marketplaceData = MarketplacePercentage::where(
                    "marketplace",
                    "Doba"
                )->first();
                return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
            }
        );

        return view("market-places.doba_pricing_cvr", [
            "mode" => $mode,
            "demo" => $demo,
            "dobaPercentage" => $percentage,
        ]);
    }

    public function getViewdobaData(Request $request)
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

        // 3. Fetch doba Sheet data
        $response = $this->apiController->fetchdobaListingData();
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
        $dobaMetrics = dobaMetric::whereIn("sku", $skus)
            ->get()
            ->keyBy("sku");
        $nrValues = DobaDataView::whereIn("sku", $skus)->pluck("value", "sku");

        // 6. Get marketplace percentage
        $percentage =
            Cache::remember(
                "doba_marketplace_percentage",
                now()->addDays(30),
                function () {
                    return MarketplacePercentage::where(
                        "marketplace",
                        "Doba"
                    )->value("percentage") ?? 100;
                }
            ) / 100;

        // 7. Build Result
        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);
            $parent = $pm->parent;
            $shopify = $shopifyData[$pm->sku] ?? null;
            $dobaMetric = $dobaMetrics[$pm->sku] ?? null;
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

            //Doba Metrics
            $row["doba L30"] = $dobaMetric->quantity_l30 ?? 0;
            $row["doba L60"] = $dobaMetric->quantity_l60 ?? 0;
            $row["doba Price"] = $dobaMetric->anticipated_income ?? 0;
            $row['doba_item_id'] = $dobaMetric->item_id ?? null;

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
            $price = floatval($row["doba Price"] ?? 0);
            $units_ordered_l30 = floatval($row["doba L30"] ?? 0);

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

            $row['NR'] = 'REQ';
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

            // Image
            $row["image_path"] =
                $shopify->image_src ??
                ($values["image_path"] ?? ($pm->image_path ?? null));

            $result[] = (object) $row;
        }

        return response()->json([
            "message" => "doba Data Fetched Successfully",
            "data" => $result,
            "status" => 200,
        ]);
    }

    public function updateAlldobaSkus(Request $request)
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
                ["marketplace" => "Doba"],
                ["percentage" => $percent]
            );

            // Store in cache
            Cache::put(
                "doba_marketplace_percentage",
                $percent,
                now()->addDays(30)
            );

            return response()->json([
                "status" => 200,
                "message" => "Percentage updated successfully",
                "data" => [
                    "marketplace" => "Doba",
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
    $sku = $request->input('sku');
    $nrInput = $request->input('nr'); // This could be string or JSON string

    if (!$sku || !$nrInput) {
        return response()->json(['error' => 'SKU and NR are required.'], 400);
    }

    // Normalize NR Input
    $nrValue = null;

    // If NR is a JSON string, decode it
    if (is_string($nrInput)) {
        $decoded = json_decode($nrInput, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['NR'])) {
            $nrValue = $decoded['NR'];
        } else {
            $nrValue = $nrInput;
        }
    } elseif (is_array($nrInput) && isset($nrInput['NR'])) {
        $nrValue = $nrInput['NR'];
    }

    // Safety check for NR value
    if ($nrValue !== 'NR' && $nrValue !== 'REQ') {
        $nrValue = 'REQ'; // Default to REQ
    }

    // Fetch or create the record
    $dobaDataView = DobaDataView::firstOrNew(['sku' => $sku]);

    // Decode existing JSON value
    $existing = is_array($dobaDataView->value)
        ? $dobaDataView->value
        : (json_decode($dobaDataView->value, true) ?: []);

    // Update NR in existing data
    $existing['NR'] = $nrValue;

    // Save merged data
    $dobaDataView->value = $existing;
    $dobaDataView->save();

    return response()->json(['success' => true, 'data' => $dobaDataView]);
}


    public function saveSpriceToDatabase(Request $request)
    {
        $sku = $request->input('sku');
        $spriceData = $request->only(['sprice', 'spft_percent', 'sroi_percent']);

        if (!$sku || !$spriceData['sprice']) {
            return response()->json(['error' => 'SKU and sprice are required.'], 400);
        }

        
        $dobaDataView = DobaDataView::firstOrNew(['sku' => $sku]);

        // Decode value column safely
        $existing = is_array($dobaDataView->value)
            ? $dobaDataView->value
            : (json_decode($dobaDataView->value, true) ?: []);

        // Merge new sprice data
        $merged = array_merge($existing, [
            'SPRICE' => $spriceData['sprice'],
            'SPFT' => $spriceData['spft_percent'],
            'SROI' => $spriceData['sroi_percent'],
        ]);

        $dobaDataView->value = $merged;
        $dobaDataView->save();

        return redirect()->back()->with('success', 'Data fetched successfully.');
    }



}
