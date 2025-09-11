<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingAliexpressController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingAmazonController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingAppscenicController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingAutoDSController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingBestbuyUSAController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingBusiness5CoreController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingDHGateController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingDobaController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingEbayController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingEbayThreeController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingEbayTwoController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingFaireController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingFBMarketplaceController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingFBShopController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingInstagramShopController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingMacysController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingMercariWoShipController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingMercariWShipController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingNeweggB2BController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingNeweggB2CController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingOfferupController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingPlsController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingPoshmarkController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingReverbController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingSheinController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingShopifyB2CController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingShopifyWholesaleController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingSpocketController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingSWGearExchangeController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingSynceeController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingTemuController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingTiendamiaController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingTiktokShopController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingWalmartController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingWayfairController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingYamibuyController;
use App\Http\Controllers\MarketPlace\ListingMarketPlace\ListingZendropController;
use App\Http\Controllers\MarketPlace\OverallAmazonController;
use App\Models\AmazonDatasheet;
use App\Models\AmazonDataView;
use App\Models\ApiCentralWalmartApiData;
use App\Models\ApiCentralWalmartMetric;
use App\Models\ChannelMaster;
use App\Models\DobaMetric;
use App\Models\Ebay2Metric;
use App\Models\Ebay3Metric;
use App\Models\EbayMetric;
use App\Models\MacyProduct;
use App\Models\MarketplacePercentage;
use App\Models\ProductMaster;
use App\Models\ReverbProduct;
use App\Models\ShopifySku;
use App\Models\TemuMetric;
use App\Models\TemuProductSheet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Spatie\FlareClient\Api;

