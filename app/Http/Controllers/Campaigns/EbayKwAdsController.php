<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\EbayDataView;
use App\Models\EbayPriorityReport;
use App\Models\ProductMaster;
use App\Models\ShopifySku;

class EbayKwAdsController extends Controller
{
    public function index(){
        return view('campaign.ebay-kw-ads');
    }

    public function getEbayKwAdsData(){
        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();

        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');

        $nrValues = EbayDataView::whereIn('sku', $skus)->pluck('value', 'sku');

        $ebayCampaignReportsL30 = EbayPriorityReport::where('report_range', 'L30')
            ->where(function ($q) use ($skus) {
                foreach ($skus as $sku) {
                    $q->orWhere('campaign_name', 'LIKE', '%' . $sku . '%');
                }
            })
            ->get();

        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);
            $parent = $pm->parent;

            $shopify = $shopifyData[$pm->sku] ?? null;

            $matchedCampaignL30 = $ebayCampaignReportsL30->first(function ($item) use ($sku) {
                return stripos($item->campaign_name, $sku) !== false;
            });

            if (!$matchedCampaignL30) {
                continue;
            }

            $row = [];
            $row['parent'] = $parent;
            $row['sku']    = $pm->sku;
            $row['INV']    = $shopify->inv ?? 0;
            $row['L30']    = $shopify->quantity ?? 0;
            $row['campaignName'] = $matchedCampaignL30->campaign_name ?? '';
            $adFees   = (float) str_replace('USD ', '', $matchedCampaignL30->cpc_ad_fees_payout_currency ?? 0);
            $sales    = (float) str_replace('USD ', '', $matchedCampaignL30->cpc_sale_amount_payout_currency ?? 0 );

            $acos = $sales > 0 ? ($adFees / $sales) * 100 : 0;
            
            if($adFees > 0 && $sales === 0){
                $row['acos_l30'] = 100;
            }else{
                $row['acos_l30'] = $acos;
            }
            $row['impressions_l30'] = $matchedCampaignL30->cpc_impressions ?? 0;
            $row['clicks_l30'] = $matchedCampaignL30->cpc_clicks ?? 0;
            $row['ad_sales_l30'] = $sales;
            $row['spend_l30'] = $adFees;
            $row['cpc_l30'] = (float) str_replace('USD ', '', $matchedCampaignL30->cost_per_click ?? 0);

            $row['NR'] = '';
            if (isset($nrValues[$pm->sku])) {
                $raw = $nrValues[$pm->sku];
                if (!is_array($raw)) {
                    $raw = json_decode($raw, true);
                }
                if (is_array($raw)) {
                    $row['NR'] = $raw['NR'] ?? null;
                }
            }

            if ($row['NR'] !== 'NRA') {
                $result[] = (object) $row;
            }
        }

        return response()->json([
            'message' => 'Data fetched successfully',
            'data'    => $result,
            'status'  => 200,
        ]);
    }
}
