<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Aws\Signature\SignatureV4;
use Aws\Credentials\Credentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonSpApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $region;
    protected $marketplaceId;
    protected $awsAccessKey;
    protected $awsSecretKey;
    protected $endpoint;

    public function __construct()
    {
        $this->clientId = env('SPAPI_CLIENT_ID');
        $this->clientSecret = env('SPAPI_CLIENT_SECRET');
        $this->refreshToken = env('SPAPI_REFRESH_TOKEN');
        $this->region = env('SPAPI_REGION', 'us-east-1');
        $this->marketplaceId = env('SPAPI_MARKETPLACE_ID');
        $this->awsAccessKey = env('AWS_ACCESS_KEY_ID');
        $this->awsSecretKey = env('AWS_SECRET_ACCESS_KEY');
        $this->endpoint = 'https://sellingpartnerapi-na.amazon.com';
    }
    public function getAccessToken()
    {
        $client = new Client();
        $response = $client->post('https://api.amazon.com/auth/o2/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        Log::info('Fetched access token.');
        return $data['access_token'];
    }

    public function updateAmazonPriceUS($sku, $price, $sellerId)
    {
        $accessToken = $this->getAccessToken();

        $endpoint = "https://sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/{$sellerId}/" . rawurlencode($sku) . "?marketplaceIds=ATVPDKIKX0DER";

        $body = [
            "productType" => "SPEAKERS",
            "patches" => [[
                "op" => "replace",
                "path" => "/attributes/purchasable_offer",
                "value" => [[
                    "marketplaceId" => "ATVPDKIKX0DER",
                    "currency" => "USD",
                    "listingPrices" => [[
                        "valueWithTax" => [
                            "amount" => (string) $price,
                            "currencyCode" => "USD"
                        ]
                    ]]
                ]]
            ]]
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'x-amz-access-token' => $accessToken,
                'content-type' => 'application/json',
                'accept' => 'application/json',
            ])
            ->patch($endpoint, $body);

        Log::info("Amazon Price Update Request", [
            "sku" => $sku,
            "price" => $price,
            "endpoint" => $endpoint,
            "body" => $body
        ]);

        if ($response->failed()) {
            Log::error("Amazon Price Update Failed", [
                "sku" => $sku,
                "status" => $response->status(),
                "response" => $response->json()
            ]);
        } else {
            Log::info("Amazon Price Update Success", $response->json());
        }

        return $response->json();
    }

    public function getAmazonListingUS($sku, $sellerId)
    {
        $accessToken = $this->getAccessToken();

        $endpoint = "https://sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/{$sellerId}/" . rawurlencode($sku) . "?marketplaceIds=ATVPDKIKX0DER";

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'x-amz-access-token' => $accessToken,
                'accept' => 'application/json',
            ])
            ->get($endpoint);

        Log::info("Amazon Get Listing Response", [
            "sku" => $sku,
            "endpoint" => $endpoint,
            "response" => $response->json()
        ]);

        return $response->json();
    }

    public function getAmazonPriceUS($asin)
{
    $accessToken = $this->getAccessToken();

    $endpoint = "https://sellingpartnerapi-na.amazon.com/products/pricing/v0/listings/{$asin}/offers?MarketplaceId=ATVPDKIKX0DER&ItemCondition=New";

    $response = Http::withToken($accessToken)
        ->withHeaders([
            'x-amz-access-token' => $accessToken,
            'accept' => 'application/json',
        ])
        ->get($endpoint);

    Log::info("Amazon Get Price Response", [
        "asin" => $asin,
        "endpoint" => $endpoint,
        "response" => $response->json()
    ]);

    return $response->json();
}




}
