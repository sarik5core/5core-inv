<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Models\AccountHealthMaster;
use App\Models\AmazonDatasheet;
use App\Models\AtoZClaimsRate;
use App\Models\BestbuyUsaProduct;
use App\Models\ChannelMaster;
use App\Models\DobaMetric;
use App\Models\Ebay2Metric;
use App\Models\Ebay3Metric;
use App\Models\EbayMetric;
use App\Models\FullfillmentRate;
use App\Models\LateShipmentRate;
use App\Models\MacyProduct;
use App\Models\MarketplacePercentage;
use App\Models\NegativeSellerRate;
use App\Models\OdrRate;
use App\Models\OnTimeDeliveryRate;
use App\Models\PLSProduct;
use App\Models\ProductMaster;
use App\Models\RefundRate;
use App\Models\ReverbProduct;
use App\Models\TemuProductSheet;
use App\Models\TiendamiaProduct;
use App\Models\ValidTrackingRate;
use App\Models\VoilanceRate;
use App\Models\WaifairProductSheet;
use App\Models\WalmartMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AccountHealthMasterDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        return view('channels.account_health_master.dashboard', [
            'mode' => $mode,
            'demo' => $demo,
        ]);
    }

    public function getMasterChannelDataHealthDashboard(Request $request)
    {
        // Get channel filter if provided
        $channelFilter = $request->get('channel');

        $channelsQuery = ChannelMaster::where('status', 'Active')->orderBy('id', 'asc');

        // Apply channel filter if provided
        if ($channelFilter) {
            $channelsQuery->where('channel', 'LIKE', '%' . $channelFilter . '%');
        }

        $channels = $channelsQuery->get(['id', 'channel', 'sheet_link']);

        if ($channels->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'No active channel found']);
        }

        $finalData = [];

        $controllerMap = [
            'amazon' => 'getAmazonChannelData',
            'ebay' => 'getEbayChannelData',
            'ebaytwo' => 'getEbaytwoChannelData',
            'ebaythree' => 'getEbaythreeChannelData',
            'macys' => 'getMacysChannelData',
            'tiendamia' => 'getTiendamiaChannelData',
            'bestbuyusa' => 'getBestbuyUsaChannelData',
            'reverb' => 'getReverbChannelData',
            'doba' => 'getDobaChannelData',
            'temu' => 'getTemuChannelData',
            'walmart' => 'getWalmartChannelData',
            'pls' => 'getPlsChannelData',
            'wayfair' => 'getWayfairChannelData',
            // Add other 40 channels here
        ];

        // Get channel IDs for batch queries
        $channelIds = $channels->pluck('id')->toArray();

        // Batch fetch all rate data for filtered channels only
        $rateModels = [
            'ODR' => OdrRate::class,
            'Fulfillment Rate' => FullfillmentRate::class,
            'Valid Tracking Rate' => ValidTrackingRate::class,
            'Late Shipment Rate' => LateShipmentRate::class,
            'On Time Delivery Rate' => OnTimeDeliveryRate::class,
            'Negative Seller Rate' => NegativeSellerRate::class,
            'AtoZ Claims Rate' => AtoZClaimsRate::class,
            'Voilation Rate' => VoilanceRate::class,
            'Refund Rate' => RefundRate::class,
        ];

        $ratesData = [];
        foreach ($rateModels as $rateKey => $model) {
            // Only fetch data for the filtered channels
            $rates = $model::whereIn('channel_id', $channelIds)
                ->orderBy('report_date', 'desc')
                ->get()
                ->keyBy('channel_id');
            $ratesData[$rateKey] = $rates;
        }

        foreach ($channels as $channelRow) {
            $channel = $channelRow->channel;
            $channelId = $channelRow->id;
            $nowDate = now()->toDateString();

            $row = [
                'Channel ' => ucfirst($channel),
                'Link' => null,
                'sheet_link' => $channelRow->sheet_link,
                'L-60 Sales' => 0,
                'L30 Sales' => 0,
                'Growth' => 0,
                'L60 Orders' => 0,
                'L30 Orders' => 0,
                'Gprofit%' => 'N/A',
                'gprofitL60' => 'N/A',
                'G ROI%' => 'N/A',
                'G RoiL60' => 'N/A',
                'red_margin' => 0,
                'NR' => 0,
                'type' => '',
                'listed_count' => 0,
                'W/Ads' => 0,
                'Update' => 0,
                'Account health' => null,
            ];

            // Get channel-specific data
            $key = strtolower(str_replace([' ', '-', '&', '/'], '', trim($channel)));
            if (isset($controllerMap[$key]) && method_exists($this, $controllerMap[$key])) {
                try {
                    $method = $controllerMap[$key];
                    $data = $this->$method($request)->getData(true);
                    if (!empty($data['data'])) {
                        $channelData = $data['data'][0];
                        $row['L-60 Sales'] = $channelData['L-60 Sales'] ?? 0;
                        $row['L30 Sales'] = $channelData['L30 Sales'] ?? 0;
                        $row['Growth'] = $channelData['Growth'] ?? 0;
                        $row['L60 Orders'] = $channelData['L60 Orders'] ?? 0;
                        $row['L30 Orders'] = $channelData['L30 Orders'] ?? 0;
                        $row['Gprofit%'] = $channelData['Gprofit%'] ?? 'N/A';
                        $row['gprofitL60'] = $channelData['gprofitL60'] ?? 'N/A';
                        $row['G ROI%'] = $channelData['G ROI%'] ?? 'N/A';
                        $row['G RoiL60'] = $channelData['G RoiL60'] ?? 'N/A';
                        $row['red_margin'] = $channelData['red_margin'] ?? 0;
                        $row['NR'] = $channelData['NR'] ?? 0;
                        $row['type'] = $channelData['type'] ?? '';
                        $row['listed_count'] = $channelData['listed_count'] ?? 0;
                        $row['W/Ads'] = $channelData['W/Ads'] ?? 0;
                        $row['Update'] = $channelData['Update'] ?? 0;
                        $row['Account health'] = $channelData['Account health'] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching data for channel {$channel}: " . $e->getMessage());
                    // Continue with default values
                }
            }

            // Fetch rate data efficiently
            foreach ($rateModels as $rateKey => $model) {
                $rate = $ratesData[$rateKey][$channelId] ?? null;
                if ($rate) {
                    $row[$rateKey] = $rate->current ?? 'N/A';
                } else {
                    // Create default record only if it doesn't exist
                    try {
                        $existingRate = $model::where('channel_id', $channelId)->first();
                        if (!$existingRate) {
                            $model::create([
                                'channel_id' => $channelId,
                                'report_date' => $nowDate,
                                'current' => 'N/A',
                                'allowed' => '',
                                'what' => '',
                                'why' => '',
                                'action' => '',
                                'c_action' => '',
                                'account_health_links' => '',
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error creating default rate for channel {$channel}, rate {$rateKey}: " . $e->getMessage());
                    }
                    $row[$rateKey] = 'N/A';
                }
            }

            $finalData[] = $row;
        }

        return response()->json([
            'status' => 200,
            'message' => 'Channel data fetched successfully',
            'data' => $finalData,
        ]);
    }

    public function getAmazonChannelData(Request $request)
    {
        $result = [];

        $query = AmazonDatasheet::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('units_ordered_l30');
        $l60Orders = $query->sum('units_ordered_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(units_ordered_l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(units_ordered_l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get Amazon marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Amazon')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate GProfit
        $amazonRows   = $query->get(['sku', 'price', 'units_ordered_l30', 'units_ordered_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($amazonRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->units_ordered_l30;
            $unitsL60  = (int) $row->units_ordered_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP from JSON/column ---
        $amazonSkus   = $amazonRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $amazonPMs    = ProductMaster::whereIn('sku', $amazonSkus)->get();

        $totalLpValue = 0;
        foreach ($amazonPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Amazon')->first();

        $result[] = [
            'Channel '   => 'Amazon',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Amazon channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getEbayChannelData(Request $request)
    {
        $result = [];

        $query = EbayMetric::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('ebay_l30');
        $l60Orders = $query->sum('ebay_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(ebay_l30 * ebay_price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(ebay_l60 * ebay_price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Ebay')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'ebay_price', 'ebay_l30', 'ebay_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->ebay_price;
            $unitsL30  = (int) $row->ebay_l30;
            $unitsL60  = (int) $row->ebay_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60       = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'eBay')->first();

        $result[] = [
            'Channel '   => 'eBay',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'eBay channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getEbaytwoChannelData(Request $request)
    {
        $result = [];

        $query = Ebay2Metric::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('ebay_l30');
        $l60Orders = $query->sum('ebay_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(ebay_l30 * ebay_price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(ebay_l60 * ebay_price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'EbayTwo')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'ebay_price', 'ebay_l30', 'ebay_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->ebay_price;
            $unitsL30  = (int) $row->ebay_l30;
            $unitsL60  = (int) $row->ebay_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'EbayTwo')->first();

        $result[] = [
            'Channel '   => 'EbayTwo',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'eBay2 channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getEbaythreeChannelData(Request $request)
    {
        $result = [];

        $query = Ebay3Metric::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('ebay_l30');
        $l60Orders = $query->sum('ebay_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(ebay_l30 * ebay_price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(ebay_l60 * ebay_price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'EbayThree')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'ebay_price', 'ebay_l30', 'ebay_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->ebay_price;
            $unitsL30  = (int) $row->ebay_l30;
            $unitsL60  = (int) $row->ebay_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'EbayThree')->first();

        $result[] = [
            'Channel '   => 'EbayThree',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60' => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'   => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'eBay three channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getMacysChannelData(Request $request)
    {
        $result = [];

        $query = MacyProduct::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('m_l30');
        $l60Orders = $query->sum('m_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(m_l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(m_l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Macys')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'm_l30', 'm_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->m_l30;
            $unitsL60  = (int) $row->m_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60       = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Macys')->first();

        $result[] = [
            'Channel '   => 'Macys',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60' => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'   => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Macys channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getTiendamiaChannelData(Request $request)
    {
        $result = [];

        $query = TiendamiaProduct::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('m_l30');
        $l60Orders = $query->sum('m_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(m_l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(m_l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Tiendamia')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'm_l30', 'm_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->m_l30;
            $unitsL60  = (int) $row->m_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Tiendamia')->first();

        $result[] = [
            'Channel '   => 'Tiendamia',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Tiendamia channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getBestbuyUsaChannelData(Request $request)
    {
        $result = [];

        $query = BestbuyUsaProduct::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('m_l30');
        $l60Orders = $query->sum('m_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(m_l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(m_l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'BestbuyUSA')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'm_l30', 'm_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->m_l30;
            $unitsL60  = (int) $row->m_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'BestBuy USA')->first();

        $result[] = [
            'Channel '   => 'BestBuy USA',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Bestbuy USA channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getReverbChannelData(Request $request)
    {
        $result = [];

        $query = ReverbProduct::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('r_l30');
        $l60Orders = $query->sum('r_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(r_l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(r_l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Reverb')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'r_l30', 'r_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->r_l30;
            $unitsL60  = (int) $row->r_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Reverb')->first();

        $result[] = [
            'Channel '   => 'Reverb',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Reverb channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getDobaChannelData(Request $request)
    {
        $result = [];

        $query = DobaMetric::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('quantity_l30');
        $l60Orders = $query->sum('quantity_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(quantity_l30 * anticipated_income) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(quantity_l60 * anticipated_income) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Doba')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'anticipated_income', 'quantity_l30', 'quantity_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->anticipated_income;
            $unitsL30  = (int) $row->quantity_l30;
            $unitsL60  = (int) $row->quantity_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Doba')->first();

        $result[] = [
            'Channel '   => 'Doba',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Doba channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getTemuChannelData(Request $request)
    {
        $result = [];

        $query = TemuProductSheet::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('l30');
        $l60Orders = $query->sum('l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Temu')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'l30', 'l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->l30;
            $unitsL60  = (int) $row->l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60       = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Temu')->first();

        $result[] = [
            'Channel '   => 'Temu',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Doba channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getWalmartChannelData(Request $request)
    {
        $result = [];

        $query = WalmartMetrics::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('l30');
        $l60Orders = $query->sum('l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get Walmart marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Walmart')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'l30', 'l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->l30;
            $unitsL60  = (int) $row->l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Walmart')->first();

        $result[] = [
            'Channel '   => 'Walmart',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Walmart channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getPlsChannelData(Request $request)
    {
        $result = [];

        $query = PLSProduct::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('p_l30');
        $l60Orders = $query->sum('p_l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(p_l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(p_l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Pls')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'p_l30', 'p_l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->p_l30;
            $unitsL60  = (int) $row->p_l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'PLS')->first();

        $result[] = [
            'Channel '   => 'PLS',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60'   => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'      => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'PLS channel data fetched successfully',
            'data' => $result,
        ]);
    }

    public function getWayfairChannelData(Request $request)
    {
        $result = [];

        $query = WaifairProductSheet::where('sku', 'not like', '%Parent%');

        $l30Orders = $query->sum('l30');
        $l60Orders = $query->sum('l60');

        $l30Sales  = (clone $query)->selectRaw('SUM(l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(l60 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Wayfair')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'l30', 'l60']);
        $totalProfit  = 0;
        $totalProfitL60  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->l30;
            $unitsL60  = (int) $row->l60;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;
            $profitTotalL60   = $profitPerUnit * $unitsL60;

            $totalProfit += $profitTotal;
            $totalProfitL60 += $profitTotalL60;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values : (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gprofitL60 = $l60Sales > 0 ? ($totalProfitL60 / $l60Sales) * 100 : 0;

        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;
        $gRoiL60    = $totalLpValue > 0 ? ($totalProfitL60 / $totalLpValue) : 0;

        // Channel data
        $channelData = ChannelMaster::where('channel', 'Wayfair')->first();

        $result[] = [
            'Channel '   => 'Wayfair',
            'L-60 Sales' => intval($l60Sales),
            'L30 Sales'  => intval($l30Sales),
            'Growth'     => round($growth, 2) . '%',
            'L60 Orders' => $l60Orders,
            'L30 Orders' => $l30Orders,
            'Gprofit%'   => round($gProfitPct, 2) . '%',
            'gprofitL60' => round($gprofitL60, 2) . '%',
            'G Roi'      => round($gRoi, 2),
            'G RoiL60'   => round($gRoiL60, 2),
            'type'       => $channelData->type ?? '',
            'W/Ads'      => $channelData->w_ads ?? 0,
            'NR'         => $channelData->nr ?? 0,
            'Update'     => $channelData->update ?? 0,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'wayfair channel data fetched successfully',
            'data' => $result,
        ]);
    }

    /**
     * Export account health data to Excel
     */
    public function export(Request $request)
    {
        try {
            // Get all channel data
            $response = $this->getMasterChannelDataHealthDashboard($request);
            $data = $response->getData(true);

            if ($data['status'] !== 200) {
                return redirect()->back()->with('error', 'Failed to fetch data for export');
            }

            $channelData = $data['data'];

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Account Health Data');

            // Headers
            $headers = [
                'A1' => 'Channel',
                'B1' => 'L30 Sales',
                'C1' => 'L30 Orders',
                'D1' => 'L60 Orders',
                'E1' => 'Growth %',
                'F1' => 'Gross Profit %',
                'G1' => 'G ROI %',
                'H1' => 'NR',
                'I1' => 'Type',
                'J1' => 'Listed Count',
                'K1' => 'ODR Rate',
                'L1' => 'Fulfillment Rate',
                'M1' => 'Valid Tracking Rate',
                'N1' => 'On Time Delivery Rate',
                'O1' => 'AtoZ Claims Rate',
                'P1' => 'Violation Rate',
                'Q1' => 'Refund Rate',
                'R1' => 'Sheet Link',
                'S1' => 'Account Health Links'
            ];

            // Set headers
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style headers
            $sheet->getStyle('A1:S1')->getFont()->setBold(true);
            $sheet->getStyle('A1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A1:S1')->getFill()->getStartColor()->setARGB('CCCCCC');

            // Fill data
            $row = 2;
            foreach ($channelData as $channel) {
                $sheet->setCellValue('A' . $row, $channel['Channel '] ?? '');
                $sheet->setCellValue('B' . $row, $channel['L30 Sales'] ?? 0);
                $sheet->setCellValue('C' . $row, $channel['L30 Orders'] ?? 0);
                $sheet->setCellValue('D' . $row, $channel['L60 Orders'] ?? 0);
                $sheet->setCellValue('E' . $row, $channel['Growth'] ?? 0);
                $sheet->setCellValue('F' . $row, $channel['Gprofit%'] ?? 'N/A');
                $sheet->setCellValue('G' . $row, $channel['G ROI%'] ?? 'N/A');
                $sheet->setCellValue('H' . $row, $channel['NR'] ?? 0);
                $sheet->setCellValue('I' . $row, $channel['type'] ?? '');
                $sheet->setCellValue('J' . $row, $channel['listed_count'] ?? 0);
                $sheet->setCellValue('K' . $row, $channel['ODR'] ?? 'N/A');
                $sheet->setCellValue('L' . $row, $channel['Fulfillment Rate'] ?? 'N/A');
                $sheet->setCellValue('M' . $row, $channel['Valid Tracking Rate'] ?? 'N/A');
                $sheet->setCellValue('N' . $row, $channel['On Time Delivery Rate'] ?? 'N/A');
                $sheet->setCellValue('O' . $row, $channel['AtoZ Claims Rate'] ?? 'N/A');
                $sheet->setCellValue('P' . $row, $channel['Voilation Rate'] ?? 'N/A');
                $sheet->setCellValue('Q' . $row, $channel['Refund Rate'] ?? 'N/A');
                $sheet->setCellValue('R' . $row, $channel['sheet_link'] ?? '');
                $sheet->setCellValue('S' . $row, $channel['Account health'] ?? '');
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'S') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Generate filename
            $filename = 'account_health_master_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Save and download
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'account_health');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Import account health data from Excel
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'import_type' => 'required|in:channel_data,health_rates,both',
            'update_mode' => 'required|in:update,create,replace'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('excel_file');
            $importType = $request->input('import_type');
            $updateMode = $request->input('update_mode');

            // Read Excel file
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            // Remove header row
            $headers = array_shift($data);

            // Process data based on import type
            $results = [];

            if ($importType === 'channel_data' || $importType === 'both') {
                $results = array_merge($results, $this->importChannelData($data, $headers, $updateMode));
            }

            if ($importType === 'health_rates' || $importType === 'both') {
                $results = array_merge($results, $this->importHealthRates($data, $headers, $updateMode));
            }

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import channel performance data
     */
    private function importChannelData($data, $headers, $updateMode)
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($data as $row) {
            if (empty($row[0])) continue; 

            $channelName = trim($row[0]);

            // Find channel
            $channel = ChannelMaster::where('channel', $channelName)->first();
            if (!$channel) {
                $skipped++;
                continue;
            }

            // Map data from Excel columns
            $healthData = [
                'channel' => $channelName,
                'l30_sales' => $this->getColumnValue($row, $headers, 'L30 Sales', 0),
                'l30_orders' => $this->getColumnValue($row, $headers, 'L30 Orders', 0),
                'report_date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ];

            // Check if record exists
            $existing = AccountHealthMaster::where('channel', $channelName)
                ->whereDate('report_date', now()->toDateString())
                ->first();

            if ($existing) {
                if ($updateMode === 'update' || $updateMode === 'replace') {
                    $existing->update($healthData);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                AccountHealthMaster::create($healthData);
                $created++;
            }
        }

        return [
            'Channel Data Created' => $created,
            'Channel Data Updated' => $updated,
            'Channel Data Skipped' => $skipped
        ];
    }

    /**
     * Import health rates data
     */
    private function importHealthRates($data, $headers, $updateMode)
    {
        $rateModels = [
            'ODR Rate' => OdrRate::class,
            'Fulfillment Rate' => FullfillmentRate::class,
            'Valid Tracking Rate' => ValidTrackingRate::class,
            'On Time Delivery Rate' => OnTimeDeliveryRate::class,
            'AtoZ Claims Rate' => AtoZClaimsRate::class,
            'Violation Rate' => VoilanceRate::class,
            'Refund Rate' => RefundRate::class,
        ];

        $results = [];

        foreach ($data as $row) {
            if (empty($row[0])) continue;

            $channelName = trim($row[0]);
            $channel = ChannelMaster::where('channel', $channelName)->first();

            if (!$channel) continue;

            foreach ($rateModels as $rateKey => $model) {
                $rateValue = $this->getColumnValue($row, $headers, $rateKey, 'N/A');

                if ($rateValue === null || $rateValue === '') continue;

                $existing = $model::where('channel_id', $channel->id)->first();

                $rateData = [
                    'channel_id' => $channel->id,
                    'current' => $rateValue,
                    'report_date' => now()->toDateString(),
                ];

                if ($existing) {
                    if ($updateMode === 'update' || $updateMode === 'replace') {
                        // Shift previous data
                        $existing->prev_2 = $existing->prev_1;
                        $existing->prev_2_date = $existing->prev_1_date;
                        $existing->prev_1 = $existing->current;
                        $existing->prev_1_date = $existing->report_date;

                        // Update with new data
                        $existing->update($rateData);
                        $results[$rateKey . ' Updated'] = ($results[$rateKey . ' Updated'] ?? 0) + 1;
                    } else {
                        $results[$rateKey . ' Skipped'] = ($results[$rateKey . ' Skipped'] ?? 0) + 1;
                    }
                } else {
                    $model::create($rateData);
                    $results[$rateKey . ' Created'] = ($results[$rateKey . ' Created'] ?? 0) + 1;
                }
            }
        }

        return $results;
    }

    /**
     * Get column value from row by header name
     */
    private function getColumnValue($row, $headers, $columnName, $default = null)
    {
        $index = array_search($columnName, $headers);
        return $index !== false ? ($row[$index] ?? $default) : $default;
    }

    /**
     * Download sample files
     */
    public function downloadSample($type = 'combined')
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            switch ($type) {
                case 'channel':
                    $sheet->setTitle('Channel Data Sample');
                    $headers = ['Channel', 'L30 Sales', 'L30 Orders', 'L60 Orders', 'Growth %', 'Gross Profit %', 'G ROI %', 'NR', 'Type', 'Listed Count'];
                    $sampleData = [
                        ['Amazon', 15000, 150, 280, 25.5, 35.2, 12.8, 0, 'FBA', 1250],
                        ['eBay', 8500, 85, 140, 18.3, 28.7, 8.9, 1, 'Auction', 890]
                    ];
                    break;

                case 'rates':
                    $sheet->setTitle('Health Rates Sample');
                    $headers = ['Channel', 'ODR Rate', 'Fulfillment Rate', 'Valid Tracking Rate', 'On Time Delivery Rate', 'AtoZ Claims Rate', 'Violation Rate', 'Refund Rate'];
                    $sampleData = [
                        ['Amazon', '0.5%', '98.5%', '99.2%', '95.8%', '0.2%', '0.1%', '2.3%'],
                        ['eBay', '1.2%', '97.8%', '98.9%', '94.5%', '0.5%', '0.3%', '3.1%']
                    ];
                    break;

                default: // combined
                    $sheet->setTitle('Combined Sample');
                    $headers = ['Channel', 'L30 Sales', 'L30 Orders', 'ODR Rate', 'Fulfillment Rate', 'Valid Tracking Rate', 'On Time Delivery Rate', 'AtoZ Claims Rate', 'Violation Rate', 'Sheet Link'];
                    $sampleData = [
                        ['Amazon', 15000, 150, '0.5%', '98.5%', '99.2%', '95.8%', '0.2%', '0.1%', 'https://docs.google.com/spreadsheets/d/example1'],
                        ['eBay', 8500, 85, '1.2%', '97.8%', '98.9%', '94.5%', '0.5%', '0.3%', 'https://docs.google.com/spreadsheets/d/example2']
                    ];
                    break;
            }

            // Set headers
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $col++;
            }

            // Add sample data
            $row = 2;
            foreach ($sampleData as $rowData) {
                $col = 'A';
                foreach ($rowData as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Auto-size columns
            foreach ($headers as $index => $header) {
                $column = chr(65 + $index); // A, B, C, etc.
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $filename = "account_health_sample_{$type}.xlsx";

            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'sample');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Sample download error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate sample file');
        }
    }
}
