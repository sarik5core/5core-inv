<?php

namespace App\Http\Controllers\MarketingMaster;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\MarketPlace\AmazonZeroController;
use App\Http\Controllers\MarketPlace\EbayZeroController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingAmazonController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingEbayController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingTemuController;
use App\Http\Controllers\MarketPlace\MacyZeroController;
use App\Http\Controllers\MarketPlace\Neweggb2cZeroController;
use App\Http\Controllers\MarketPlace\Shopifyb2cZeroController;
use App\Http\Controllers\MarketPlace\TemuZeroController;
use App\Http\Controllers\MarketPlace\WayfairZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\AliexpressZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\DobaZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\Ebay2ZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\Ebay3ZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\SheinZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\TiktokShopZeroController;
use App\Http\Controllers\MarketPlace\ZeroViewMarketPlace\WalmartZeroController;
use App\Models\AliexpressDataView;
use App\Models\AmazonDataView;
use App\Models\DobaDataView;
use App\Models\EbayDataView;
use App\Models\EbayThreeDataView;
use App\Models\EbayTwoDataView;
use App\Models\MacyDataView;
use App\Models\ZeroVisibilityMaster;
use App\Models\ProductMaster;
use App\Models\SheinDataView;
use App\Models\Shopifyb2cDataView;
use App\Models\ShopifySku;
use App\Models\TemuDataView;
use App\Models\TiktokShopDataView;
use App\Models\TiktokVideoAd;
use App\Models\WalmartDataView;
use App\Models\WayfairDataView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class ZeroVisibilityMasterController extends Controller
{

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
        $productSKUs = ProductMaster::where('sku', 'NOT LIKE', '%PARENT%')
            ->pluck('sku')
            ->toArray();

        $zeroInvCount = ShopifySku::whereIn('sku', $productSKUs)
            ->where('inv', '<=', 0)
            ->count();


        $totalSkuCount = count($productSKUs);

        // --- Get eBay zero view count ---
        // $ebayZeroCount = app(EbayZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', 'eBay')
        //     ->update(['zero_visibility_sku_count' => $ebayZeroCount]);

        // // --- Get Amazon zero view count ---
        // $amazonZeroCount = app(AmazonZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', 'Amazon')
        //     ->update(['zero_visibility_sku_count' => $amazonZeroCount]);

        // // --- Get Shopify B2C zero view count ---
        // $shopifyB2CZeroCount = app(Shopifyb2cZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', 'Shopify B2C')
        //     ->update(['zero_visibility_sku_count' => $shopifyB2CZeroCount]);

        // // --- Get Macy's zero view count ---
        // $macyZeroCount = app(MacyZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', 'Macys')
        //     ->update(['zero_visibility_sku_count' => $macyZeroCount]);

        // // --- Get Newegg B2C zero view count ---
        // $neweggB2CZeroCount = app(Neweggb2cZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', `Newegg B2C`)
        //     ->update(['zero_visibility_sku_count' => $neweggB2CZeroCount]);

        // // --- Get Wayfair zero view count ---
        // $wayfairZeroCount = app(WayfairZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', 'Wayfair')
        //     ->update(['zero_visibility_sku_count' => $wayfairZeroCount]);

        // // --- Get Temu zero view count ---
        // $temuZeroCount = app(TemuZeroController::class)->getZeroViewCount();
        // ZeroVisibilityMaster::where('channel_name', 'Temu')
        //     ->update(['zero_visibility_sku_count' => $temuZeroCount]);

        $channels = ZeroVisibilityMaster::all();

        foreach ($channels as $channel) {
            $nrCount = null;

            $channelName = strtolower(trim($channel->channel_name));

            switch ($channelName) {
                case 'amazon':
                    $nrCount = app(ListingAmazonController::class)->getNrReqCount()['NR'] ?? null;
                    break;

                case 'ebay':
                    $nrCount = app(ListingEbayController::class)->getNrReqCount()['NR'] ?? null;
                    break;

                case 'temu':
                    $nrCount = app(ListingTemuController::class)->getNrReqCount()['NR'] ?? null;
                    break;

                // Add more cases for other channels
            }

            $channel->NR = $nrCount; // assign NR to be shown in blade
        }

        return view('marketing-masters.zero-visibility-master', compact('totalSkuCount', 'zeroInvCount','channels'));
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
        $data = $request->all();

        // Store or update based on channel name
        $record = ZeroVisibilityMaster::updateOrCreate(
            ['channel_name' => $data['channel_name']],
            [
                'sheet_link' => $data['sheet_link'] ?? null,
                'is_ra_checked' => $data['is_ra_checked'] ?? false,
                'total_sku' => $data['total_sku'] ?? 0,
                'nr' => $data['nr'] ?? 0,
                'listed_req' => $data['listed_req'] ?? 0,
                'listed' => $data['listed'] ?? 0,
                'listing_pending' => $data['listing_pending'] ?? 0,
                'zero_inv' => $data['zero_inv'] ?? 0,
                'live_req' => $data['live_req'] ?? 0,
                'active_and_live' => $data['active_and_live'] ?? 0,
                'live_pending' => $data['live_pending'] ?? 0,
                'zero_visibility_sku_count' => $data['zero_visibility_sku_count'] ?? 0,
                'reason' => $data['reason'] ?? '',
                'step_taken' => $data['step_taken'] ?? '',
            ]
        );

        return response()->json(['message' => 'Saved successfully']);

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
        $row = ZeroVisibilityMaster::findOrFail($request->id);
        $row->update($request->except('id'));
        return response()->json(['status' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    //  public function getViewChannelData(Request $request)
    // {
    //     // Fetch data from the Google Sheet using the ApiController method
    //     $response = $this->apiController->fetchDataFromChannelMasterGoogleSheet();

    //     // Check if the response is successful
    //     if ($response->getStatusCode() === 200) {
    //         $data = $response->getData(); // Get the JSON data from the response
    //         $searchTerm = strtolower(trim($request->input('searchTerm')));

    //         // Filter out rows where both Parent and (Child) sku are empty
    //         $filteredData = array_filter($data->data, function($item) {
    //             $channel = $item->{'Channel '} ?? '';

    //             // Keep the row if either channel is not empty
    //             return !(empty(trim($channel)));
    //         });

    //         if (!empty($searchTerm)) {
    //             $filteredData = array_filter($filteredData, function ($item) use ($searchTerm) {
    //                 $channelName = strtolower(trim($item->{'Channel '} ?? ''));
    //                 return stripos($channelName, $searchTerm) !== false;
    //             });
    //         }

    //         // Re-index the array after filtering
    //         $filteredData = array_values($filteredData);

    //         $sortBy = $request->get('sort_by');       // e.g., "Channel ", "Exec"
    //         $sortOrder = $request->get('sort_order'); // "asc" or "desc"

    //         if ($sortBy && in_array($sortOrder, ['asc', 'desc'])) {
    //             usort($filteredData, function ($a, $b) use ($sortBy, $sortOrder) {
    //                 $valA = strtolower(trim($a->{$sortBy} ?? ''));
    //                 $valB = strtolower(trim($b->{$sortBy} ?? ''));

    //                 if (is_numeric($valA) && is_numeric($valB)) {
    //                     $valA = (float) $valA;
    //                     $valB = (float) $valB;
    //                 }

    //                 return $sortOrder === 'asc' ? $valA <=> $valB : $valB <=> $valA;
    //             });
    //         }

    //         // Return the filtered data
    //         return response()->json([
    //             'message' => 'Data fetched successfully',
    //             'data' => $filteredData,
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

    // public function getViewChannelData(Request $request)
    // {
    //     // Fetch data from Google Sheet using API controller
    //     $response = $this->apiController->fetchDataFromChannelMasterGoogleSheet();

    //     if ($response->getStatusCode() === 200) {
    //         $sheetData = $response->getData()->data ?? [];

    //         // Optional: clean input search term
    //         $searchTerm = strtolower(trim($request->input('searchTerm')));

    //         // Filter out rows with empty channel
    //         $filteredData = array_filter($sheetData, function ($item) {
    //             return !empty(trim($item->{'Channel '} ?? ''));
    //         });

    //         // If search term is provided, filter by channel name
    //         if (!empty($searchTerm)) {
    //             $filteredData = array_filter($filteredData, function ($item) use ($searchTerm) {
    //                 $channel = strtolower(trim($item->{'Channel '} ?? ''));
    //                 return str_contains($channel, $searchTerm);
    //             });
    //         }

    //         // Sort logic
    //         $sortBy = $request->get('sort_by');
    //         $sortOrder = $request->get('sort_order');

    //         if ($sortBy && in_array($sortOrder, ['asc', 'desc'])) {
    //             usort($filteredData, function ($a, $b) use ($sortBy, $sortOrder) {
    //                 $valA = strtolower(trim($a->{$sortBy} ?? ''));
    //                 $valB = strtolower(trim($b->{$sortBy} ?? ''));

    //                 if (is_numeric($valA) && is_numeric($valB)) {
    //                     $valA = floatval($valA);
    //                     $valB = floatval($valB);
    //                 }

    //                 return $sortOrder === 'asc' ? $valA <=> $valB : $valB <=> $valA;
    //             });
    //         }

    //         // Keep only necessary fields (channel, R&A, URL LINK)
    //         $finalData = array_map(function ($item) {
    //             return [
    //                 'Channel ' => trim($item->{'Channel '} ?? ''),
    //                 'R&A' => trim($item->{'R&A'} ?? ''),
    //                 'URL LINK' => trim($item->{'URL LINK'} ?? ''),
    //             ];
    //         }, $filteredData);

    //         return response()->json([
    //             'message' => 'Data fetched successfully',
    //             'data' => array_values($finalData),
    //             'status' => 200
    //         ]);
    //     }

    //     // Fallback on error
    //     return response()->json([
    //         'message' => 'Failed to fetch data from Google Sheet',
    //         'status' => $response->getStatusCode()
    //     ], $response->getStatusCode());
    // }


    // public function getMergedChannelData(Request $request)
    // {
    //     ini_set('max_execution_time', 120);

    //     $sheetResponse = (new ApiController)->fetchDataFromChannelMasterGoogleSheet();

    //     if ($sheetResponse->getStatusCode() !== 200) {
    //         return response()->json(['data' => [], 'message' => 'Sheet fetch failed'], 500);
    //     }

    //     $sheetData = $sheetResponse->getData()->data ?? [];

    //     // Load DB records
    //     $dbRecords = ZeroVisibilityMaster::all()->keyBy(fn($row) => strtolower(trim($row->channel_name)));

    //     $amazonZeroController = app()->make(AmazonZeroController::class);
    //     $ebayZeroController = app()->make(EbayZeroController::class);
    //     $shopifyb2cZeroController = app()->make(Shopifyb2cZeroController::class);
    //     $macysZeroController = app()->make(MacyZeroController::class);
    //     $wayfairZeroController = app()->make(WayfairZeroController::class);
    //     $temuZeroController = app()->make(TemuZeroController::class);
    //     $dobaZeroController = app()->make(DobaZeroController::class);

    //     $mergedData = [];

    //     foreach ($sheetData as $item) {
    //         $channelName = trim($item->{'Channel '} ?? '');
    //         if (!$channelName)
    //             continue;

    //         $lower = strtolower($channelName);
    //         $dbRow = $dbRecords[$lower] ?? null;

    //         // If not found in DB, create it
    //         if (!$dbRow) {
    //             $dbRow = ZeroVisibilityMaster::create([
    //                 'channel_name' => $channelName,
    //                 // You can set other default fields here if needed
    //             ]);
    //             // Add to $dbRecords so future lookups in this loop work
    //             $dbRecords[$lower] = $dbRow;
    //         }

    //         $ch = strtolower(trim($channelName));
    //         $nrCount = $this->getNRCount($ch);
    //         // $nrCount = $this->getNRCount(strtolower(trim($channelName)));

    //         // $amazonData = DB::table('amazon_data_view')->pluck('value');

    //         $totalListed = 0;
    //         $totalLive = 0;
    //         $livePending = null;
    //         $zeroVisibilityCount = '';

            
    //         if ($ch === 'amazon') {

    //             $zeroVisibilityCount = $amazonZeroController->getZeroViewCount();

    //             $amazonData = AmazonDataView::pluck('value');

    //             foreach ($amazonData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }

    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //             // if ($livePending < 0) {
    //             //     $livePending = 0; // avoid negative
    //             // }
    //         }

    //         if ($ch === 'ebay') {

    //             $zeroVisibilityCount = $ebayZeroController->getZeroViewCount();

    //             $ebayData = EbayDataView::pluck('value');

    //             foreach ($ebayData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }
    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //         }

    //         if ($ch === 'shopify b2c') {

    //             $zeroVisibilityCount = $shopifyb2cZeroController->getZeroViewCount();

    //             $shopifyb2cData = Shopifyb2cDataView::pluck('value');

    //             foreach ($shopifyb2cData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }
    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //         }

    //         if ($ch === 'macys') {

    //             $zeroVisibilityCount = $macysZeroController->getZeroViewCount();

    //             $macyData = MacyDataView::pluck('value');

    //             foreach ($macyData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }
    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //         }

    //         if ($ch === 'wayfair') {

    //             $zeroVisibilityCount = $wayfairZeroController->getZeroViewCount();

    //             $wayfairData = MacyDataView::pluck('value');

    //             foreach ($wayfairData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }
    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //         }

    //         if ($ch === 'temu') {

    //             $zeroVisibilityCount = $temuZeroController->getZeroViewCount();

    //             $temuData = TemuDataView::pluck('value');

    //             foreach ($temuData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }
    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //         }

    //         if ($ch === 'doba') {

    //             $zeroVisibilityCount = $dobaZeroController->getZeroViewCount();

    //             $dobaData = DobaDataView::pluck('value');

    //             foreach ($dobaData as $valueData) {
    //                 if (is_string($valueData)) {
    //                     $valueData = json_decode($valueData, true);
    //                 }

    //                 if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
    //                     $totalListed++;
    //                 }
    //                 if (!empty($valueData['Live']) && $valueData['Live'] === true) {
    //                     $totalLive++;
    //                 }
    //             }
    //             // Pull Zero Inv value from DB
    //             $zeroInv = $dbRow->zero_inv ?? 0;

    //             // Apply formula = Listed - Zero Inv - Live
    //             $livePending = $totalListed - $zeroInv - $totalLive;
    //         }


    //         $mergedData[] = [
    //             'Channel ' => $channelName,
    //             // 'URL LINK' => trim($item->{'URL LINK'} ?? ''),
    //             'R&A' => trim($item->{'R&A'} ?? ''),
    //             'Live Pending' => $livePending,
    //             'Zero Visibility SKU Count' => $zeroVisibilityCount,
               
    //         ];
    //     }

    //     return response()->json([
    //         'data' => array_values($mergedData),
    //         'message' => 'Merged successfully'
    //     ]);
    // }

    public function getMergedChannelData(Request $request)
    {
        ini_set('max_execution_time', 120);

        $sheetResponse = (new ApiController)->fetchDataFromChannelMasterGoogleSheet();
        if ($sheetResponse->getStatusCode() !== 200) {
            return response()->json(['data' => [], 'message' => 'Sheet fetch failed'], 500);
        }

        $sheetData = $sheetResponse->getData()->data ?? [];

        // Load DB records once
        $dbRecords = ZeroVisibilityMaster::all()->keyBy(fn($row) => strtolower(trim($row->channel_name)));

        // Preload all zero counts
        $zeroCounts = [
            'amazon'      => app(AmazonZeroController::class)->getZeroViewCount(),
            'ebay'        => app(EbayZeroController::class)->getZeroViewCount(),
            'shopify b2c' => app(Shopifyb2cZeroController::class)->getZeroViewCount(),
            'macys'       => app(MacyZeroController::class)->getZeroViewCount(),
            'wayfair'     => app(WayfairZeroController::class)->getZeroViewCount(),
            'temu'        => app(TemuZeroController::class)->getZeroViewCount(),
            'doba'        => app(DobaZeroController::class)->getZeroViewCount(),
            'ebay 2'      => app(Ebay2ZeroController::class)->getZeroViewCount(),
            'ebay 3'      => app(Ebay3ZeroController::class)->getZeroViewCount(),
            'walmart'     => app(WalmartZeroController::class)->getZeroViewCount(),
            'aliexpress'  => app(AliexpressZeroController::class)->getZeroViewCount(),
            'tiktok shop' => app(TiktokShopZeroController::class)->getZeroViewCount(),
            // 'shein'       => app(SheinZeroController::class)->getZeroViewCount(),
        ];

        // Preload and decode all data views once
        $channelData = [
            'amazon'      => AmazonDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'ebay'        => EbayDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'shopify b2c' => Shopifyb2cDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'macys'       => MacyDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'wayfair'     => WayfairDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'temu'        => TemuDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'doba'        => DobaDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'ebay 2'      => EbayTwoDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'ebay 3'      => EbayThreeDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'walmart'     => WalmartDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'aliexpress'  => AliexpressDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            'tiktok shop' => TiktokShopDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
            // 'shein'       => SheinDataView::pluck('value')->map(fn($v) => is_string($v) ? json_decode($v, true) : $v),
        ];

        // Precompute counts for each channel
        $channelCounts = [];
        foreach ($channelData as $channel => $records) {
            $listed = 0;
            $live = 0;
            foreach ($records as $valueData) {
                if (!empty($valueData['Listed']) && $valueData['Listed'] === true) {
                    $listed++;
                }
                if (!empty($valueData['Live']) && $valueData['Live'] === true) {
                    $live++;
                }
            }
            $channelCounts[$channel] = [
                'listed' => $listed,
                'live'   => $live
            ];
        }

        $mergedData = [];

        foreach ($sheetData as $item) {
            $channelName = trim($item->{'Channel '} ?? '');
            if (!$channelName) {
                continue;
            }

            $lower = strtolower($channelName);
            $dbRow = $dbRecords[$lower] ?? null;

            if (!$dbRow) {
                $dbRow = ZeroVisibilityMaster::create([
                    'channel_name' => $channelName,
                ]);
                $dbRecords[$lower] = $dbRow;
            }

            $zeroInv = $dbRow->zero_inv ?? 0;

            $listedCount = $channelCounts[$lower]['listed'] ?? 0;
            $liveCount   = $channelCounts[$lower]['live'] ?? 0;

            // Apply formula: Listed - Zero Inv - Live
            $livePending = $listedCount - $zeroInv - $liveCount;

            $mergedData[] = [
                'Channel '               => $channelName,
                'R&A'                    => trim($item->{'R&A'} ?? ''),
                'Live Pending'           => $livePending,
                'Zero Visibility SKU Count' => $zeroCounts[$lower] ?? 0,
            ];
        }

        return response()->json([
            'data' => array_values($mergedData),
            'message' => 'Merged successfully'
        ]);
    }



    public function exportCsv()
    {
        $sheetResponse = (new ApiController)->fetchDataFromChannelMasterGoogleSheet();
        if ($sheetResponse->getStatusCode() !== 200) {
            return response()->json(['error' => 'Failed to fetch Google Sheet'], 500);
        }

        $sheetData = $sheetResponse->getData()->data ?? [];
        $dbRecords = ZeroVisibilityMaster::all()->keyBy(fn($row) => strtolower(trim($row->channel_name)));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="zero_visibility_master_data.csv"',
        ];

        $columns = [
            'SL',
            'Channel Name',
            'R&A',
            'URL LINK',
            'Total SKU',
            'NR',
            'Listed Req',
            'Listed',
            'Listing Pending',
            'Zero Inv',
            'Live Req',
            'Active & Live',
            'Live Pending',
            'Zero Visibility SKU Count',
            'Reason',
            'Step Taken',
        ];

        $callback = function () use ($sheetData, $dbRecords, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $sl = 1;
            foreach ($sheetData as $item) {
                $channelName = trim($item->{'Channel '} ?? '');
                if (!$channelName)
                    continue;

                $lower = strtolower($channelName);
                $dbRow = $dbRecords[$lower] ?? null;

                fputcsv($file, [
                    $sl++,
                    $channelName,
                    trim($item->{'R&A'} ?? ''),
                    // trim($item->{'URL LINK'} ?? ''),
                    $dbRow->sheet_link ?? '',
                    $dbRow->total_sku ?? '',
                    $dbRow->nr ?? '',
                    $dbRow->listed_req ?? '',
                    $dbRow->listed ?? '',
                    $dbRow->listing_pending ?? '',
                    $dbRow->zero_inv ?? '',
                    $dbRow->live_req ?? '',
                    $dbRow->active_and_live ?? '',
                    $dbRow->live_pending ?? '',
                    $dbRow->zero_visibility_sku_count ?? '',
                    $dbRow->reason ?? '',
                    $dbRow->step_taken ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function updateRaCheckbox(Request $request)
    {
        $channel = trim($request->input('channel'));
        $checked = $request->input('checked') ? true : false;

        Log::info('Received update-checkbox request', [
            'channel' => $channel,
            'checked' => $checked,
        ]);

        // Update Google Sheet
        $url = 'https://script.google.com/macros/s/AKfycbzhlu7KV3dx3PS-9XPFBI9FMgI0JZIAgsuZY48Lchr_60gkSmx1hNAukKwFGZXgPwid/exec';

        $response = Http::post($url, [
            'channel' => $channel,
            'checked' => $checked
        ]);

        if ($response->failed()) {
            Log::error('Failed to send to GSheet:', [$response->body()]);
            return response()->json(['success' => false, 'message' => 'Failed to update GSheet'], 500);
        }

        Log::info("Google Sheet updated successfully");

        // Update Laravel DB
        ZeroVisibilityMaster::updateOrCreate(
            ['channel_name' => $channel],
            ['is_ra_checked' => $checked]
        );

        Log::info("Database updated for channel: $channel");

        return response()->json(['success' => true, 'message' => 'Updated GSheet & DB']);
    }


    private function getNRCount($channel)
    {
        $channel = strtolower(trim($channel));

        try {
            switch ($channel) {
                case 'amazon':
                    return app(AmazonZeroController::class)->getNrReqCount()['NR'] ?? 0;

                case 'ebay':
                    return app(EbayZeroController::class)->getNrReqCount()['NR'] ?? 0;

                case 'temu':
                    return app(TemuZeroController::class)->getNrReqCount()['NR'] ?? 0;

                case 'doba':
                    return app(DobaZeroController::class)->getNrReqCount()['NR'] ?? 0;

                case 'macys':
                    return app(MacyZeroController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'walmart':
                //     return app(ListingWalmartController::class)->getNrReqCount()['NR'] ?? 0;
                
                case 'wayfair':
                    return app(WayfairZeroController::class)->getNrReqCount()['NR'] ?? 0;
                
                // case 'ebay 3':
                //     return app(ListingEbayThreeController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'shopify b2c':
                //     return app(ListingShopifyB2CController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'reverb':
                //     return app(ListingReverbController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'aliexpress':
                //     return app(ListingAliexpressController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'shein':
                //     return app(ListingSheinController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'tiktok shop':
                //     return app(ListingTiktokShopController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'shopify wholesale/ds':
                //     return app(ListingShopifyWholesaleController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'faire':
                //     return app(ListingFaireController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'ebay 2':
                //     return app(ListingEbayTwoController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'mercari w ship':
                //     return app(ListingMercariWShipController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'newegg b2c':
                //     return app(ListingNeweggB2CController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'fb marketplace':
                //     return app(ListingFBMarketplaceController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'syncee':
                //     return app(ListingSynceeController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'auto ds':
                //     return app(ListingAutoDSController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'mercari w/o ship':
                //     return app(ListingMercariWoShipController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'business 5core':
                //     return app(ListingBusiness5CoreController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'zendrop':
                //     return app(ListingZendropController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'poshmark':
                //     return app(ListingPoshmarkController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'appscenic':
                //     return app(ListingAppscenicController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'tiendamia':
                //     return app(ListingTiendamiaController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'spocket':
                //     return app(ListingSpocketController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'offerup':
                //     return app(ListingOfferupController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'newegg b2b':
                //     return app(ListingNeweggB2BController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'fb shop':
                //     return app(ListingFBShopController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'instagram shop':
                //     return app(ListingInstagramShopController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'Yamibuy':
                //     return app(ListingYamibuyController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'dhgate':
                //     return app(ListingDHGateController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'bestbuy usa':
                //     return app(ListingBestbuyUSAController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'sw gear exchange':
                //     return app(ListingSWGearExchangeController::class)->getNrReqCount()['NR'] ?? 0;

                // case 'dhgate':
                //     return app(ListingDHGateController::class)->getNrReqCount()['NR'] ?? 0;
  

                default:
                    return 0;
            }
        } catch (\Throwable $e) {
            return 0;
        }
    }






}
