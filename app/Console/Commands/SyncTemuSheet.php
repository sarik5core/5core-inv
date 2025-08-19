<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ApiController;
use App\Models\TemuProductSheet;

class SyncTemuSheet extends Command
{
    protected $signature = 'sync:temu-sheet';
    protected $description = 'Sync Temu Product Sheet';

    public function handle()
    {
        $controller = new ApiController();
        $sheet = $controller->fetchDataFromTemuListingDataSheet();
        $rows = collect($sheet->getData()->data ?? []);

        foreach ($rows as $row) {
            $sku = trim($row->{'(Child) sku'} ?? '');
            if (!$sku) continue;

            TemuProductSheet::updateOrCreate(
                ['sku' => $sku],
                [
                    'price'     => $this->toDecimalOrNull($row->{'R prc'} ?? null),
                    'pft'       => $this->toDecimalOrNull($row->{'Pft%'} ?? null),
                    'roi'       => $this->toDecimalOrNull($row->{'Roi%'} ?? null),
                    'l30'       => $this->toIntOrNull($row->{' T L30'} ?? null),
                    'dil'       => $this->toDecimalOrNull($row->{'Dil%'} ?? null),
                    'buy_link'  => trim($row->{'Buyer Link'} ?? ''),
                ]
            );
        }

        $this->info('Temu sheet synced successfully!');
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
