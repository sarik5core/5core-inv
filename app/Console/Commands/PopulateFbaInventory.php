<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopifySku;
use App\Models\AmazonDatasheet;
use App\Models\FbaInventory;

class PopulateFbaInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-fba-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate FBA inventory table with Amazon data for FBA SKUs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting FBA inventory population...');

        // Get all FBA SKUs from ShopifySku table
        $fbaSkus = ShopifySku::where('sku', 'like', '%FBA%')->pluck('sku')->toArray();

        $this->info("Found " . count($fbaSkus) . " FBA SKUs in database");

        // Convert FBA SKUs to base SKUs (remove " FBA" suffix)
        $baseSkus = array_map(function($sku) {
            return trim(str_replace(' FBA', '', $sku));
        }, $fbaSkus);

        $this->info("Checking " . count($baseSkus) . " base SKUs in Amazon data...");

        // Get Amazon data for these base SKUs
        $amazonData = AmazonDatasheet::whereIn('sku', $baseSkus)->get();

        $this->info("Found " . count($amazonData) . " matching SKUs in Amazon data");

        $inserted = 0;
        $updated = 0;

        foreach ($amazonData as $amazonItem) {
            // Find the original FBA SKU
            $baseSku = $amazonItem->sku;
            $originalFbaSku = $baseSku . ' FBA';

            // Check if this FBA SKU exists in our list
            if (in_array($originalFbaSku, $fbaSkus)) {
                $data = [
                    'sku' => $baseSku,
                    'asin' => $amazonItem->asin,
                    'price' => $amazonItem->price,
                    'units_ordered_l30' => $amazonItem->units_ordered_l30 ?? 0,
                    'sessions_l30' => $amazonItem->sessions_l30 ?? 0,
                    'units_ordered_l60' => $amazonItem->units_ordered_l60 ?? 0,
                    'sessions_l60' => $amazonItem->sessions_l60 ?? 0,
                    'original_fba_sku' => $originalFbaSku,
                ];

                // Update or create record
                $existing = FbaInventory::where('sku', $baseSku)->first();

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    FbaInventory::create($data);
                    $inserted++;
                }
            }
        }

        $this->info("✅ FBA inventory population completed!");
        $this->info("📊 Summary:");
        $this->info("   - Records inserted: $inserted");
        $this->info("   - Records updated: $updated");
        $this->info("   - Total FBA SKUs processed: " . count($amazonData));
    }
}
