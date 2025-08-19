<?php

namespace App\Console\Commands;

use App\Models\WaifairProductSheet;
use Illuminate\Console\Command;
use App\Http\Controllers\ApiController;

class SyncWayfairSheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:wayfair-sheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Wayfair Product Sheet';

    /**
     * Execute the console command.
     */
  

    public function handle()
    {
        $controller = new ApiController();
        $sheet = $controller->fetchDataFromWayfairMasterGoogleSheet();
        $rows = collect($sheet->getData()->data ?? []);

        foreach ($rows as $row) {
            $sku = trim($row->{'(Child) sku'} ?? '');
            if (!$sku) continue;

            WaifairProductSheet::updateOrCreate(
                ['sku' => $sku],
                [
                    'price'     => $this->toDecimalOrNull($row->{'Wayfair Price'} ?? null),
                    'pft'       => $this->toDecimalOrNull($row->{'PFT%'} ?? null),
                    'roi'       => $this->toDecimalOrNull($row->{'ROI%'} ?? null),
                    'l30'       => $this->toIntOrNull($row->{'W L30'} ?? null),
                    'dil'       => $this->toDecimalOrNull($row->{'Dil%'} ?? null),
                    'buy_link'  => trim($row->{'Buyer Link'} ?? ''),
                ]
            );
        }




        $this->info('Wayfair sheet synced successfully!');
    }

    private function toDecimalOrNull($value)
    {
        return is_numeric($value) ? round((float)$value, 2) : null;
    }

    private function toIntOrNull($value)
    {
        return is_numeric($value) ? (int)$value : null;
    }
}
