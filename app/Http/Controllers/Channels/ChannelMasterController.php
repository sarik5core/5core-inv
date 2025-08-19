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
use App\Models\ChannelMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

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

    public function getViewChannelData(Request $request)
    {
        // Fetch data from the Google Sheet using the ApiController method
        $response = $this->apiController->fetchDataFromChannelMasterGoogleSheet();

        // Fetch all entries from the database
        $dbData = ChannelMaster::select('channel as Channel', 'sheet_link', 'nr', 'w_ads', 'update', 'type')->get();

        // Format DB data to match the sheet structure
        $formattedDbData = $dbData->map(function ($item) {
            return (object)[
                'Channel ' => $item->Channel,
                'sheet_link' => $item->sheet_link,
                'type' => $item->type,
                'source' => 'database',
                'nr' => $item->nr,              // [NEW]
                'w_ads' => $item->w_ads,        // [NEW]
                'update' => $item->update, // [NEW]
                // 'red_margin' => $item->red_margin // [NEW]
            ];
        })->toArray();

        $mergedData = [];

        if ($response->getStatusCode() === 200) {
            $sheetData = $response->getData(); // Get the JSON data from the response
            $searchTerm = strtolower(trim($request->input('searchTerm')));

            $sheetRows = array_filter($sheetData->data, function ($item) {
                $channel = $item->{'Channel '} ?? '';
                return !empty(trim($channel));
            });

            // Normalize DB sheet links + checkboxes to override sheet values
            $dbChannelMap = $dbData->keyBy(function ($item) {
                return strtolower(trim($item->Channel));
            });

            // Update sheet rows with DB fields if available
            foreach ($sheetRows as &$item) {
                $channelName = strtolower(trim($item->{'Channel '} ?? ''));
                $dbRow = $dbChannelMap[$channelName] ?? null;

                $item->sheet_link = $dbRow->sheet_link ?? null;
                $item->source = 'sheet';
                
                // [NEW] Inject DB checkbox states if available
                $item->nr = $dbRow->nr ?? 0;
                $item->w_ads = $dbRow->w_ads ?? 0;
                $item->update = $dbRow->update ?? 0;
                $item->type = $dbRow->type ?? null;
                // $item->red_margin = $dbRow->red_margin ?? 0;

                $item->listed_count = $this->getListedCount($channelName);
            }

            // Convert sheet data to array
            $sheetRows = array_values($sheetRows);

            // Merge DB rows not present in sheet
            $sheetChannelNames = collect($sheetRows)->pluck('Channel ')->map(function ($c) {
                return strtolower(trim($c));
            })->toArray();

            $dbOnlyRows = array_filter($formattedDbData, function ($row) use ($sheetChannelNames) {
                return !in_array(strtolower(trim($row->{'Channel '})), $sheetChannelNames);
            });

            // Merge sheet + db-only rows
            $mergedData = array_merge($sheetRows, $dbOnlyRows);

            // Search filter
            if (!empty($searchTerm)) {
                $mergedData = array_filter($mergedData, function ($item) use ($searchTerm) {
                    $channelName = strtolower(trim($item->{'Channel '} ?? ''));
                    return stripos($channelName, $searchTerm) !== false;
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by');       // e.g., "Channel ", "Exec"
            $sortOrder = $request->get('sort_order'); // "asc" or "desc"

            if ($sortBy && in_array($sortOrder, ['asc', 'desc'])) {
                usort($mergedData, function ($a, $b) use ($sortBy, $sortOrder) {
                    $valA = strtolower(trim($a->{$sortBy} ?? ''));
                    $valB = strtolower(trim($b->{$sortBy} ?? ''));

                    if (is_numeric($valA) && is_numeric($valB)) {
                        $valA = (float) $valA;
                        $valB = (float) $valB;
                    }

                    return $sortOrder === 'asc' ? $valA <=> $valB : $valB <=> $valA;
                });
            }

            return response()->json([
                'message' => 'Data fetched from both Sheet and DB',
                'data' => array_values($mergedData),
                'status' => 200
            ]);
        }

        // If Google Sheet failed, return only DB data
        return response()->json([
            'message' => 'Google Sheet failed, showing DB data only',
            'data' => $formattedDbData,
            'status' => 200
        ]);
    }


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
