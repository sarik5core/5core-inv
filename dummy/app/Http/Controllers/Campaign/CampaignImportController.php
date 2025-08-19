<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessCampaignCsvChunk;
use App\Models\Campaign;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // <-- Add this

class CampaignImportController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::all();
        return view('campaign.campaign', compact('campaigns'));
    }

    private function cleanNumber($value)
    {
        if ($value === null) return null;
        // Remove $ and commas, then convert to float if possible
        $clean = str_replace(['$', ','], '', trim($value));
        return is_numeric($clean) ? $clean : null;
    }

    public function upload(Request $request)
    {
        Log::info('Campaign CSV upload started.');

        if (!$request->hasFile('csv_file')) {
            Log::error('No file uploaded.');
            return back()->with('error', 'No file uploaded.');
        }

        $file = $request->file('csv_file');
        Log::info('File received for import.', ['filename' => $file->getClientOriginalName()]);

        $rowCount = 0;
        $errorCount = 0;

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $header = fgetcsv($handle);

            // Normalize header to snake_case for DB columns
            $normalizedHeader = array_map(function ($h) {
                $h = preg_replace('/[^A-Za-z0-9 ]/', '', $h); // Remove special chars
                $h = strtolower(str_replace(' ', '_', $h));
                return $h;
            }, $header);

            Log::info('CSV header read.', ['header' => $normalizedHeader]);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rowCount++;
                if (count($data) != count($normalizedHeader)) {
                    $errorCount++;
                    Log::error('Row import failed: column count mismatch', [
                        'row' => $rowCount,
                        'data' => $data
                    ]);
                    continue;
                }
                try {
                    $rowData = array_combine($normalizedHeader, $data);

                    // Parse dates robustly
                    $startDate = $this->parseDate($rowData['start_date'] ?? null);
                    $endDate = $this->parseDate($rowData['end_date'] ?? null);

                    DB::table('campaigns')->insert([
                        'state' => $rowData['state'] ?? null,
                        'campaigns' => $rowData['campaigns'] ?? null,
                        'country' => $rowData['country'] ?? null,
                        'status' => $rowData['status'] ?? null,
                        'type' => $rowData['type'] ?? null,
                        'targeting' => $rowData['targeting'] ?? null,
                        'retailer' => $rowData['retailer'] ?? null,
                        'portfolio' => $rowData['portfolio'] ?? null,
                        'campaign_bidding_strategy' => $rowData['campaign_bidding_strategy'] ?? null,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'budget_converted' => $this->cleanNumber($rowData['budget_converted'] ?? null),
                        'budget' => $this->cleanNumber($rowData['budget'] ?? null),
                        'cost_type' => $rowData['cost_type'] ?? null,
                        'impressions' => $this->cleanNumber($rowData['impressions'] ?? null),
                        'top_of_search_impression_share' => $rowData['topofsearch_impression_share'] ?? null,
                        'top_of_search_bid_adjustment' => $this->cleanNumber($rowData['topofsearch_bid_adjustment'] ?? null),
                        'clicks' => $this->cleanNumber($rowData['clicks'] ?? null),
                        'ctr' => $this->cleanNumber($rowData['ctr'] ?? null),
                        'spend_converted' => $this->cleanNumber($rowData['spend_converted'] ?? null),
                        'spend' => $this->cleanNumber($rowData['spend'] ?? null),
                        'cpc_converted' => $this->cleanNumber($rowData['cpc_converted'] ?? null),
                        'cpc' => $this->cleanNumber($rowData['cpc'] ?? null),
                        'detail_page_views' => $this->cleanNumber($rowData['detail_page_views'] ?? null),
                        'orders' => $this->cleanNumber($rowData['orders'] ?? null),
                        'sales_converted' => $this->cleanNumber($rowData['sales_converted'] ?? null),
                        'sales' => $this->cleanNumber($rowData['sales'] ?? null),
                        'acos' => $this->cleanNumber($rowData['acos'] ?? null),
                        'roas' => $this->cleanNumber($rowData['roas'] ?? null),
                        'ntb_orders' => $this->cleanNumber($rowData['ntb_orders'] ?? null),
                        'percent_orders_ntb' => $this->cleanNumber($rowData['of_orders_ntb'] ?? null),
                        'ntb_sales_converted' => $this->cleanNumber($rowData['ntb_sales_converted'] ?? null),
                        'ntb_sales' => $this->cleanNumber($rowData['ntb_sales'] ?? null),
                        'percent_sales_ntb' => $this->cleanNumber($rowData['of_sales_ntb'] ?? null),
                        'long_term_sales_converted' => $this->cleanNumber($rowData['longterm_sales_converted'] ?? null),
                        'long_term_sales' => $this->cleanNumber($rowData['longterm_sales'] ?? null),
                        'long_term_roas' => $this->cleanNumber($rowData['longterm_roas'] ?? null),
                        'cumulative_reach' => $this->cleanNumber($rowData['cumulative_reach'] ?? null),
                        'household_reach' => $this->cleanNumber($rowData['household_reach'] ?? null),
                        'viewable_impressions' => $this->cleanNumber($rowData['viewable_impressions'] ?? null),
                        'cpm_converted' => $this->cleanNumber($rowData['cpm_converted'] ?? null),
                        'cpm' => $this->cleanNumber($rowData['cpm'] ?? null),
                        'vcpm_converted' => $this->cleanNumber($rowData['vcpm_converted'] ?? null),
                        'vcpm' => $this->cleanNumber($rowData['vcpm'] ?? null),
                        'video_first_quartile' => $this->cleanNumber($rowData['video_first_quartile'] ?? null),
                        'video_midpoint' => $this->cleanNumber($rowData['video_midpoint'] ?? null),
                        'video_third_quartile' => $this->cleanNumber($rowData['video_third_quartile'] ?? null),
                        'video_complete' => $this->cleanNumber($rowData['video_complete'] ?? null),
                        'video_unmute' => $this->cleanNumber($rowData['video_unmute'] ?? null),
                        'vtr' => $this->cleanNumber($rowData['vtr'] ?? null),
                        'vctr' => $this->cleanNumber($rowData['vctr'] ?? null),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info('Row imported', ['row' => $rowCount]);
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Row import failed', [
                        'row' => $rowCount,
                        'error' => $e->getMessage(),
                        'data' => $data
                    ]);
                }
            }
            fclose($handle);
        }

        Log::info('Campaign CSV upload finished.', [
            'total_rows' => $rowCount,
            'errors' => $errorCount
        ]);

        return back()->with('success', 'CSV imported successfully!');
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        $date = trim($date);
        foreach (['m/d/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed) return $parsed->format('Y-m-d');
        }
        return null;
    }
}
