<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use App\Models\WalmartDataView;
use App\Models\WalmartProductSheet;
use Illuminate\Http\Request;

class WalmartUtilisationController extends Controller
{
    public function index(){
        return view('campaign.walmart-utilized-kw-ads');
    }

    public function overUtilisedView(){
        return view('campaign.walmart-over-utili');
    }

    public function underUtilisedView(){
        return view('campaign.walmart-under-utili');
    }

    public function correctlyUtilisedView(){
        return view('campaign.walmart-correctly-utili');
    }

    public function getWalmartAdsData()
    {
        $normalizeSku = fn($sku) => strtoupper(trim(preg_replace('/\s+/', ' ', str_replace("\xc2\xa0", ' ', $sku))));

        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')
            ->filter()
            ->unique()
            ->map(fn($sku) => $normalizeSku($sku))
            ->values()
            ->all();

        $walmartProductSheet = WalmartProductSheet::whereIn('sku', $skus)->get()->keyBy(fn($item) => $normalizeSku($item->sku));

        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy(fn($item) => $normalizeSku($item->sku));

        $nrValues = WalmartDataView::whereIn('sku', $skus)->pluck('value', 'sku');

        $result = [];

        foreach ($productMasters as $pm) {
            $sku = $normalizeSku($pm->sku);
            $parent = $pm->parent;

            $amazonSheet = $walmartProductSheet[$sku] ?? null;
            $shopify = $shopifyData[$sku] ?? null;

            $row = [];
            $row['parent'] = $parent;
            $row['sku']    = $pm->sku;
            $row['INV']    = $shopify->inv ?? 0;
            $row['L30']    = $shopify->quantity ?? 0;
            $row['WA_L30'] = $amazonSheet->l30 ?? 0;

            $row['NRL']  = '';
            $row['NRA']  = '';
            $row['FBA']  = '';

            if (isset($nrValues[$pm->sku])) {
                $raw = $nrValues[$pm->sku];
                if (!is_array($raw)) {
                    $raw = json_decode($raw, true);
                }
                if (is_array($raw)) {
                    $row['NRL']  = $raw['NRL'] ?? null;
                    $row['NRA']  = $raw['NRA'] ?? null;
                    $row['FBA']  = $raw['FBA'] ?? null;
                }
            }

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
