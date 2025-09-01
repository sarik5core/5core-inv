<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEbayPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ebay-prices';
    protected $description = 'One-time sync of prices from repricer.lmp_data to 5coreinventory.ebay_metrics';


    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Subquery to get lowest non-zero price per SKU
            $subQuery = DB::table('5core_repricer.lmp_data')
                ->select('sku', DB::raw('MIN(price) as price'))
                ->where('price', '>', 0)
                ->groupBy('sku');

            $subQuery = DB::table('5core_repricer.lmp_data')
                ->select('sku', DB::raw('MIN(price) as price'))
                ->groupBy('sku');

            $updated = DB::table('5coreinventory.ebay_metrics as a')
                ->joinSub($subQuery, 'l', function ($join) {
                    $join->on('a.sku', '=', 'l.sku');
                })
                ->update([
                    'price_lmpa' => DB::raw('l.price'),
                    'updated_at' => now(),
                ]);



            if ($updated) {
                $this->info("✅ {$updated} rows updated successfully.");
            } else {
                $this->warn("⚠️ No rows updated, prices already in sync.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Error syncing prices: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
