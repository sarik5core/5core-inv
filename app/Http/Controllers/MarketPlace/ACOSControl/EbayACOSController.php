<?php

namespace App\Http\Controllers\MarketPlace\ACOSControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EbayACOSController extends Controller
{
    public function index(){
        return view('market-places.acos-control.ebay-acos-control');
    }
}
