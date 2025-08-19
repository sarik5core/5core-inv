<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class Ebay2ApiService
{
    protected $appId;
    protected $certId;
    protected $devId;
    protected $userToken;
    protected $endpoint;
    protected $siteId;
    protected $compatLevel;

    public function __construct()
    {
        $this->appId       = env('EBAY2_APP_ID');
        $this->certId      = env('EBAY2_CERT_ID');
        $this->devId       = env('EBAY2_DEV_ID');
        $this->userToken   = env('EBAY2_USER_TOKEN');
        $this->endpoint    = env('EBAY2_TRADING_API_ENDPOINT', 'https://api.ebay.com/ws/api.dll');
        $this->siteId      = env('EBAY2_SITE_ID', 0); // US = 0
        $this->compatLevel = env('EBAY2_COMPAT_LEVEL', '1189');
    }

    /**
     * Revise the price of a fixed-price item listing.
     *
     * @param string $itemId
     * @param float $price
     * @param int|null $quantity
     * @param string|null $sku
     * @param array|null $variationSpecifics
     * @param array|null $variationSpecificsSet
     * @return array
     */

    public function getOAuthToken()
    {
        $clientId     = env('EBAY_APP_ID');
        $clientSecret = env('EBAY_CERT_ID');

        $credentials = base64_encode("{$clientId}:{$clientSecret}");

        $response = Http::withHeaders([
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => "Basic {$credentials}",
        ])->asForm()->post('https://api.ebay.com/identity/v1/oauth2/token', [
            'grant_type'    => 'client_credentials',
        ]);

        dd($response->json());

        if ($response->successful()) {
            return $response->json()['access_token'] ?? null;
        }

        return [];
    }

    public function reviseFixedPriceItem($itemId, $price, $quantity = null, $sku = null, $variationSpecifics = null, $variationSpecificsSet = null)
    {
        // Build XML body
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents"/>');
        $credentials = $xml->addChild('RequesterCredentials');
        $authToken = env('EBAY_USER_TOKEN');
        $credentials->addChild('eBayAuthToken', $authToken);


        $item = $xml->addChild('Item');
        $item->addChild('ItemID', $itemId);

        // Update price
        $item->addChild('StartPrice', $price);

        // Optionally update quantity
        if ($quantity !== null) {
            $item->addChild('Quantity', $quantity);
        }

        // If variation exists, use variation structure
        if ($variationSpecifics && $variationSpecificsSet) {
            $variations = $item->addChild('Variations');
            $variation = $variations->addChild('Variation');

            if ($sku) {
                $variation->addChild('SKU', $sku);
            }

            $variation->addChild('StartPrice', $price);
            if ($quantity !== null) {
                $variation->addChild('Quantity', $quantity);
            }

            // VariationSpecifics
            $vs = $variation->addChild('VariationSpecifics');
            foreach ($variationSpecifics as $name => $value) {
                $nvl = $vs->addChild('NameValueList');
                $nvl->addChild('Name', $name);
                $nvl->addChild('Value', $value);
            }

            // VariationSpecificsSet
            $vss = $item->addChild('VariationSpecificsSet');
            foreach ($variationSpecificsSet as $name => $values) {
                $nvl = $vss->addChild('NameValueList');
                $nvl->addChild('Name', $name);
                foreach ($values as $val) {
                    $nvl->addChild('Value', $val);
                }
            }
        }

        $xmlBody = $xml->asXML();

        // Prepare headers
        $headers = [
            'X-EBAY-API-COMPATIBILITY-LEVEL' => $this->compatLevel,
            'X-EBAY-API-DEV-NAME'            => $this->devId,
            'X-EBAY-API-APP-NAME'            => $this->appId,
            'X-EBAY-API-CERT-NAME'           => $this->certId,
            'X-EBAY-API-CALL-NAME'           => 'ReviseFixedPriceItem',
            'X-EBAY-API-SITEID'              => $this->siteId,
            'Content-Type'                   => 'text/xml',
        ];

        // Send API request
        $response = Http::withHeaders($headers)
            ->withBody($xmlBody, 'text/xml')
            ->post($this->endpoint);

        $body = $response->body();

        // Parse XML response
        libxml_use_internal_errors(true);
        $xmlResp = simplexml_load_string($body);
        if ($xmlResp === false) {
            return [
                'success' => false,
                'message' => 'Invalid XML response',
                'raw' => $body,
            ];
        }

        $responseArray = json_decode(json_encode($xmlResp), true);
        $ack = $responseArray['Ack'] ?? 'Failure';

        if ($ack === 'Success' || $ack === 'Warning') {
            return [
                'success' => true,
                'message' => 'Item updated successfully.',
                'data' => $responseArray,
            ];
        } else {
            return [
                'success' => false,
                'errors' => $responseArray['Errors'] ?? 'Unknown error',
                'data' => $responseArray,
            ];
        }
    }
}
