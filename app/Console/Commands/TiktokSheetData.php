<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApiController;
use App\Models\TiktokSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TiktokSheetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
   protected $signature = 'sync:tiktok-sheet-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync TikTok product sheet data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://script.google.com/macros/s/AKfycbyj1Z0xGDKHOWZvqj1fdnBi02abq67NzwBc7fj0XckA9O3zGbZOyHnLLDXuOPnTLC3E/exec';

        try {
            $response = Http::timeout(seconds: 120)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                $this->info('Fetched data: ' . json_encode($data));
                $rows = collect($data['data'] ?? $data ?? []);
                $this->info('Rows count: ' . $rows->count());
            } else {
                $this->error('Failed to fetch data from Google Sheet. Status: ' . $response->status());
                return;
            }
        } catch (\Exception $e) {
            $this->error('Exception while fetching data: ' . $e->getMessage());
            return;
        }

        foreach ($rows as $row) {
            $sku = trim($row['SKU'] ?? '');
            if (!$sku) continue;

            TiktokSheet::updateOrCreate(
                ['sku' => $sku],
                [
                    'price'     => $this->toDecimalOrNull($row['live price '] ?? null),
                    'l30'       => $this->toIntOrNull($row['L30'] ?? null),
                    'l60'       => $this->toIntOrNull($row['TL60'] ?? null),
                    'views'       => $this->toDecimalOrNull($row['P Views'] ?? null),
                   
                ]
            );
        }

        $this->info('tiktok sheet data synced successfully!');
    }

    private function toDecimalOrNull($value)
    {
        return is_numeric($value) ? round((float)$value, 2) : null;
    }

    private function toIntOrNull($value)
    {
        if ($value === null || $value === '') return null;
        $value = str_replace(',', '', $value);
        return is_numeric($value) ? (int)$value : null;
    }
}
