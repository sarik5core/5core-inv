<?php

namespace App\Http\Controllers\MarketingMaster;

use App\Http\Controllers\Controller;
use App\Models\MarketplacePercentage;
use Illuminate\Http\Request;


class ListingMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('marketing-masters.listingMaster');
    }

    // fetch data for listing audit master
    public function getListingMasterData(Request $request)
    {
        $marketplaces = MarketplacePercentage::pluck('marketplace')->toArray();

        $data = array_map(function ($marketplace) {
            // Normalize marketplace name for controller naming and URL convention
            $normalizedName = $marketplace;
            $controllerClass = "\\App\\Http\\Controllers\\MarketPlace\\ListingMarketPlace\\Listing{$normalizedName}Controller";

            // Default values
            $counts = [
                'REQ' => 0,
                'Listed' => 0,
                'Pending' => 0
            ];

            // Build URL slug based on marketplace name
            $urlSlug = 'listing-' . strtolower($marketplace);

            // Try to get counts from the corresponding controller if it exists
            if (class_exists($controllerClass)) {
                try {
                    $marketplaceController = app($controllerClass);
                    if (method_exists($marketplaceController, 'getNrReqCount')) {
                        $response = $marketplaceController->getNrReqCount();
                        // If response is a JsonResponse, decode it
                        if ($response instanceof \Illuminate\Http\JsonResponse) {
                            $counts = $response->getData(true); // true returns associative array
                        } else {
                            $counts = $response;
                        }
                    } else {
                        // \Log::error("Method getNrReqCount not found in controller for {$marketplace} ({$controllerClass})");
                    }
                } catch (\Exception $e) {
                    // \Log::error("Error loading counts for {$marketplace} ({$controllerClass}): " . $e->getMessage());
                }
                // Only log if counts are not fetched (all zero)
                if (($counts['REQ'] ?? 0) === 0 && ($counts['Listed'] ?? 0) === 0 && ($counts['Pending'] ?? 0) === 0) {
                    // \Log::error("Counts not fetched for marketplace: {$marketplace}");
                }
                return [
                    'Channel' => $marketplace,
                    'REQ' => $counts['REQ'] ?? 0,
                    'Listed' => $counts['Listed'] ?? 0,
                    'Pending' => $counts['Pending'] ?? 0,
                    'channel_url' => url($urlSlug),
                ];
            } else {
                // \Log::error("Controller class not found for marketplace: {$marketplace} ({$controllerClass})");
            }
            return [
                'Channel' => $marketplace,
                'REQ' => 0,
                'Listed' => 0,
                'Pending' => 0,
                'channel_url' => null,
            ];
        }, $marketplaces);

        return response()->json([
            'data' => $data,
            'status' => 200
        ]);
    }
}
