<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FbaInventory;

class FetchFbaInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-fba-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch inventory data from Amazon SP-API for FBA products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accessToken = $this->getAccessToken();
        $marketplaceId = env('SPAPI_MARKETPLACE_ID');

        $fbas = FbaInventory::whereNotNull('sku')->get();

        $this->info('Fetching inventory for ' . $fbas->count() . ' FBA products.');

        foreach ($fbas as $fba) {
            $this->fetchInventoryForSku($accessToken, $marketplaceId, $fba->sku, $fba);
            sleep(1); // rate limit
        }

        $this->info('FBA inventory updated.');
    }

    private function getInventoryData()
    {
        $accessToken = $this->getAccessToken();
        $marketplaceId = env('SPAPI_MARKETPLACE_ID');

        // Create report
        $response = Http::withHeaders([
            'x-amz-access-token' => $accessToken,
        ])->post('https://sellingpartnerapi-na.amazon.com/reports/2021-06-30/reports', [
            'reportType' => 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA',
            'marketplaceIds' => [$marketplaceId],
        ]);

        $reportId = $response['reportId'] ?? null;
        if (!$reportId) {
            $this->error('Failed to create inventory report.');
            return;
        }

        // Wait for report
        do {
            sleep(15);
            $status = Http::withHeaders([
                'x-amz-access-token' => $accessToken,
            ])->get("https://sellingpartnerapi-na.amazon.com/reports/2021-06-30/reports/{$reportId}");
            $processingStatus = $status['processingStatus'] ?? 'UNKNOWN';
            $this->info("Waiting for inventory report... Status: $processingStatus");
        } while ($processingStatus !== 'DONE');

        // Download
        $documentId = $status['reportDocumentId'];
        $doc = Http::withHeaders([
            'x-amz-access-token' => $accessToken,
        ])->get("https://sellingpartnerapi-na.amazon.com/reports/2021-06-30/documents/{$documentId}");

        $url = $doc['url'] ?? null;
        if (!$url) {
            $this->error('Document URL not found.');
            return;
        }

        // Parse
        $csv = file_get_contents($url);
        $lines = explode("\n", $csv);
        $headers = str_getcsv(array_shift($lines));

        $this->info('Headers: ' . implode(',', $headers));

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $row = str_getcsv($line);
            $this->info('Row count: ' . count($row) . ' for SKU: ' . ($row[0] ?? 'unknown'));
            if (count($row) != count($headers)) {
                $this->warn('Skipping row with mismatched columns');
                continue;
            }

            $data = array_combine($headers, $row);

            $sku = $data['sku'] ?? null;
            $totalQuantity = $data['total_quantity'] ?? 0;

            if ($sku) {
                FbaInventory::where('sku', $sku)->update([
                    'total_quantity' => $totalQuantity,
                    // Add other fields if available
                ]);
                $this->info("Updated inventory for SKU: $sku - Total: $totalQuantity");
            }
        }
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

    private function fetchInventoryForSku($accessToken, $marketplaceId, $sku, $fba)
    {
        $response = Http::withHeaders([
            'x-amz-access-token' => $accessToken,
        ])->get('https://sellingpartnerapi-na.amazon.com/fba/inventory/v1/summaries', [
            'marketplaceId' => $marketplaceId,
            'granularityType' => 'SKU',
            'granularityId' => $sku,
            'details' => 'true',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $summaries = $data['payload']['inventorySummaries'] ?? [];

            if (!empty($summaries)) {
                $summary = $summaries[0];
                $fba->update([
                    'total_quantity' => $summary['totalQuantity'] ?? 0,
                    'sellable_quantity' => $summary['sellableQuantity'] ?? 0,
                    'unsellable_quantity' => $summary['unsellableQuantity'] ?? 0,
                    'reserved_quantity' => $summary['reservedQuantity'] ?? 0,
                    'inbound_quantity' => ($summary['inboundWorkingQuantity'] ?? 0) + ($summary['inboundShippedQuantity'] ?? 0) + ($summary['inboundReceivingQuantity'] ?? 0),
                ]);
                $this->info("Updated inventory for SKU: $sku");
            } else {
                $this->warn("No inventory data for SKU: $sku");
            }
        } else {
            $this->error("Failed to fetch inventory for SKU: $sku - " . $response->status());
        }
    }
}
