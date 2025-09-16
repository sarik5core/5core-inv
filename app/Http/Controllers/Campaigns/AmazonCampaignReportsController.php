<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\AmazonDatasheet;
use App\Models\AmazonDataView;
use App\Models\AmazonSpCampaignReport;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmazonCampaignReportsController extends Controller
{
    public function index(){
        $data = DB::table('amazon_sp_campaign_reports')
            ->selectRaw('
                report_date_range,
                SUM(clicks) as clicks, 
                SUM(spend) as spend, 
                SUM(purchases1d) as orders, 
                SUM(sales1d) as sales
            ')
            ->whereIn('report_date_range', ['L60','L30','L15','L7','L1'])
            ->groupBy('report_date_range')
            ->orderByRaw("FIELD(report_date_range, 'L60','L30','L15','L7','L1')")
            ->get();

        $dates  = $data->pluck('report_date_range');
        $clicks = $data->pluck('clicks')->map(fn($v) => (int) $v);
        $spend  = $data->pluck('spend')->map(fn($v) => (float) $v);
        $orders = $data->pluck('orders')->map(fn($v) => (int) $v);
        $sales  = $data->pluck('sales')->map(fn($v) => (float) $v);

        
        return view('campaign.amazon-campaign-reports',compact('dates', 'clicks', 'spend', 'orders', 'sales'));
    }

    public function getAmazonCampaignsData(){

        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();

        $amazonDatasheetsBySku = AmazonDatasheet::whereIn('sku', $skus)->get()->keyBy(function ($item) {
            return strtoupper($item->sku);
        });

        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');

        $nrValues = AmazonDataView::whereIn('sku', $skus)->pluck('value', 'sku');

        $amazonSpCampaignReportsL30 = AmazonSpCampaignReport::where('ad_type', 'SPONSORED_PRODUCTS')
            ->where('report_date_range', 'L30')
            ->where(function ($q) use ($skus) {
                foreach ($skus as $sku) {
                    $q->orWhere('campaignName', 'LIKE', '%' . $sku . '%');
                }
            })
            ->get();

        $amazonSpCampaignReportsL7 = AmazonSpCampaignReport::where('ad_type', 'SPONSORED_PRODUCTS')
            ->where('report_date_range', 'L7')
            ->where(function ($q) use ($skus) {
                foreach ($skus as $sku) {
                    $q->orWhere('campaignName', 'LIKE', '%' . $sku . '%');
                }
            })
            ->get();

        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);
            $parent = $pm->parent;

            $amazonSheet = $amazonDatasheetsBySku[$sku] ?? null;
            $shopify = $shopifyData[$pm->sku] ?? null;

            $matchedCampaignL30 = $amazonSpCampaignReportsL30->first(function ($item) use ($sku) {
                return strcasecmp(trim($item->campaignName), $sku) === 0;
            });

            $matchedCampaignL7 = $amazonSpCampaignReportsL7->first(function ($item) use ($sku) {
                return strcasecmp(trim($item->campaignName), $sku) === 0;
            });

            $row = [];
            $row['parent'] = $parent;
            $row['sku']    = $pm->sku;
            $row['INV']    = $shopify->inv ?? 0;
            $row['L30']    = $shopify->quantity ?? 0;
            $row['fba']    = $pm->fba ?? null;
            $row['A_L30']  = $amazonSheet->units_ordered_l30 ?? 0;
            $row['campaign_id'] = $matchedCampaignL30->campaign_id ??  '';
            $row['campaignName'] = $matchedCampaignL30->campaignName ?? '';
            $row['campaignStatus'] = $matchedCampaignL30->campaignStatus ?? '';
            $row['campaignBudgetAmount'] = $matchedCampaignL30->campaignBudgetAmount ?? '';
            $row['l7_cpc'] = $matchedCampaignL7->costPerClick ?? 0;
            
            $row['acos_L30'] = ($matchedCampaignL30 && ($matchedCampaignL30->sales30d ?? 0) > 0)
                ? round(($matchedCampaignL30->spend / $matchedCampaignL30->sales30d) * 100, 2)
                : null;

            $row['clicks_L30'] = $matchedCampaignL30->clicks ?? 0;
            $row['spend_L30'] = $matchedCampaignL30->spend ?? 0;
            $row['sales_L30'] = $matchedCampaignL30->sales30d ?? 0;
            $row['sold_L30'] = $matchedCampaignL30->unitsSoldSameSku30d ?? 0;

            $row['NRL']  = '';
            $row['NRA'] = '';
            $row['FBA'] = '';
            if (isset($nrValues[$pm->sku])) {
                $raw = $nrValues[$pm->sku];
                if (!is_array($raw)) {
                    $raw = json_decode($raw, true);
                }
                if (is_array($raw)) {
                    $row['NRL']  = $raw['NRL'] ?? null;
                    $row['NRA'] = $raw['NRA'] ?? null;
                    $row['FBA'] = $raw['FBA'] ?? null;
                }
            }

            $result[] = (object) $row;
        }

        return response()->json([
            'message' => 'Data fetched successfully',
            'data'    => $result,
            'status'  => 200,
        ]);
    }
}
