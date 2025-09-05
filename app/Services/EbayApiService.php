<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class EbayApiService
{

    protected $appId;
    protected $certId;
    protected $devId;
    protected $endpoint;
    protected $siteId;
    protected $compatLevel;

    public function __construct()
    {
        $this->appId       = env('EBAY_APP_ID');
        $this->certId      = env('EBAY_CERT_ID');
        $this->devId       = env('EBAY_DEV_ID');
        $this->endpoint    = env('EBAY_TRADING_API_ENDPOINT', 'https://api.ebay.com/ws/api.dll');
        $this->siteId      = env('EBAY_SITE_ID', 0); // US = 0
        $this->compatLevel = env('EBAY_COMPAT_LEVEL', '1189');
    }


    public function generateBearerToken()
    {
        // 1. If cached token exists, return it immediately
        if (Cache::has('ebay_bearer')) {
            echo "\nBearer Token in Cache";

            return Cache::get('ebay_bearer');
        }


        echo "Generating New Ebay Token";

        // 2. Otherwise, request new token from eBay
        $clientId     = env('EBAY_APP_ID');
        $clientSecret = env('EBAY_CERT_ID');
        $refreshToken = env('EBAY_REFRESH_TOKEN');

        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post('https://api.ebay.com/identity/v1/oauth2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope'         => 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.inventory',
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get eBay token: ' . $response->body());
        }

        $data        = $response->json();
        $accessToken = $data['access_token'];
        $expiresIn   = $data['expires_in'] ?? 3600; // seconds, defaults to 1h

        // 3. Store token in cache for slightly less than expiry time
        Cache::put('ebay_bearer', $accessToken, now()->addSeconds($expiresIn - 60));

        return $accessToken;
    }


    public function reviseFixedPriceItem($itemId, $price, $quantity = null, $sku = null, $variationSpecifics = null, $variationSpecificsSet = null)
    {
        // Build XML body
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents"/>');
        $credentials = $xml->addChild('RequesterCredentials');

        $authToken = $this->generateBearerToken();

        $credentials->addChild('eBayAuthToken', $authToken ?? '');


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

    public function doRepricing($query)
    {
        $bearerToken = $this->generateBearerToken();

        // Initial URL
        $url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?q=' . urlencode($query);

        $constructedData = [];
        $i = 0;

        sleep(2);
        
        // First request to check total count
        $firstResponse = Http::withHeaders([
            'Authorization' => "Bearer {$bearerToken}"
        ])->get($url);

        $firstData = $firstResponse->json() ?? [];

        // Check if "total" exists and exceeds limit
        if (!empty($firstData['total']) && $firstData['total'] > 2000) {
            return [
                'error'   => true,
                'message' => 'Data too large (' . $firstData['total'] . ' items found)'
            ];
        }

        // Process first page
        $responseJSON = $firstData;

        do {
            foreach ($responseJSON['itemSummaries'] ?? [] as $data) {
                $constructedData[$i]['title']         = data_get($data, 'title', '');
                $constructedData[$i]['item_id']       = data_get($data, 'itemId', '');
                $constructedData[$i]['link']          = data_get($data, 'itemWebUrl', '');
                $constructedData[$i]['condition']     = data_get($data, 'condition', '');
                $constructedData[$i]['shipping_type'] = data_get($data, 'shippingOptions.0.shippingCostType', '');
                $constructedData[$i]['shipping_cost'] = data_get($data, 'shippingOptions.0.shippingCost.value', 0);
                $constructedData[$i]['shipping_from'] = data_get($data, 'shippingOptions.0.itemLocation.country', '');
                $constructedData[$i]['buying_options'] = data_get($data, 'buyingOptions.0', '');
                $constructedData[$i]['image']         = data_get($data, 'image.imageUrl', '');

                // Price = price.value + shippingCost.value
                $price        = data_get($data, 'price.value', 0);
                $shippingCost = data_get($data, 'shippingOptions.0.shippingCost.value', 0);
                $constructedData[$i]['price'] = (float) ($price + $shippingCost);

                $constructedData[$i]['seller']        = data_get($data, 'seller.username', '');
                $constructedData[$i]['seller_rating'] = data_get($data, 'seller.feedbackPercentage', '');
                $constructedData[$i]['seller_score']  = data_get($data, 'seller.feedbackScore', '');

                $originDate  = data_get($data, 'itemOriginDate');
                $listingDate = data_get($data, 'itemCreationDate');

                $constructedData[$i]['origin_date']  = $originDate ? date('d-m-Y h:i:s A', strtotime($originDate)) : '';
                $constructedData[$i]['listing_date'] = $listingDate ? date('d-m-Y h:i:s A', strtotime($listingDate)) : '';

                $constructedData[$i]['listing_type'] = !empty(data_get($data, 'buyingOptions'))
                    ? implode('-', data_get($data, 'buyingOptions', []))
                    : '';

                $i++;
            }

            // Get next page URL
            $url = data_get($responseJSON, 'next');

            // Fetch next page if exists
            if (!empty($url)) {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$bearerToken}"
                ])->get($url);

                $responseJSON = $response->json() ?? [];
            }
        } while (!empty($url));

        return $constructedData;
    }


    public function getRateLimitForAPI(String $name, String $context)
    {
        $bearerToken = $this->generateBearerToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$bearerToken}"
        ])
            ->get('https://api.ebay.com/developer/analytics/v1_beta/rate_limit', [
                'api_name' => $name,
                'api_context' => $context,
            ]);

        return $response->json();
    }
}
