<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\MacyProduct;
use Carbon\Carbon;


class FetchMacyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-macy-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store Macy products data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = $this->getAccessToken();
        if (!$token) return;

        // Step 1: Mass-fetch all orders once
        $skuSales = $this->getSalesTotals($token); // ['sku' => ['m_l30' => 12, 'm_l60' => 4]]

        // Step 2: Paginate through products
        $pageToken = null;
        $page = 1;

        do {
            $this->info("Fetching product page $page...");
            $url = 'https://miraklconnect.com/api/products?limit=1000';
            if ($pageToken) {
                $url .= '&page_token=' . urlencode($pageToken);
            }

            $response = Http::withToken($token)->get($url);
            if (!$response->successful()) {
                $this->error('Product fetch failed: ' . $response->body());
                return;
            }

            $json = $response->json();
            $products = $json['data'] ?? [];
            $pageToken = $json['next_page_token'] ?? null;

            foreach ($products as $product) {
                $sku = $product['id'] ?? null;
                $price = $product['discount_prices'][0]['price']['amount'] ?? null;

                if (!$sku || $price === null) continue;

                $m_l30 = $skuSales[$sku]['m_l30'] ?? 0;
                $m_l60 = $skuSales[$sku]['m_l60'] ?? 0;

                MacyProduct::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'price' => $price,
                        'm_l30' => $m_l30,
                        'm_l60' => $m_l60,
                    ]
                );
            }

            $page++;
        } while ($pageToken);

        $this->info("All Macy products stored successfully");
    }

    private function getAccessToken()
    {
        return Cache::remember('macy_access_token', 3500, function () {
            $response = Http::asForm()->post('https://auth.mirakl.net/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.macy.client_id'),
                'client_secret' => config('services.macy.client_secret'),
            ]);

            return $response->successful()
                ? $response->json()['access_token']
                : null;
        });
    }

    private function getSalesTotals(string $token): array
    {
        $this->info("Fetching all orders in last 60 days...");

        $orders = [];
        $pageToken = null;
        $startDate = now()->subDays(60)->toIso8601String(); // ISO format for query param

        do {
            $url = 'https://miraklconnect.com/api/v2/orders?fulfillment_type=FULFILLED_BY_SELLER&limit=100';
            $url .= '&updated_from=' . urlencode($startDate);
            if ($pageToken) {
                $url .= '&page_token=' . urlencode($pageToken);
            }

            $response = Http::withToken($token)->get($url);
            if (!$response->successful()) {
                $this->error("Order fetch failed: " . $response->body());
                break;
            }

            $json = $response->json();
            $orders = array_merge($orders, $json['data'] ?? []);
            $pageToken = $json['next_page_token'] ?? null;
        } while ($pageToken);

        $this->info("Orders fetched: " . count($orders));

        // Define date ranges
        $now = now();
        $startL30 = $now->copy()->subDays(30);
        $endL30 = $now->copy()->subDay();

        $startL60 = $now->copy()->subDays(60);
        $endL60 = $now->copy()->subDays(31);

        // Initialize sku map
        $sales = [];

        foreach ($orders as $order) {
            $created = Carbon::parse($order['created_at']);

            foreach ($order['order_lines'] ?? [] as $line) {
                $sku = $line['product']['id'] ?? null;
                $qty = $line['quantity'] ?? 0;

                if (!$sku) continue;

                if (!isset($sales[$sku])) {
                    $sales[$sku] = ['m_l30' => 0, 'm_l60' => 0];
                }

                if ($created->between($startL60, $endL60)) {
                    $sales[$sku]['m_l60'] += $qty;
                } elseif ($created->between($startL30, $endL30)) {
                    $sales[$sku]['m_l30'] += $qty;
                }
            }
        }

        return $sales;
    }

}
