<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class DobaApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = env('EBAY_APP_ID');
        $this->clientSecret = env('EBAY_CERT_ID');
        $this->refreshToken = env('EBAY_REFRESH_TOKEN');
        $this->baseUrl = 'https://api.ebay.com';
    }

    public function updateItemPrice($sku, $price)
    {
        $timestamp = $this->getMillisecond();
        $getContent = $this->getContent($timestamp);
        $sign = $this->generateSignature($getContent);

        $response = Http::withHeaders([
            'appKey' => env('DOBA_APP_KEY'),
            'signType' => 'rsa2',
            'timestamp' => $timestamp,
            'sign' => $sign,
            'Content-Type' => 'application/json',
        ])->post('https://openapi.doba.com/api/goods/price/update?anticipatedIncome=' . $price . '&itemNo=' . $sku);

        return $response->json();
    }


    private function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return intval((float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000));
    }


    public function generateSignature($content)
    {
        $privateKeyFormatted = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap(env('DOBA_PRIVATE_KEY'), 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $private_key = openssl_pkey_get_private($privateKeyFormatted);
        if (!$private_key) {
            throw new Exception("Invalid private key.");
        }
        openssl_sign($content, $signature, $private_key, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($signature);
        return $sign;
    }

    private function getContent($timestamp)
    {
        $appKey = env('DOBA_APP_KEY');
        $contentForSign = "appKey={$appKey}&signType=rsa2&timestamp={$timestamp}";
        return $contentForSign;
    }
}
