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

        $periods = ['L7', 'L15', 'L30', 'L60'];
        $campaignReports = [];
        foreach ($periods as $period) {
            $campaignReports[$period] = EbayPriorityReport::where('report_range', $period)
                ->where(function ($q) use ($skus) {
                    foreach ($skus as $sku) {
                        $q->orWhere('campaign_name', 'LIKE', '%' . $sku . '%');
                    }
                })->get();
        }

        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);
            $parent = $pm->parent;
            $shopify = $shopifyData[$pm->sku] ?? null;

            $row = [
                'parent' => $parent,
                'sku'    => $pm->sku,
                'INV'    => $shopify->inv ?? 0,
                'L30'    => $shopify->quantity ?? 0,
                'NR'     => ''
            ];

            $matchedCampaignL30 = $campaignReports['L30']->first(function ($item) use ($sku) {
                return stripos($item->campaign_name, $sku) !== false;
            });

            $row['campaignName'] = $matchedCampaignL30->campaign_name ?? '';
            $row['campaignBudgetAmount'] = $matchedCampaignL30->campaignBudgetAmount ?? 0;

            if(!$matchedCampaignL30){
                continue;
            }

            if (isset($nrValues[$pm->sku])) {
                $raw = $nrValues[$pm->sku];
                if (!is_array($raw)) {
                    $raw = json_decode($raw, true);
                }
                if (is_array($raw)) {
                    $row['NR'] = $raw['NR'] ?? null;
                }
            }

            foreach ($periods as $period) {
                $matchedCampaign = $campaignReports[$period]->first(function ($item) use ($sku) {
                    return stripos($item->campaign_name, $sku) !== false;
                });
                
                if (!$matchedCampaign) {
                    $row["impressions_" . strtolower($period)] = 0;
                    $row["clicks_" . strtolower($period)]      = 0;
                    $row["ad_sales_" . strtolower($period)]    = 0;
                    $row["ad_sold_" . strtolower($period)]     = 0;
                    $row["spend_" . strtolower($period)]       = 0;
                    $row["acos_" . strtolower($period)]        = 0;
                    $row["cpc_" . strtolower($period)]         = 0;
                    continue;
                }

                $adFees = (float) str_replace('USD ', '', $matchedCampaign->cpc_ad_fees_payout_currency ?? 0);
                $sales  = (float) str_replace('USD ', '', $matchedCampaign->cpc_sale_amount_payout_currency ?? 0);
                $clicks = (float) ($matchedCampaign->cpc_clicks ?? 0);
                $spend  = (float) ($matchedCampaign->cpc_cost ?? $adFees);
                $cpc    = $clicks > 0 ? ($spend / $clicks) : 0;
                $acos   = $sales > 0 ? ($adFees / $sales) * 100 : 0;

                if ($adFees > 0 && $sales === 0) {
                    $acos = 100;
                }

                $row["impressions_" . strtolower($period)] = $matchedCampaign->cpc_impressions ?? 0;
                $row["clicks_" . strtolower($period)]      = $matchedCampaign->cpc_clicks ?? 0;
                $row["ad_sales_" . strtolower($period)]    = $sales;
                $row["ad_sold_" . strtolower($period)]     = $matchedCampaign->unitsSold ?? 0;
                $row["spend_" . strtolower($period)]       = $adFees;
                $row["acos_" . strtolower($period)]        = $acos;
                $row["cpc_" . strtolower($period)]         = $cpc;
            }

            if ($row['NR'] !== "NRA") {
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
