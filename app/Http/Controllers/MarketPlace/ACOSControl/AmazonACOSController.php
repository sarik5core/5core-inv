<?php

namespace App\Http\Controllers\MarketPlace\ACOSControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmazonACOSController extends Controller
{
    public function index(){
        return view('market-places.acos-control.amazon-acos-control');
    }

    public function getAmzonAcOSData()
    {
        $data = DB::table(function($query) {
            $query->select(
                'id', 'campaign_id', 'note', 'sbid', 'yes_sbid', 'profile_id', 'ad_type',
                'report_date_range', 'campaignName', 'clicks', 'cost', 'impressions',
                'startDate', 'endDate', 'sales',
                'campaignBudgetAmount', 'campaignBudgetCurrencyCode', DB::raw("'SB' as source"), 'campaignStatus'
            )
            ->from('amazon_sb_campaign_reports')
            ->unionAll(
                DB::table('amazon_sd_campaign_reports')->select(
                    'id', 'campaign_id', 'note', 'sbid', 'yes_sbid', 'profile_id', 'ad_type',
                    'report_date_range', 'campaignName', 'clicks', 'cost', 'impressions',
                    'startDate', 'endDate', 'sales',
                    DB::raw('NULL as campaignBudgetAmount'), 'campaignBudgetCurrencyCode', DB::raw("'SD' as source"), 'campaignStatus'
                )
            )
            ->unionAll(
                DB::table('amazon_sp_campaign_reports')->select(
                    'id', 'campaign_id', 'note', 'sbid', 'yes_sbid', 'profile_id', 'ad_type',
                    'report_date_range', 'campaignName', 'clicks', 'cost', 'impressions',
                    'startDate', 'endDate', 'sales30d as sales',
                    'campaignBudgetAmount', 'campaignBudgetCurrencyCode', DB::raw("'SP' as source"), 'campaignStatus'
                )
            );
        }, 'base')
        ->leftJoin('campaign_entries as ce', DB::raw('TRIM(base.campaignName)'), '=', DB::raw('TRIM(ce.campaign_name)'))
        ->leftJoin(DB::raw('(SELECT campaign_id, profile_id, source,
                SUM(CASE WHEN report_date_range="L7" THEN clicks ELSE 0 END) as l7_clicks,
                SUM(CASE WHEN report_date_range="L15" THEN clicks ELSE 0 END) as l15_clicks,
                SUM(CASE WHEN report_date_range="L30" THEN clicks ELSE 0 END) as l30_clicks,
                SUM(CASE WHEN report_date_range="L60" THEN clicks ELSE 0 END) as l60_clicks
            FROM (
                SELECT campaign_id, profile_id, report_date_range, clicks, "SB" as source FROM amazon_sb_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, clicks, "SD" as source FROM amazon_sd_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, clicks, "SP" as source FROM amazon_sp_campaign_reports
            ) AS reports
            GROUP BY campaign_id, profile_id, source
        ) as clicks_by_range'), function($join){
            $join->on('base.campaign_id', '=', 'clicks_by_range.campaign_id')
                 ->on('base.profile_id', '=', 'clicks_by_range.profile_id')
                 ->on('base.source', '=', 'clicks_by_range.source');
        })
        ->leftJoin(DB::raw('(SELECT campaign_id, profile_id, source,
                SUM(CASE WHEN report_date_range="L1" THEN cost ELSE 0 END) as l1_spend,
                SUM(CASE WHEN report_date_range="L7" THEN cost ELSE 0 END) as l7_spend,
                SUM(CASE WHEN report_date_range="L15" THEN cost ELSE 0 END) as l15_spend,
                SUM(CASE WHEN report_date_range="L30" THEN cost ELSE 0 END) as l30_spend,
                SUM(CASE WHEN report_date_range="L60" THEN cost ELSE 0 END) as l60_spend
            FROM (
                SELECT campaign_id, profile_id, report_date_range, cost, "SB" as source FROM amazon_sb_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, cost, "SD" as source FROM amazon_sd_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, cost, "SP" as source FROM amazon_sp_campaign_reports
            ) as spend_reports
            GROUP BY campaign_id, profile_id, source
        ) as spend_by_range'), function($join){
            $join->on('base.campaign_id', '=', 'spend_by_range.campaign_id')
                 ->on('base.profile_id', '=', 'spend_by_range.profile_id')
                 ->on('base.source', '=', 'spend_by_range.source');
        })
        ->leftJoin(DB::raw('(SELECT campaign_id, profile_id, source,
                SUM(CASE WHEN report_date_range="L7" THEN sales ELSE 0 END) as l7_sales,
                SUM(CASE WHEN report_date_range="L15" THEN sales ELSE 0 END) as l15_sales,
                SUM(CASE WHEN report_date_range="L30" THEN sales ELSE 0 END) as l30_sales,
                SUM(CASE WHEN report_date_range="L60" THEN sales ELSE 0 END) as l60_sales
            FROM (
                SELECT campaign_id, profile_id, report_date_range, sales, "SB" as source FROM amazon_sb_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, sales, "SD" as source FROM amazon_sd_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, sales30d as sales, "SP" as source FROM amazon_sp_campaign_reports
            ) as sales_reports
            GROUP BY campaign_id, profile_id, source
        ) as sales_by_range'), function($join){
            $join->on('base.campaign_id', '=', 'sales_by_range.campaign_id')
                 ->on('base.profile_id', '=', 'sales_by_range.profile_id')
                 ->on('base.source', '=', 'sales_by_range.source');
        })
        ->leftJoin(DB::raw('(SELECT campaign_id, profile_id, source,
                ROUND(SUM(CASE WHEN report_date_range="L7" THEN cost ELSE 0 END)/NULLIF(SUM(CASE WHEN report_date_range="L7" THEN clicks ELSE 0 END),0),2) as l7_cpc,
                ROUND(SUM(CASE WHEN report_date_range="L15" THEN cost ELSE 0 END)/NULLIF(SUM(CASE WHEN report_date_range="L15" THEN clicks ELSE 0 END),0),2) as l15_cpc,
                ROUND(SUM(CASE WHEN report_date_range="L30" THEN cost ELSE 0 END)/NULLIF(SUM(CASE WHEN report_date_range="L30" THEN clicks ELSE 0 END),0),2) as l30_cpc,
                ROUND(SUM(CASE WHEN report_date_range="L60" THEN cost ELSE 0 END)/NULLIF(SUM(CASE WHEN report_date_range="L60" THEN clicks ELSE 0 END),0),2) as l60_cpc
            FROM (
                SELECT campaign_id, profile_id, report_date_range, clicks, cost, "SB" as source FROM amazon_sb_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, clicks, cost, "SD" as source FROM amazon_sd_campaign_reports
                UNION ALL
                SELECT campaign_id, profile_id, report_date_range, clicks, cost, "SP" as source FROM amazon_sp_campaign_reports
            ) as cpc_reports
            GROUP BY campaign_id, profile_id, source
        ) as cpc_by_range'), function($join){
            $join->on('base.campaign_id', '=', 'cpc_by_range.campaign_id')
                 ->on('base.profile_id', '=', 'cpc_by_range.profile_id')
                 ->on('base.source', '=', 'cpc_by_range.source');
        })
        ->select('base.*', 'ce.parent',
            'clicks_by_range.l7_clicks','clicks_by_range.l15_clicks','clicks_by_range.l30_clicks','clicks_by_range.l60_clicks',
            'spend_by_range.l1_spend','spend_by_range.l7_spend','spend_by_range.l15_spend','spend_by_range.l30_spend','spend_by_range.l60_spend',
            'sales_by_range.l7_sales','sales_by_range.l15_sales','sales_by_range.l30_sales','sales_by_range.l60_sales',
            'cpc_by_range.l7_cpc','cpc_by_range.l15_cpc','cpc_by_range.l30_cpc','cpc_by_range.l60_cpc',
            DB::raw('ROUND(spend_by_range.l7_spend/NULLIF(sales_by_range.l7_sales,0)*100,2) as l7_acos'),
            DB::raw('ROUND(spend_by_range.l15_spend/NULLIF(sales_by_range.l15_sales,0)*100,2) as l15_acos'),
            DB::raw('ROUND(spend_by_range.l30_spend/NULLIF(sales_by_range.l30_sales,0)*100,2) as l30_acos'),
            DB::raw('ROUND(spend_by_range.l60_spend/NULLIF(sales_by_range.l60_sales,0)*100,2) as l60_acos')
        )
        ->get();

        return response()->json($data);
    }
}
