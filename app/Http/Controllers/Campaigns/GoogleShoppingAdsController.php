<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoogleShoppingAdsController extends Controller
{
    public function index(){
        return view('campaign.google-shopping-ads');
    }

    public function googleShoppingSerp(){
        return view('campaign.google-shopping-ads-serp');
    }

    public function googleShoppingPmax(){
        return view('campaign.google-shopping-ads-pmax');
    }

    public function googleShoppingAdsRunning(){
        return view('campaign.google-shopping-ads-running');
    }

    public function getGoogleShoppingAdsData(){

        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();

        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');

        $googleCampaigns = DB::connection('apicentral')
            ->table('google_ads_campaigns')
            ->select('campaign_name')
            ->get();

        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper(trim($pm->sku));
            $parent = $pm->parent;

            $shopify = $shopifyData[$sku] ?? null;

            $matchedCampaign = $googleCampaigns->first(function ($c) use ($sku) {
                $campaign = strtoupper(trim($c->campaign_name));
                $parts = array_map('trim', explode(',', $campaign));
                foreach ($parts as $part) {
                    if ($part === $sku) {
                        return true;
                    }
                }
                return false;
            });

            $row = [];
            $row['parent'] = $parent;
            $row['sku']    = $pm->sku;
            $row['INV']    = $shopify->inv ?? 0;
            $row['L30']    = $shopify->quantity ?? 0;
            $row['campaignName'] = $matchedCampaign->campaign_name ?? null;


            $result[] = (object) $row;

        }
        
        $uniqueResult = collect($result)->unique('sku')->values()->all();

        return response()->json([
            'message' => 'Data fetched successfully',
            'data'    => $uniqueResult,
            'status'  => 200,
        ]);
    }
}
