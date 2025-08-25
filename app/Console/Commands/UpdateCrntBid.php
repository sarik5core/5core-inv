<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AmazonSpCampaignReport;
use Illuminate\Support\Facades\DB;

class UpdateCampaignBid extends Command
{
    protected $signature = 'update:campaign-bid';
    protected $description = 'Update currentSpBidPrice from SBID for approved campaigns at 12 PM';

    public function handle()
    {
        $updated = AmazonSpCampaignReport::where('ad_type', 'SPONSORED_PRODUCTS')
            ->where('apprSbid', 'approved')
            ->whereIn('report_date_range', ['L7', 'L1'])
            ->update([
                'currentSpBidPrice' => DB::raw('sbid')
            ]);

        $this->info("{$updated} campaigns updated successfully.");
    }
}