class ChannelMasterController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    /**
     * Handle dynamic route parameters and return a view.
     */
    public function channel_master_index(Request $request, $first = null, $second = null)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        if ($first === "assets") {
            return redirect('home');
        }

        // return view($first, compact('mode', 'demo', 'second', 'channels'));
        return view($first . '.' . $second, [
            'mode' => $mode,
            'demo' => $demo,
        ]);
    }

    // public function getViewChannelData(Request $request)
    // {
    //     // Fetch data from the Google Sheet using the ApiController method
    //     $response = $this->apiController->fetchDataFromChannelMasterGoogleSheet();

    //     // Check if the response is successful
    //     if ($response->getStatusCode() === 200) {
    //         $data = $response->getData(); // Get the JSON data from the response
    //         $searchTerm = strtolower(trim($request->input('searchTerm')));

    //         $dbChannelLinks = ChannelMaster::pluck('sheet_link', 'channel')->mapWithKeys(function ($link, $name) {
    //             return [strtolower(trim($name)) => $link];
    //         });
            
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

    //         foreach ($filteredData as &$item) {
    //             $channelName = strtolower(trim($item->{'Channel '} ?? ''));
    //             $item->sheet_link = $dbChannelLinks[$channelName] ?? null;
    //         }

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

    // public function getViewChannelData(Request $request)        //merged with db+sheet
    // {
    //     // Fetch data from the Google Sheet using the ApiController method
    //     $response = $this->apiController->fetchDataFromChannelMasterGoogleSheet();

    //     // Fetch all entries from the database
    //     $dbData = ChannelMaster::select('channel as Channel', 'sheet_link')->get();

    //     // Format DB data to match the sheet structure
    //     $formattedDbData = $dbData->map(function ($item) {
    //         return (object)[
    //             'Channel ' => $item->Channel,
    //             'sheet_link' => $item->sheet_link,
    //             'source' => 'database'
    //         ];
    //     })->toArray();

    //     $mergedData = [];

    //     if ($response->getStatusCode() === 200) {
    //         $sheetData = $response->getData(); // Get the JSON data from the response
    //         $searchTerm = strtolower(trim($request->input('searchTerm')));

    //         $sheetRows = array_filter($sheetData->data, function ($item) {
    //             $channel = $item->{'Channel '} ?? '';
    //             return !empty(trim($channel));
    //         });

    //         // Normalize DB sheet links to override sheet values
    //         $dbChannelLinks = ChannelMaster::pluck('sheet_link', 'channel')->mapWithKeys(function ($link, $name) {
    //             return [strtolower(trim($name)) => $link];
    //         });

    //         // Update sheet rows with DB sheet_link if available
    //         foreach ($sheetRows as &$item) {
    //             $channelName = strtolower(trim($item->{'Channel '} ?? ''));
    //             $item->sheet_link = $dbChannelLinks[$channelName] ?? null;
    //             $item->source = 'sheet';
    //         }

    //         // Convert sheet data to array
    //         $sheetRows = array_values($sheetRows);

    //         // Now merge DB rows not present in sheet
    //         $sheetChannelNames = collect($sheetRows)->pluck('Channel ')->map(function ($c) {
    //             return strtolower(trim($c));
    //         })->toArray();

    //         $dbOnlyRows = array_filter($formattedDbData, function ($row) use ($sheetChannelNames) {
    //             return !in_array(strtolower(trim($row->{'Channel '})), $sheetChannelNames);
    //         });

    //         // Merge sheet + db-only rows
    //         $mergedData = array_merge($sheetRows, $dbOnlyRows);

    //         // Search filter
    //         if (!empty($searchTerm)) {
    //             $mergedData = array_filter($mergedData, function ($item) use ($searchTerm) {
    //                 $channelName = strtolower(trim($item->{'Channel '} ?? ''));
    //                 return stripos($channelName, $searchTerm) !== false;
    //             });
    //         }

    //         // Sorting
    //         $sortBy = $request->get('sort_by');       // e.g., "Channel ", "Exec"
    //         $sortOrder = $request->get('sort_order'); // "asc" or "desc"

    //         if ($sortBy && in_array($sortOrder, ['asc', 'desc'])) {
    //             usort($mergedData, function ($a, $b) use ($sortBy, $sortOrder) {
    //                 $valA = strtolower(trim($a->{$sortBy} ?? ''));
    //                 $valB = strtolower(trim($b->{$sortBy} ?? ''));

    //                 if (is_numeric($valA) && is_numeric($valB)) {
    //                     $valA = (float) $valA;
    //                     $valB = (float) $valB;
    //                 }

    //                 return $sortOrder === 'asc' ? $valA <=> $valB : $valB <=> $valA;
    //             });
    //         }

    //         return response()->json([
    //             'message' => 'Data fetched from both Sheet and DB',
    //             'data' => array_values($mergedData),
    //             'status' => 200
    //         ]);
    //     }

    //     // If Google Sheet failed, return only DB data
    //     return response()->json([
    //         'message' => 'Google Sheet failed, showing DB data only',
    //         'data' => $formattedDbData,
    //         'status' => 200
    //     ]);
    // }

    // public function getViewChannelData(Request $request)
    // {

    //     // Fetch data from the Google Sheet using the ApiController method
    //     $response = $this->apiController->fetchDataFromChannelMasterGoogleSheet();

    //     // Fetch all entries from the database
    //     $dbData = ChannelMaster::select('channel as Channel', 'sheet_link', 'nr', 'w_ads', 'update', 'type')->get();

    //     // Format DB data to match the sheet structure
    //     $formattedDbData = $dbData->map(function ($item) {
    //         return (object)[
    //             'Channel ' => $item->Channel,
    //             'sheet_link' => $item->sheet_link,
    //             'type' => $item->type,
    //             'source' => 'database',
    //             'nr' => $item->nr,              // [NEW]
    //             'w_ads' => $item->w_ads,        // [NEW]
    //             'update' => $item->update, // [NEW]
    //             // 'red_margin' => $item->red_margin // [NEW]
    //         ];
    //     })->toArray();

    //     $mergedData = [];

    //     if ($response->getStatusCode() === 200) {
    //         $sheetData = $response->getData(); // Get the JSON data from the response
    //         $searchTerm = strtolower(trim($request->input('searchTerm')));

    //         $sheetRows = array_filter($sheetData->data, function ($item) {
    //             $channel = $item->{'Channel '} ?? '';
    //             return !empty(trim($channel));
    //         });

    //         // Normalize DB sheet links + checkboxes to override sheet values
    //         $dbChannelMap = $dbData->keyBy(function ($item) {
    //             return strtolower(trim($item->Channel));
    //         });

    //         // Update sheet rows with DB fields if available
    //         foreach ($sheetRows as &$item) {
    //             $channelName = strtolower(trim($item->{'Channel '} ?? ''));
    //             $dbRow = $dbChannelMap[$channelName] ?? null;

    //             $item->sheet_link = $dbRow->sheet_link ?? null;
    //             $item->source = 'sheet';
                
    //             // [NEW] Inject DB checkbox states if available
    //             $item->nr = $dbRow->nr ?? 0;
    //             $item->w_ads = $dbRow->w_ads ?? 0;
    //             $item->update = $dbRow->update ?? 0;
    //             $item->type = $dbRow->type ?? null;
    //             // $item->red_margin = $dbRow->red_margin ?? 0;

    //             $item->listed_count = $this->getListedCount($channelName);
    //         }

    //         // Convert sheet data to array
    //         $sheetRows = array_values($sheetRows);

    //         // Merge DB rows not present in sheet
    //         $sheetChannelNames = collect($sheetRows)->pluck('Channel ')->map(function ($c) {
    //             return strtolower(trim($c));
    //         })->toArray();

    //         $dbOnlyRows = array_filter($formattedDbData, function ($row) use ($sheetChannelNames) {
    //             return !in_array(strtolower(trim($row->{'Channel '})), $sheetChannelNames);
    //         });

    //         // Merge sheet + db-only rows
    //         $mergedData = array_merge($sheetRows, $dbOnlyRows);

    //         // Search filter
    //         if (!empty($searchTerm)) {
    //             $mergedData = array_filter($mergedData, function ($item) use ($searchTerm) {
    //                 $channelName = strtolower(trim($item->{'Channel '} ?? ''));
    //                 return stripos($channelName, $searchTerm) !== false;
    //             });
    //         }

    //         // Sorting
    //         $sortBy = $request->get('sort_by');       // e.g., "Channel ", "Exec"
    //         $sortOrder = $request->get('sort_order'); // "asc" or "desc"

    //         if ($sortBy && in_array($sortOrder, ['asc', 'desc'])) {
    //             usort($mergedData, function ($a, $b) use ($sortBy, $sortOrder) {
    //                 $valA = strtolower(trim($a->{$sortBy} ?? ''));
    //                 $valB = strtolower(trim($b->{$sortBy} ?? ''));

    //                 if (is_numeric($valA) && is_numeric($valB)) {
    //                     $valA = (float) $valA;
    //                     $valB = (float) $valB;
    //                 }

    //                 return $sortOrder === 'asc' ? $valA <=> $valB : $valB <=> $valA;
    //             });
    //         }

    //         return response()->json([
    //             'message' => 'Data fetched from both Sheet and DB',
    //             'data' => array_values($mergedData),
    //             'status' => 200
    //         ]);
    //     }

    //     // If Google Sheet failed, return only DB data
    //     return response()->json([
    //         'message' => 'Google Sheet failed, showing DB data only',
    //         'data' => $formattedDbData,
    //         'status' => 200
    //     ]);
    // }

    // public function getViewChannelData(Request $request)
    // {
    //     $channel = ChannelMaster::where('status', 'Active')
    //             ->orderBy('id', 'asc')
    //             ->value('channel');

    //     if (!$channel) {
    //         return response()->json(['status' => 404, 'message' => 'No active channel found']);
    //     }

    //     $key = strtolower(str_replace([' ', '-', '&', '/'], '', trim($channel)));

    //     // Map key -> controller class
    //     $controllerMap = [
    //         'amazon'    => OverallAmazonController::class,
          
    //         // add more as needed
    //     ];

    //     if (!isset($controllerMap[$key])) {
    //         return response()->json(['status' => 404, 'message' => 'Channel not supported']);
    //     }

    //     $controllerClass = $controllerMap[$key];

    //     if (!class_exists($controllerClass)) {
    //         return response()->json(['status' => 404, 'message' => 'Controller not found']);
    //     }

    //     $controller = app($controllerClass);

    //     // Convention: `get{Channel}ChannelData`
    //     $method = 'get' . ucfirst($key) . 'ChannelData';
    //     if (!method_exists($controller, $method)) {
    //         return response()->json(['status' => 404, 'message' => "Method $method not found"]);
    //     }

    //     return $controller->{$method}($request);
    // }


    // public function getViewChannelData(Request $request)
    // {
    //     $channels = ChannelMaster::where('status', 'Active')
    //         ->orderBy('id', 'asc')
    //         ->pluck('channel')
    //         ->toArray();

    //     if (empty($channels)) {
    //         return response()->json(['status' => 404, 'message' => 'No active channel found']);
    //     }

    //     // Map channel key -> controller class
    //     $controllerMap = [
    //         'amazon' => $this->getAmazonChannelData($request),
    //         // 'ebay' => ListingEbayController::class,
            
    //         // add more here...
    //     ];

    //     $finalData = [];

    //     foreach ($channels as $channel) {
    //         $key = strtolower(str_replace([' ', '-', '&', '/'], '', trim($channel)));

    //         if (!isset($controllerMap[$key])) {
    //             continue; // skip unsupported channels
    //         }

    //         $controllerClass = $controllerMap[$key];
    //         if (!class_exists($controllerClass)) {
    //             continue;
    //         }

    //         $controller = app($controllerClass);

    //         // convention: getAmazonChannelData, getEbayChannelData, etc.
    //         $method = 'get' . ucfirst($key) . 'ChannelData';
    //         if (!method_exists($controller, $method)) {
    //             continue;
    //         }

    //         $response = $controller->{$method}($request);

    //         $payload = $response->getData(true);

    //         // if (!empty($payload['data'])) {
    //         //     // 🔹 Add channel name inside each row
    //         //     foreach ($payload['data'] as &$row) {
    //         //         $row['channel'] = ucfirst($channel);
    //         //     }
    //         //     $finalData = array_merge($finalData, $payload['data']);
    //         // }

    //         if (!empty($payload['data'])) {
    //             foreach ($payload['data'] as &$row) {
    //                 // Only add channel if it's missing
    //                 if (empty($row['channel'])) {
    //                     $row['channel'] = ucfirst($channel);
    //                 }
    //             }
    //             $finalData = array_merge($finalData, $payload['data']);
    //         }

    //     }

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'Channel data fetched successfully',
    //         'data' => $finalData,
    //     ]);
    // }


    // public function getViewChannelData(Request $request)
    // {
    //     $channels = ChannelMaster::where('status', 'Active')
    //         ->orderBy('id', 'asc')
    //         ->pluck('channel')
    //         ->toArray();

    //     if (empty($channels)) {
    //         return response()->json(['status' => 404, 'message' => 'No active channel found']);
    //     }

    //     $finalData = [];

    //     foreach ($channels as $channel) {
    //         $row = [
    //             'Channel '     => ucfirst($channel),  // keep key name same as DataTable
    //             'Link'         => null,
    //             'sheet_link'   => null,
    //             'L-60 Sales'   => 0,
    //             'L30 Sales'    => 0,
    //             'Growth'       => 0,
    //             'L60 Orders'   => 0,
    //             'L30 Orders'   => 0,
    //             'Gprofit%'     => 'N/A',
    //             'G Roi%'       => 'N/A',
    //             'red_margin'   => 0,
    //             'NR'           => 0,
    //             'type'         => '',
    //             'listed_count' => 0,
    //             'W/Ads'        => 0,
    //             '0 Sold SKU Count' => 0,
    //             'Sold SKU Count'   => 0,
    //             'Brand Registry'   => '',
    //             'Update'       => 0,
    //             'Account health' => null,
    //         ];

    //         // 🔹 Merge channel specific data (Amazon example)
    //         $key = strtolower(str_replace([' ', '-', '&', '/'], '', trim($channel)));
    //         if ($key === 'amazon') {
    //             $amazonData = $this->getAmazonChannelData($request)->getData(true);
    //             if (!empty($amazonData['data'])) {
    //                 $row = array_merge($row, $amazonData['data'][0]); // merge amazon metrics
    //             }
    //         }

    //         $finalData[] = $row;
    //     }

    //     return response()->json([
    //         'status'  => 200,
    //         'message' => 'Channel data fetched successfully',
    //         'data'    => $finalData,
    //     ]);
    // }

    // public function getViewChannelData(Request $request)
    // {
    //     // Fetch both channel and sheet_link from ChannelMaster
    //     $channels = ChannelMaster::where('status', 'Active')
    //         ->orderBy('id', 'asc')
    //         ->get(['channel', 'sheet_link']);  // ⬅️ NEW CODE: include sheet_link

    //     if ($channels->isEmpty()) {
    //         return response()->json(['status' => 404, 'message' => 'No active channel found']);
    //     }

    //     $finalData = [];

    //     foreach ($channels as $channelRow) {
    //         $channel = $channelRow->channel;

    //         $row = [
    //             'Channel '     => ucfirst($channel),
    //             'Link'         => null,
    //             'sheet_link'   => $channelRow->sheet_link, // NEW CODE: pull from DB
    //             'L-60 Sales'   => 0,
    //             'L30 Sales'    => 0,
    //             'Growth'       => 0,
    //             'L60 Orders'   => 0,
    //             'L30 Orders'   => 0,
    //             'Gprofit%'     => 'N/A',
    //             'G Roi%'       => 'N/A',
    //             'red_margin'   => 0,
    //             'NR'           => 0,
    //             'type'         => '',
    //             'listed_count' => 0,
    //             'W/Ads'        => 0,
    //             '0 Sold SKU Count' => 0,
    //             'Sold SKU Count'   => 0,
    //             'Brand Registry'   => '',
    //             'Update'       => 0,
    //             'Account health' => null,
    //         ];

    //         // Merge channel specific data (Amazon example)
    //         $key = strtolower(str_replace([' ', '-', '&', '/'], '', trim($channel)));
    //         if ($key === 'amazon') {
    //             $amazonData = $this->getAmazonChannelData($request)->getData(true);
    //             if (!empty($amazonData['data'])) {
    //                 $row = array_merge($row, $amazonData['data'][0]);
    //             }
    //         }

    //         $finalData[] = $row;
    //     }

    //     return response()->json([
    //         'status'  => 200,
    //         'message' => 'Channel data fetched successfully',
    //         'data'    => $finalData,
    //     ]);
    // }

    public function getViewChannelData(Request $request)
    {
        // Fetch both channel and sheet_link from ChannelMaster
        $channels = ChannelMaster::where('status', 'Active')
            ->orderBy('id', 'asc')
            ->get(['channel', 'sheet_link']);

        if ($channels->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'No active channel found']);
        }

        $finalData = [];

        // Map lowercase channel key => controller method
        $controllerMap = [
            'amazon'    => 'getAmazonChannelData',
            'ebay'      => 'getEbayChannelData',
            'ebaytwo'   => 'getEbaytwoChannelData',
            'ebaythree' => 'getEbaythreeChannelData',
            'macys'     => 'getMacysChannelData',
            'reverb'    => 'getReverbChannelData',
            'doba'      => 'getDobaChannelData',
            'temu'      => 'getTemuChannelData',
            'walmart'   => 'getWalmartChannelData',
            // add all other 40 channels here like:
            // 'walmart' => 'getWalmartChannelData',
            // 'shopify' => 'getShopifyChannelData',
        ];

        foreach ($channels as $channelRow) {
            $channel = $channelRow->channel;

            // Base row
            $row = [
                'Channel '       => ucfirst($channel),
                'Link'           => null,
                'sheet_link'     => $channelRow->sheet_link,
                'L-60 Sales'     => 0,
                'L30 Sales'      => 0,
                'Growth'         => 0,
                'L60 Orders'     => 0,
                'L30 Orders'     => 0,
                'Gprofit%'       => 'N/A',
                'G Roi%'         => 'N/A',
                'red_margin'     => 0,
                'NR'             => 0,
                'type'           => '',
                'listed_count'   => 0,
                'W/Ads'          => 0,
                // '0 Sold SKU Count' => 0,
                // 'Sold SKU Count'   => 0,
                // 'Brand Registry'   => '',
                'Update'         => 0,
                'Account health' => null,
            ];

            // Normalize channel name for lookup
            $key = strtolower(str_replace([' ', '-', '&', '/'], '', trim($channel)));

            if (isset($controllerMap[$key]) && method_exists($this, $controllerMap[$key])) {
                $method = $controllerMap[$key];
                $data = $this->$method($request)->getData(true); // call respective function
                if (!empty($data['data'])) {
                    $row = array_merge($row, $data['data'][0]);
                }
            }

            $finalData[] = $row;
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Channel data fetched successfully',
            'data'    => $finalData,
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
        $amazonRows   = $query->get(['sku', 'price', 'units_ordered_l30']);
        $totalProfit  = 0;

        foreach ($amazonRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->units_ordered_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP from JSON/column ---
        $amazonSkus   = $amazonRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $amazonPMs    = ProductMaster::whereIn('sku', $amazonSkus)->get();

        $totalLpValue = 0;
        foreach ($amazonPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $ebayRows     = $query->get(['sku', 'ebay_price', 'ebay_l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->ebay_price;
            $unitsL30  = (int) $row->ebay_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $ebayRows     = $query->get(['sku', 'ebay_price', 'ebay_l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->ebay_price;
            $unitsL30  = (int) $row->ebay_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $ebayRows     = $query->get(['sku', 'ebay_price', 'ebay_l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->ebay_price;
            $unitsL30  = (int) $row->ebay_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $ebayRows     = $query->get(['sku', 'price', 'm_l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->m_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $ebayRows     = $query->get(['sku', 'price', 'r_l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->r_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $ebayRows     = $query->get(['sku', 'anticipated_income', 'quantity_l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->anticipated_income;
            $unitsL30  = (int) $row->quantity_l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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
        $l60Orders = $query->sum('l30');

        $l30Sales  = (clone $query)->selectRaw('SUM(l30 * price) as total')->value('total') ?? 0;
        $l60Sales  = (clone $query)->selectRaw('SUM(l30 * price) as total')->value('total') ?? 0;

        $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

        // Get eBay marketing percentage
        $percentage = MarketplacePercentage::where('marketplace', 'Temu')->value('percentage') ?? 100;
        $percentage = $percentage / 100; // convert % to fraction

        // Load product masters (lp, ship) keyed by SKU
        $productMasters = ProductMaster::all()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        // Calculate total profit
        $ebayRows     = $query->get(['sku', 'price', 'l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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

        $query = ApiCentralWalmartMetric::where('sku', 'not like', '%Parent%');

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
        $ebayRows     = $query->get(['sku', 'price', 'l30']);
        $totalProfit  = 0;

        foreach ($ebayRows as $row) {
            $sku       = strtoupper($row->sku);
            $price     = (float) $row->price;
            $unitsL30  = (int) $row->l30;

            $soldAmount = $unitsL30 * $price;
            if ($soldAmount <= 0) {
                continue;
            }

            $lp   = 0;
            $ship = 0;

            if (isset($productMasters[$sku])) {
                $pm = $productMasters[$sku];

                $values = is_array($pm->Values) ? $pm->Values :
                        (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

                $lp   = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
                $ship = isset($values['ship']) ? (float) $values['ship'] : ($pm->ship ?? 0);
            }

            // Profit per unit
            $profitPerUnit = ($price * $percentage) - $lp - $ship;
            $profitTotal   = $profitPerUnit * $unitsL30;

            $totalProfit += $profitTotal;
        }

        // --- FIX: Calculate total LP only for SKUs in eBayMetrics ---
        $ebaySkus   = $ebayRows->pluck('sku')->map(fn($s) => strtoupper($s))->toArray();
        $ebayPMs    = ProductMaster::whereIn('sku', $ebaySkus)->get();

        $totalLpValue = 0;
        foreach ($ebayPMs as $pm) {
            $values = is_array($pm->Values) ? $pm->Values :
                    (is_string($pm->Values) ? json_decode($pm->Values, true) : []);

            $lp = isset($values['lp']) ? (float) $values['lp'] : ($pm->lp ?? 0);
            $totalLpValue += $lp;
        }

        // Use L30 Sales for denominator
        $gProfitPct = $l30Sales > 0 ? ($totalProfit / $l30Sales) * 100 : 0;
        $gRoi       = $totalLpValue > 0 ? ($totalProfit / $totalLpValue) : 0;

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
            'G Roi'      => round($gRoi, 2),
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




    // public function getEbayChannelData(Request $request)
    // {
    //     $result = [];

    //     // $l30Sales = AmazonDatasheet::where('sku', 'not like', '%Parent%')->sum('units_ordered_l30');
    //     // $l60Sales = AmazonDatasheet::where('sku', 'not like', '%Parent%')->sum('units_ordered_l60');

    //     $query = EbayMetric::where('sku', 'not like', '%Parent%');

    //     $l30Orders = $query->sum('ebay_l30');
    //     $l60Orders = $query->sum('ebay_l60');

    //     $l30Sales  = (clone $query)->selectRaw('SUM(ebay_l30 * ebay_price) as total')->value('total') ?? 0;
    //     $l60Sales  = (clone $query)->selectRaw('SUM(ebay_l60 * ebay_price) as total')->value('total') ?? 0;


    //     // $growth = $l30Orders > 0 ? (($l30Orders - $l60Orders) / $l30Orders) * 100 : 0;
    //     $growth = $l30Sales > 0 ? (($l30Sales - $l60Sales) / $l30Sales) * 100 : 0;

    //     $channelData = ChannelMaster::where('channel', 'eBay')->first();

    //     $result[] = [
    //         'Channel '   => 'eBay',
    //         'L-60 Sales' => intval($l60Sales),
    //         'L30 Sales'  => intval($l30Sales),
    //         'Growth'     => round($growth, 2) . '%',
    //         'L60 Orders' => $l60Orders,
    //         'L30 Orders' => $l30Orders,
    //         'Gprofit%'   => 'N/A',
    //         'G Roi%'     => 'N/A',
    //         'type'       => $channelData->type ?? '',
    //         'W/Ads'      => $channelData->w_ads ?? 0,
    //         'NR'         => $channelData->nr ?? 0,
    //         'Update'     => $channelData->update ?? 0,
    //     ];

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'eBay channel data fetched successfully',
    //         'data' => $result,
    //     ]);
    // }



    


    /**
     * Store a newly created channel in storage.
     */
    public function store(Request $request)
    {
        // Validate Request Data
        $validatedData = $request->validate([
            'channel' => 'required|string',
            'sheet_link' => 'nullable|url',
            'type' => 'nullable|string',
            // 'status' => 'required|in:Active,In Active,To Onboard,In Progress',
            // 'executive' => 'nullable|string',
            // 'b_link' => 'nullable|string',
            // 's_link' => 'nullable|string',
            // 'user_id' => 'nullable|string',
            // 'action_req' => 'nullable|string',
        ]);
        // Save Data to Database
        try {
            $channel = ChannelMaster::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'channel saved successfully',
                'data' => $channel
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving channel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save channel. Please try again.'
            ], 500);
        }
    }

    /**
     * Store a update channel in storage.
     */
    public function update(Request $request)
    {
        $originalChannel = $request->input('original_channel');
        $updatedChannel = $request->input('channel');
        $sheetUrl = $request->input('sheet_url');
        $type = $request->input('type');

        $channel = ChannelMaster::where('channel', $originalChannel)->first();

        if (!$channel) {
            return response()->json(['success' => false, 'message' => 'Channel not found']);
        }

        $channel->channel = $updatedChannel;
        $channel->sheet_link = $sheetUrl;
        $channel->type = $type;
        $channel->save();

        return response()->json(['success' => true]);
    }


    public function getChannelCounts()
    {
        // Fetch counts from the database
        $totalChannels = DB::table('channel_master')->count();
        $activeChannels = DB::table('channel_master')->where('status', 'Active')->count();
        $inactiveChannels = DB::table('channel_master')->where('status', 'In Active')->count();
    
        return response()->json([
            'success' => true,
            'totalChannels' => $totalChannels,
            'activeChannels' => $activeChannels,
            'inactiveChannels' => $inactiveChannels,
        ]);
    }

    public function destroy(Request $request)
    {
        // Delete channel from database
    }

    public function sendToGoogleSheet(Request $request)
    {

        $channel = $request->input('channel');
        $checked = $request->input('checked');

        Log::info('Received update-checkbox request', [
            'channel' => $channel,
            'checked' => $checked,
        ]);

        // Log for debugging
        Log::info("Updating GSheet for channel: $channel, checked: " . ($checked ? 'true' : 'false'));

        $url = 'https://script.google.com/macros/s/AKfycbzhlu7KV3dx3PS-9XPFBI9FMgI0JZIAgsuZY48Lchr_60gkSmx1hNAukKwFGZXgPwid/exec'; 

        $response = Http::post($url, [
            'channel' => $channel,
            'checked' => $checked
        ]);

        if ($response->successful()) {
            Log::info("Google Sheet updated successfully");
            return response()->json(['success' => true, 'message' => 'Updated GSheet']);
        } else {
            Log::error('Failed to send to GSheet:', [$response->body()]);
            return response()->json(['success' => false, 'message' => 'Failed to update GSheet'], 500);
        }
    }

    public function updateExecutive(Request $request)
    {
        $channel = trim($request->input('channel'));
        $exec = trim($request->input('exec'));

        $spreadsheetId = '13ZjGtJvSkiLHin2VnkBD-hrGimSRD7duVjILfkoJ2TA';
        $url = 'https://script.google.com/macros/s/AKfycbzYct_htZ_z89S36bPMDdjdDy6s1Nrzm79No6N2PqPriyrwXF1plIschk1c4cDnPYQ5/exec'; // Your Apps Script doPost URL

        $payload = [
            'channel' => $channel,
            'exec' => $exec,
            'action' => 'update_exec'
        ];

        $response = Http::post($url, $payload);

        if ($response->successful()) {
            return response()->json(['message' => 'Executive updated successfully.']);
        } else {
            return response()->json(['message' => 'Failed to update.'], 500);
        }
    }


    public function updateSheetLink(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
            'sheet_link' => 'nullable|url',
        ]);

        ChannelMaster::updateOrCreate(
            ['channel' => $request->channel], // search by channel
            ['sheet_link' => $request->sheet_link] // update or insert
        );

        return response()->json(['status' => 'success']);
    }

    public function toggleCheckboxFlag(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
            'field' => 'required|in:nr,w_ads,update',
            'value' => 'required|boolean'
        ]);

        $channelName = trim($request->channel);
        $field = $request->field;
        $value = $request->value;

        $channel = ChannelMaster::whereRaw('LOWER(channel) = ?', [strtolower($channelName)])->first();

        if ($channel) {
            $channel->$field = $value;
            $channel->save();
            return response()->json(['success' => true, 'message' => 'Channel updated.']);
        }

        // Channel not found — insert new row
        $newChannel = new ChannelMaster();
        $newChannel->channel = $channelName;
        $newChannel->$field = $value;
        $newChannel->save();

        return response()->json(['success' => true, 'message' => 'New channel inserted and updated.']);
    }


    public function updateType(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
            'type' => 'nullable|string'
        ]);

        $channelName = trim($request->input('channel'));
        $type = $request->input('type');

        $channel = ChannelMaster::where('channel', $channelName)->first();

        if (!$channel) {
            // If not found, create new
            $channel = new ChannelMaster();
            $channel->channel = $channelName;
        }

        $channel->type = $type;
        $channel->save();

        return response()->json([
            'success' => true,
            'message' => 'Type updated successfully.'
        ]);
    }


    private function getListedCount($channel)
    {
        $channel = strtolower(trim($channel));

        try {
            switch ($channel) {
                case 'amazon':
                    return app(ListingAmazonController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'ebay':
                    return app(ListingEbayController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'temu':
                    return app(ListingTemuController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'doba':
                    return app(ListingDobaController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'macys':
                    return app(ListingMacysController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'walmart':
                    return app(ListingWalmartController::class)->getNrReqCount()['Listed'] ?? 0;
                
                case 'wayfair':
                    return app(ListingWayfairController::class)->getNrReqCount()['Listed'] ?? 0;
                
                case 'ebay 3':
                    return app(ListingEbayThreeController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'shopify b2c':
                    return app(ListingShopifyB2CController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'reverb':
                    return app(ListingReverbController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'aliexpress':
                    return app(ListingAliexpressController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'shein':
                    return app(ListingSheinController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'tiktok shop':
                    return app(ListingTiktokShopController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'shopify wholesale/ds':
                    return app(ListingShopifyWholesaleController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'faire':
                    return app(ListingFaireController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'ebay 2':
                    return app(ListingEbayTwoController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'mercari w ship':
                    return app(ListingMercariWShipController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'newegg b2c':
                    return app(ListingNeweggB2CController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'fb marketplace':
                    return app(ListingFBMarketplaceController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'syncee':
                    return app(ListingSynceeController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'auto ds':
                    return app(ListingAutoDSController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'mercari w/o ship':
                    return app(ListingMercariWoShipController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'business 5core':
                    return app(ListingBusiness5CoreController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'zendrop':
                    return app(ListingZendropController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'poshmark':
                    return app(ListingPoshmarkController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'appscenic':
                    return app(ListingAppscenicController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'tiendamia':
                    return app(ListingTiendamiaController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'spocket':
                    return app(ListingSpocketController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'offerup':
                    return app(ListingOfferupController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'newegg b2b':
                    return app(ListingNeweggB2BController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'fb shop':
                    return app(ListingFBShopController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'instagram shop':
                    return app(ListingInstagramShopController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'Yamibuy':
                    return app(ListingYamibuyController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'dhgate':
                    return app(ListingDHGateController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'bestbuy usa':
                    return app(ListingBestbuyUSAController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'sw gear exchange':
                    return app(ListingSWGearExchangeController::class)->getNrReqCount()['Listed'] ?? 0;

                case 'dhgate':
                    return app(ListingDHGateController::class)->getNrReqCount()['Listed'] ?? 0;
  

                default:
                    return 0;
            }
        } catch (\Throwable $e) {
            return 0;
        }
    }


}
