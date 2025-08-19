<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WayfairApiService
{
    protected $token;

    public function __construct()
    {
        $this->authenticate();
    }

    /**
     * Authenticate with Wayfair and get access token
     */
    protected function authenticate()
    {
        $response = Http::asForm()->post('https://sso.auth.wayfair.com/oauth/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => env('WAYFAIR_CLIENT_ID'),
            'client_secret' => env('WAYFAIR_CLIENT_SECRET'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to authenticate with Wayfair API: ' . $response->body());
        }

        return $response->json('access_token');
    }

    public function updatePrice(string $sku, float $price)
    {
        // Build XML for pricing feed
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<PriceFeed xmlns="http://api.wayfair.com/v1/pricefeed.xsd">
    <Price>
        <SupplierPartNumber>{$sku}</SupplierPartNumber>
        <PriceAmount>{$price}</PriceAmount>
        <CurrencyCode>USD</CurrencyCode>
    </Price>
</PriceFeed>
XML;

        $response = Http::withToken($this->authenticate())
            ->attach('file', $xml, 'price_feed.xml')
            ->post('https://api.wayfair.com/v1/feeds/pricing');

        return $response->json();
    }
}
