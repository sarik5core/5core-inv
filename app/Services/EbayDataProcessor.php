<?php

namespace App\Services;

use App\Models\EbayMetric;

class EbayDataProcessor
{
    
    public function calculateAndSave(EbayMetric $metric, float $lp, float $ship, float $percentage)
    {
        $price = $metric->ebay_price ?? 0;
        $units = $metric->ebay_l30 ?? 0;

        $totalPft = ($price * $percentage - $lp - $ship) * $units;
        $tSale = $price * $units;
        $pftPct = $price > 0 ? (($price * $percentage - $lp - $ship) / $price) * 100 : 0;
        $roiPct = $lp > 0 ? (($price * $percentage - $lp - $ship) / $lp) * 100 : 0;
        $tCogs = $lp * $units;

        $metric->total_pft      = round($totalPft, 2);
        $metric->t_sale_l30     = round($tSale, 2);
        $metric->pft_percentage = round($pftPct, 2);
        $metric->roi_percentage = round($roiPct, 2);
        $metric->t_cogs         = round($tCogs, 2);
        logger()->info('Before update', $metric->toArray());
        $metric->save();
        logger()->info('After update', $metric->fresh()->toArray());

    }
}
