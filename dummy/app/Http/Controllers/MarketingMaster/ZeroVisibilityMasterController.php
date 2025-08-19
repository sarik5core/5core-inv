<?php

namespace App\Http\Controllers\MarketingMaster;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiController;
use App\Models\ZeroVisibilityMaster;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
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
        $ebayZeroCount = app(\App\Http\Controllers\MarketPlace\EbayZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', 'eBay')
            ->update(['zero_visibility_sku_count' => $ebayZeroCount]);

        // --- Get Amazon zero view count ---
        $amazonZeroCount = app(\App\Http\Controllers\MarketPlace\AmazonZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', 'Amazon')
            ->update(['zero_visibility_sku_count' => $amazonZeroCount]);

        // --- Get Shopify B2C zero view count ---
        $shopifyB2CZeroCount = app(\App\Http\Controllers\MarketPlace\Shopifyb2cZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', 'Shopify B2C')
            ->update(['zero_visibility_sku_count' => $shopifyB2CZeroCount]);

        // --- Get Macy's zero view count ---
        $macyZeroCount = app(\App\Http\Controllers\MarketPlace\MacyZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', 'Macys')
            ->update(['zero_visibility_sku_count' => $macyZeroCount]);

        // --- Get Newegg B2C zero view count ---
        $neweggB2CZeroCount = app(\App\Http\Controllers\MarketPlace\Neweggb2cZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', `Newegg B2C`)
            ->update(['zero_visibility_sku_count' => $neweggB2CZeroCount]);

        // --- Get Wayfair zero view count ---
        $wayfairZeroCount = app(\App\Http\Controllers\MarketPlace\WayfairZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', 'Wayfair')
            ->update(['zero_visibility_sku_count' => $wayfairZeroCount]);

        // --- Get Temu zero view count ---
        $temuZeroCount = app(\App\Http\Controllers\MarketPlace\TemuZeroController::class)->getZeroViewCount();
        ZeroVisibilityMaster::where('channel_name', 'Temu')
            ->update(['zero_visibility_sku_count' => $temuZeroCount]);


        return view('marketing-masters.zero-visibility-master', compact('totalSkuCount', 'zeroInvCount'));
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


    public function getMergedChannelData(Request $request)
    {
        $sheetResponse = (new ApiController)->fetchDataFromChannelMasterGoogleSheet();

        if ($sheetResponse->getStatusCode() !== 200) {
            return response()->json(['data' => [], 'message' => 'Sheet fetch failed'], 500);
        }

        $sheetData = $sheetResponse->getData()->data ?? [];

        // Load DB records
        $dbRecords = ZeroVisibilityMaster::all()->keyBy(fn($row) => strtolower(trim($row->channel_name)));

        $mergedData = [];

        foreach ($sheetData as $item) {
            $channelName = trim($item->{'Channel '} ?? '');
            if (!$channelName)
                continue;

            $lower = strtolower($channelName);
            $dbRow = $dbRecords[$lower] ?? null;

            // If not found in DB, create it
            if (!$dbRow) {
                $dbRow = ZeroVisibilityMaster::create([
                    'channel_name' => $channelName,
                    // You can set other default fields here if needed
                ]);
                // Add to $dbRecords so future lookups in this loop work
                $dbRecords[$lower] = $dbRow;
            }

            $mergedData[] = [
                'Channel ' => $channelName,
                'URL LINK' => trim($item->{'URL LINK'} ?? ''),
                'R&A' => trim($item->{'R&A'} ?? ''),

                // DB fields (will be empty if not yet saved)
                'Total SKU' => $dbRow->total_sku ?? '',
                'NR' => $dbRow->nr ?? '',
                'Listed Req' => $dbRow->listed_req ?? '',
                'Listed' => $dbRow->listed ?? '',
                'Listing Pending' => $dbRow->listing_pending ?? '',
                'Zero Inv' => $dbRow->zero_inv ?? '',
                'Live Req' => $dbRow->live_req ?? '',
                'Active & Live' => $dbRow->active_and_live ?? '',
                'Live Pending' => $dbRow->live_pending ?? '',
                'Zero Visibility SKU Count' => $dbRow->zero_visibility_sku_count ?? '',
                'Reason' => $dbRow->reason ?? '',
                'Step Taken' => $dbRow->step_taken ?? '',
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






}
