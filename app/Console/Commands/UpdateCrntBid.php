<?php

namespace App\Console\Commands;

use App\Models\AmazonSbCampaignReport;
use Illuminate\Console\Command;
use App\Models\AmazonSpCampaignReport;
use Illuminate\Support\Facades\DB;

class UpdateCampaignBid extends Command
{
    protected $signature = 'update:campaign-bid';
    protected $description = 'Update currentSpBidPrice from SBID for approved campaigns at 12 PM';

    public function handle()
    {
        $updatedSp = AmazonSpCampaignReport::where('ad_type', 'SPONSORED_PRODUCTS')
            ->where('apprSbid', 'approved')
            ->whereIn('report_date_range', ['L7', 'L1'])
            ->update([
                'currentSpBidPrice' => DB::raw('sbid'),
                'sbid' => DB::raw('sbid * 0.9')     
            ]);

        $updatedSb = AmazonSbCampaignReport::where('ad_type', 'SPONSORED_BRANDS')
            ->where('apprSbid', 'approved')
            ->whereIn('report_date_range', ['L7', 'L1'])
            ->update([
                'currentSbBidPrice' => DB::raw('sbid'),
                'sbid' => DB::raw('sbid * 0.9')     
            ]);

        $this->info("{$updatedSp} campaigns updated successfully.");
        $this->info("{$updatedSb} campaigns updated successfully.");
    }
}
