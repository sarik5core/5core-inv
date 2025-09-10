<?php

namespace App\Console\Commands;

use App\Http\Controllers\Campaigns\AmazonSpBudgetController;
use App\Http\Controllers\MarketPlace\ACOSControl\AmazonACOSController;
use App\Models\AmazonDatasheet;
use App\Models\AmazonDataView;
use App\Models\AmazonSbCampaignReport;
use Illuminate\Console\Command;
use App\Models\AmazonSpCampaignReport;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AutoUpdateAmazonBgtHl extends Command
{
    protected $signature = 'amazon:auto-update-amz-bgt-hl';
    protected $description = 'Automatically update Amazon campaign bgt price';

    protected $profileId;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info("Starting Amazon bgts auto-update...");

        $updateKwBgts = new AmazonACOSController;

        $campaigns = $this->amazonAcosKwControlData();

        if (empty($campaigns)) {
            $this->warn("No campaigns matched filter conditions.");
            return 0;
        }

        $campaignIds = collect($campaigns)->pluck('campaign_id')->toArray();
        $newBgts = collect($campaigns)->pluck('sbgt')->toArray();

        $result = $updateKwBgts->updateAutoAmazonSbCampaignBgt($campaignIds, $newBgts);
        $this->info("Update Result: " . json_encode($result));

    }

    public function amazonAcosKwControlData()
    {
        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();
        
        $amazonSpCampaignReportsL30 = AmazonSbCampaignReport::where('ad_type', 'SPONSORED_BRANDS')
            ->where('report_date_range', 'L30')
            ->where(function ($q) use ($skus) {
                foreach ($skus as $sku) {
                    $q->orWhere('campaignName', 'LIKE', '%' . strtoupper($sku) . '%');
                }
            })
            ->get();

        $result = [];

        foreach ($productMasters as $pm) {
            $sku = strtoupper($pm->sku);

            $matchedCampaignL30 = $amazonSpCampaignReportsL30->first(function ($item) use ($sku) {
                $cleanName = strtoupper(trim($item->campaignName));
                $expected1 = $sku;                
                $expected2 = $sku . ' HEAD';      

                return ($cleanName === $expected1 || $cleanName === $expected2)
                    && strtoupper($item->campaignStatus) === 'ENABLED';
            });

            if (!$matchedCampaignL30) {
                continue;
            }

            // clicks must be >= 25
            if (($matchedCampaignL30->clicks ?? 0) < 25) {
                continue;
            }

            $row = [];
            $row['campaign_id'] = $matchedCampaignL30->campaign_id ?? '';
            $row['campaign_name'] = $matchedCampaignL30->campaignName ?? '';
            $row['acos_L30'] = ($matchedCampaignL30 && ($matchedCampaignL30->sales ?? 0) > 0)
                ? round(($matchedCampaignL30->cost / $matchedCampaignL30->sales) * 100, 2)
                : 0;

            $acos = (float) ($row['acos_L30'] ?? 0);

            if ($acos > 0) {
                if ($acos >= 100) {
                    $row['sbgt'] = 1;
                } elseif ($acos >= 50 && $acos <= 100) {
                    $row['sbgt'] = 2;
                } elseif ($acos >= 40 && $acos <= 50) {
                    $row['sbgt'] = 3;
                } elseif ($acos >= 35 && $acos <= 40) {
                    $row['sbgt'] = 4;
                } elseif ($acos >= 30 && $acos <= 35) {
                    $row['sbgt'] = 5;
                } elseif ($acos >= 25 && $acos <= 30) {
                    $row['sbgt'] = 6;
                } elseif ($acos >= 20 && $acos <= 25) {
                    $row['sbgt'] = 7;
                } elseif ($acos >= 15 && $acos <= 20) {
                    $row['sbgt'] = 8;
                } elseif ($acos >= 10 && $acos <= 15) {
                    $row['sbgt'] = 9;
                } elseif ($acos < 10 && $acos > 0) {
                    $row['sbgt'] = 10;
                } else {
                    $row['sbgt'] = 3;
                }
            }else {
                // Skip sbgt assignment if ACOS = 0
                $row['sbgt'] = 0;
            }

            $result[] = (object) $row;
        }

        return $result;
    }
}