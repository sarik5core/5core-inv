<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\EbayApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateEbaySPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $itemId;
    protected $price;

    /**
     * Create a new job instance.
     */
    public function __construct($itemId, $price)
    {
        $this->itemId = $itemId;
        $this->price = $price;
    }

    public function handle(EbayApiService $ebayApiService)
    {
        try {


            $response = $ebayApiService->reviseFixedPriceItem(
                itemId: $this->itemId,
                price: $this->price,
            );

            return $response;
        } catch (\Throwable $e) {

            // Optional: rethrow to retry
            throw $e;
        }
    }
}
