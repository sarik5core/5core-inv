<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;
use App\Models\ShopifySku;
use App\Models\AmazonDatasheet;
use App\Models\FbaInventory;

class PopulateFbaInventory extends Command
{
    protected $signature = 'app:populate-fba-inventory';
    protected $description = 'Populate FBA inventory table with Amazon data for FBA SKUs';

    public function handle()
    {
        $this->info('Starting FBA inventory population...');

        $fbaSkus = ShopifySku::where('sku', 'like', '%FBA%')->pluck('sku')->toArray();
        $this->info("Found " . count($fbaSkus) . " FBA SKUs in database");

        $baseSkus = array_map(fn($sku) => trim(str_replace(' FBA', '', $sku)), $fbaSkus);
        $this->info("Checking " . count($baseSkus) . " base SKUs in Amazon data...");

        $amazonData = AmazonDatasheet::whereIn('sku', $baseSkus)->get();
        $this->info("Found " . count($amazonData) . " matching SKUs in Amazon data");

        $inserted = 0;
        $updated = 0;

        foreach ($amazonData as $amazonItem) {
            $baseSku = $amazonItem->sku;
            $originalFbaSku = $baseSku . ' FBA';

            if (!in_array($originalFbaSku, $fbaSkus)) {
                continue;
            }

            // ✅ Fetch BuyBox / ListPrice from Amazon SP-API
            $accessToken = $this->getAccessToken();
            $marketplaceId = env('SPAPI_MARKETPLACE_ID');

            $response = Http::withHeaders([
                'x-amz-access-token' => $accessToken,
            ])->get("https://sellingpartnerapi-na.amazon.com/products/pricing/v0/items/{$amazonItem->asin}/offers", [
                'MarketplaceId' => $marketplaceId,
                'ItemCondition' => 'New',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $buyBoxPrice = $data['payload']['Summary']['ListPrice']['Amount'] ?? $data['payload']['Summary']['BuyBoxPrices'][0]['LandedPrice']['Amount'] ?? 0;
            } else {
                $buyBoxPrice = 0;
                $this->error("Failed to fetch pricing for ASIN {$amazonItem->asin}: " . $response->status());
            }

            $data = [
                'sku' => $baseSku,
                'asin' => $amazonItem->asin,
                'price' => $amazonItem->price ?? 0, // Listing price
                'buy_box_price' => $buyBoxPrice,
                'units_ordered_l30' => $amazonItem->units_ordered_l30 ?? 0,
                'sessions_l30' => $amazonItem->sessions_l30 ?? 0,
                'units_ordered_l60' => $amazonItem->units_ordered_l60 ?? 0,
                'sessions_l60' => $amazonItem->sessions_l60 ?? 0,
                'original_fba_sku' => $originalFbaSku,
            ];

            $existing = FbaInventory::where('sku', $baseSku)->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                FbaInventory::create($data);
                $inserted++;
            }
        }

        $this->info("✅ FBA inventory population completed!");
        $this->info("📊 Summary:");
        $this->info("   - Records inserted: $inserted");
        $this->info("   - Records updated: $updated");
        $this->info("   - Total FBA SKUs processed: " . count($amazonData));
    }

    private function getAccessToken()
    {
        $res = Http::asForm()->post('https://api.amazon.com/auth/o2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => env('SPAPI_REFRESH_TOKEN'),
            'client_id' => env('SPAPI_CLIENT_ID'),
            'client_secret' => env('SPAPI_CLIENT_SECRET'),
        ]);

        return $res['access_token'] ?? null;
    }
}
