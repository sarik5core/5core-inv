<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Aws\Signature\SignatureV4;
use Aws\Credentials\Credentials;
use Illuminate\Support\Facades\Http;

class AmazonSpApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $region;
    protected $marketplaceId;
    protected $awsAccessKey;
    protected $awsSecretKey;

    public function __construct()
    {
        $this->clientId = env('SPAPI_CLIENT_ID');
        $this->clientSecret = env('SPAPI_CLIENT_SECRET');
        $this->refreshToken = env('SPAPI_REFRESH_TOKEN');
        $this->region = env('SPAPI_REGION', 'us-east-1');
        $this->marketplaceId = env('SPAPI_MARKETPLACE_ID');
        $this->awsAccessKey = env('AWS_ACCESS_KEY_ID');
        $this->awsSecretKey = env('AWS_SECRET_ACCESS_KEY');
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
        return $data['access_token'];
    }

    public function updatePriceBySku($sellerId, $sku, $price, $currency = 'USD')
    {
        $accessToken = $this->getAccessToken();
        $encodedSku = rawurlencode($sku);

        // STEP 1: Get product type using catalog API
        // $asin = 'B0DKVGSDQJ'; // manually fetched from earlier step
        $productType = "SPEAKERS"; // based on catalog or seller central

        $uri = "/listings/2021-08-01/items/{$sellerId}/{$encodedSku}?marketplaceIds={$this->marketplaceId}";
        $endpoint = "https://sellingpartnerapi-na.amazon.com";
        $url = $endpoint . $uri;

        $payload = [
            "productType" => "SPEAKERS", // Replace with actual product type from catalog
            "patches" => [
                [
                    "op" => "replace",
                    "path" => "/attributes/purchasable_offer",
                    "value" => [
                        [
                            "marketplace_id" => "ATVPDKIKX0DER",
                            "currency" => "USD",
                            "our_price" => [
                                [
                                    "schedule" => [
                                        [
                                            "value_with_tax" => (double) $price
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];


        $credentials = new Credentials($this->awsAccessKey, $this->awsSecretKey);
        $signature = new SignatureV4('execute-api', $this->region);
        $timestamp = gmdate('Ymd\THis\Z');

        $headers = [
            'host' => parse_url($endpoint, PHP_URL_HOST),
            'content-type' => 'application/json',
            'x-amz-access-token' => $accessToken,
            'x-amz-date' => $timestamp,
            'accept' => 'application/json',
        ];

        $request = new Request('PATCH', $url, $headers, json_encode($payload));
        $signedRequest = $signature->signRequest($request, $credentials);

        $client = new Client();
        $response = $client->send($signedRequest);

        return json_decode($response->getBody(), true);
    }
}


// namespace App\Services;

// use Aws\Credentials\Credentials;
// use Aws\Signature\SignatureV4;
// use Exception;
// use GuzzleHttp\Client;
// use GuzzleHttp\Psr7\Request;
// use Illuminate\Support\Facades\Http;

// class AmazonSpApiService
// {
//     protected $clientId;
//     protected $clientSecret;
//     protected $refreshToken;
//     protected $region;
//     protected $marketplaceId;
//     protected $awsAccessKey;
//     protected $awsSecretKey;

//     public function __construct()
//     {
//         $this->clientId = env('SPAPI_CLIENT_ID');
//         $this->clientSecret = env('SPAPI_CLIENT_SECRET');
//         $this->refreshToken = env('SPAPI_REFRESH_TOKEN');
//         $this->region = env('SPAPI_REGION', 'us-east-1');
//         $this->marketplaceId = env('SPAPI_MARKETPLACE_ID');
//         $this->awsAccessKey = env('AWS_ACCESS_KEY_ID');
//         $this->awsSecretKey = env('AWS_SECRET_ACCESS_KEY');
//     }

//     public function getAccessToken()
//     {
//         $client = new Client();
//         $response = $client->post('https://api.amazon.com/auth/o2/token', [
//             'form_params' => [
//                 'grant_type' => 'refresh_token',
//                 'refresh_token' => $this->refreshToken,
//                 'client_id' => $this->clientId,
//                 'client_secret' => $this->clientSecret,
//             ]
//         ]);

//         $data = json_decode($response->getBody(), true);

//         return $data['access_token'];
//     }

//     public function checkFeedStatus($feedId)
//     {
//         $accessToken = $this->getAccessToken();
//         $endpoint = 'https://sellingpartnerapi-na.amazon.com';
//         $url = "$endpoint/feeds/2021-06-30/feeds/{$feedId}";

//         $feedDetails = $this->sendSignedRequest('GET', $url, $accessToken);

//         if (!empty($feedDetails['resultFeedDocumentId'])) {
//             $report = $this->getFeedProcessingReport($feedDetails['resultFeedDocumentId']);
//             $feedDetails['processingReport'] = $report;
//         }

//         return $feedDetails;
//     }

//     public function getFeedProcessingReport($documentId)
//     {
//         $accessToken = $this->getAccessToken();
//         $endpoint = 'https://sellingpartnerapi-na.amazon.com';
//         $url = "$endpoint/feeds/2021-06-30/documents/{$documentId}";

//         $docDetails = $this->sendSignedRequest('GET', $url, $accessToken);
//         $client = new Client();
//         $reportResponse = $client->get($docDetails['url']);

//         return $reportResponse->getBody()->getContents();
//     }

//     private function sendSignedRequest($method, $url, $accessToken, $body = null)
//     {
//         $credentials = new Credentials($this->awsAccessKey, $this->awsSecretKey);
//         $signature = new SignatureV4('execute-api', $this->region);
//         $timestamp = gmdate('Ymd\THis\Z');

//         $headers = [
//             'host' => parse_url($url, PHP_URL_HOST),
//             'x-amz-access-token' => $accessToken,
//             'x-amz-date' => $timestamp,
//             'accept' => 'application/json',
//         ];

//         if ($method === 'POST') {
//             $headers['content-type'] = 'application/json';
//         }

//         $request = new Request($method, $url, $headers, $body ? json_encode($body) : null);
//         $signedRequest = $signature->signRequest($request, $credentials);

//         $client = new Client();
//         $response = $client->send($signedRequest);

//         return json_decode($response->getBody(), true);
//     }
// }
