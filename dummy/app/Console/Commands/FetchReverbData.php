<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ReverbProduct;
use Carbon\Carbon;

class FetchReverbData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Reverb listing data and store in database daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching Reverb Listings...');
        $listings = $this->fetchAllListings();

        $rL30 = $this->getOrderQuantities(30);
        $rL60 = $this->getOrderQuantities(60);

        foreach ($listings as $item) {
            $sku = $item['sku'] ?? null;

            if (!$sku) {
                $this->warn("Skipping missing SKU or ID");
                continue;
            }

            $r30 = $rL30[$sku] ?? 0;
            $r60 = $rL60[$sku] ?? 0;

            $this->line("Listing SKU: $sku | R_L30: $r30 | R_L60: $r60");

            // Store record
            ReverbProduct::updateOrCreate(
            ['sku' => $sku], // Match on SKU
            [
                'sku' => $sku,
                'r_l30' => $r30,
                'r_l60' => $r60,
                'price' => $item['price']['amount'] ?? null,
                'views' => $item['stats']['views'] ?? null,
            ]);
        }

        $this->info('Reverb data stored successfully.');
    }

    protected function fetchAllListings(): array
    {
        $listings = [];
        $url = 'https://api.reverb.com/api/my/listings';

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.reverb.token'),
                'Accept' => 'application/hal+json',
                'Accept-Version' => '3.0',
            ])->get($url);

            if ($response->failed()) {
                $this->error('Failed to fetch listings.');
                break;
            }

            $data = $response->json();
            $listings = array_merge($listings, $data['listings'] ?? []);
            $url = $data['_links']['next']['href'] ?? null;

        } while ($url);

        $this->info('Fetched total listings: ' . count($listings));
        return $listings;
    }

    protected function getOrderQuantities($days): array
    {
        $this->info("Fetching orders for last {$days} days...");
        $since = now()->subDays($days)->toIso8601String();
        $url = "https://api.reverb.com/api/my/orders/selling/all?updated_start_date={$since}";
        $quantityMap = [];

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.reverb.token'),
                'Accept' => 'application/hal+json',
                'Accept-Version' => '3.0',
            ])->get($url);

            if ($response->failed()) {
                $this->error("Failed to fetch orders for R_L{$days}");
                return [];
            }

            $orders = $response->json()['orders'] ?? [];
            foreach ($orders as $order) {
                $sku = $order['sku'] ?? null;
                $qty = $order['quantity'] ?? 0;

                if ($sku) {
                    $this->line("Order SKU: $sku, Qty: $qty");
                    $quantityMap[$sku] = ($quantityMap[$sku] ?? 0) + $qty;
                }
            }

            $url = $response->json()['_links']['next']['href'] ?? null;
        } while ($url);

        $this->info("R_L{$days} order quantities calculated.");
        return $quantityMap;
    }

}
