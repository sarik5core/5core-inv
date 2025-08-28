<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\FetchReverbData;
use App\Console\Commands\FetchMacyProducts;
use App\Console\Commands\FetchWayfairData;
use App\Console\Commands\LogClear;
use App\Console\Commands\SyncTemuSheet;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        FetchReverbData::class,
        FetchMacyProducts::class,
        FetchWayfairData::class,
        \App\Console\Commands\LogClear::class,
        \App\Console\Commands\SyncTemuSheet::class,
        \App\Console\Commands\UpdateCampaignBid::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Test scheduler to verify it's working
        $schedule->call(function () {
            Log::info('Test scheduler is working at ' . now());
        })->everyMinute()->name('test-scheduler-log');

        // Clear Laravel log after test log
        $schedule->call(function () {
            $logPath = storage_path('logs/laravel.log');
            if (file_exists($logPath)) {
                file_put_contents($logPath, '');
            }
        })->everyFiveMinutes()->name('clear-laravel-log');

        // All commands running every 5 minutes
        $schedule->command('shopify:save-daily-inventory')
            ->everyFiveMinutes()
            ->timezone('UTC');
        $schedule->command('app:process-jungle-scout-sheet-data')
            ->dailyAt('00:30')
            ->timezone('America/Los_Angeles');
        $schedule->command('app:fetch-amazon-listings')
            ->dailyAt('06:00')
            ->timezone('America/Los_Angeles');
        $schedule->command('reverb:fetch')
            ->everyFiveMinutes()
            ->timezone('UTC');
        $schedule->command('app:fetch-ebay-reports')
            ->hourly()
            ->timezone('UTC');
        $schedule->command('app:fetch-macy-products')
            ->everyFiveMinutes()
            ->timezone('UTC');
        $schedule->command('app:fetch-wayfair-data')
            ->everyFiveMinutes()
            ->timezone('UTC');
        $schedule->command('app:amazon-campaign-reports')
            ->dailyAt('04:00')
            ->timezone('UTC');
        $schedule->command('app:ebay-campaign-reports')
            ->dailyAt('05:00')
            ->timezone('UTC');
        $schedule->command('app:fetch-doba-metrics')
            ->dailyAt('00:00')
            ->timezone('UTC');

        // Sync Main sheet update command
        $schedule->command('app:sync-sheet')
            ->dailyAt('02:10')
            ->timezone('UTC');
        // Sync Temu sheet command
        $schedule->command('sync:temu-sheet')->everyTenMinutes();
        // Sync Newegg sheet command
        $schedule->command('sync:neweegg-sheet')->everyTenMinutes();
        // Sync Wayfair sheet command
        $schedule->command('sync:wayfair-sheet')->everyTenMinutes();

        // Sync Walmart sheet command
        $schedule->command('sync:walmart-sheet')->everyTenMinutes();

        // Sync eBay 2 sheet command
        $schedule->command('sync:ebay-two-sheet')->everyTenMinutes();
        // Sync eBay 3 sheet command
        $schedule->command('sync:ebay-three-sheet')->everyTenMinutes();

        // Sync Shopify sheet command
        $schedule->command('sync:shopify-quantity')->everyTenMinutes()
            ->timezone('UTC');        
        $schedule->command('app:fetch-ebay3-metrics')
            ->dailyAt('02:00')
            ->timezone('America/Los_Angeles');
        $schedule->command('app:ebay3-campaign-reports')
            ->dailyAt('04:00')
            ->timezone('America/Los_Angeles');
        $schedule->command('app:fetch-temu-metrics')
            ->dailyAt('03:00')
            ->timezone('America/Los_Angeles');
        $schedule->command('app:fetch-ebay2-metrics')
            ->dailyAt('01:00')
            ->timezone('America/Los_Angeles');
        $schedule->command('app:ebay2-campaign-reports')
            ->dailyAt('01:15')
            ->timezone('America/Los_Angeles');
       $schedule->command('update:campaign-bid')
            ->dailyAt('23:00')
            ->timezone('Asia/Kolkata');
        $schedule->command('sync:amazon-prices')->everyMinute();
         $schedule->command('sync:ebay-prices')->everyMinute();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
