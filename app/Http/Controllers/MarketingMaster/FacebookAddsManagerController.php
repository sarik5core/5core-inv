<?php

namespace App\Http\Controllers\MarketingMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FacebookAddsManagerController extends Controller
{
    public function index()
    {
        return view('marketing-masters.facebook_ads_manager.index');
    }

    public function getFacebookAdsData()
    {
        $data = [
            ['id' => 1, 'campaign_name' => 'Campaign 1', 'status' => 'Active', 'budget' => 100],
            ['id' => 2, 'campaign_name' => 'Campaign 2', 'status' => 'Paused', 'budget' => 200],
        ];

        return response()->json($data);
    }
}
