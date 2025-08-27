<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncAmazonPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run with: php artisan sync:amazon-prices
     */
    protected $signature = 'sync:amazon-prices';

    /**
     * The console command description.
     */
    protected $description = 'One-time sync of prices from repricer.lmpa_data to 5coreinventory.amazon_datsheets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $sql = <<<SQL
                UPDATE `5coreinventory`.`amazon_datsheets` a
                JOIN `5core_repricer`.`lmpa_data` l ON a.`sku` = l.`sku`
                SET a.`price_lmpa` = l.`price`,
                    a.`updated_at` = NOW()
                WHERE (a.`price_lmpa` <> l.`price`)
                   OR (a.`price_lmpa` IS NULL AND l.`price` IS NOT NULL)
                   OR (a.`price_lmpa` IS NOT NULL AND l.`price` IS NULL)
            SQL;

            $updated = DB::statement($sql);

            $this->info("✅ Price_lmpa synced successfully. Rows updated: {$updated}");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error syncing prices: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
