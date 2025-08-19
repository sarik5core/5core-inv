<?php

namespace App\Http\Controllers\MarketPlace;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\MarketplacePercentage;
use App\Models\Shopifyb2cDataView;
use App\Models\ShopifySku;
use App\Models\ProductMaster;
use App\Models\ShopifyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class Shopifyb2cLowVisibilityController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }
    public function shopifyb2cLowVisibilityView(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // Get percentage from cache or database
        $percentage = Cache::remember('shopifyb2c_marketplace_percentage', now()->addDays(30), function () {
            $marketplaceData = MarketplacePercentage::where('marketplace', 'ShopifyB2C')->first();
            return $marketplaceData ? $marketplaceData->percentage : 100; // Default to 100 if not set
        });

        return view('market-places.shopifyb2cLowVisibilityView', [
            'mode' => $mode,
            'demo' => $demo,
            'shopifyb2cPercentage' => $percentage
        ]);
    }

    public function getViewShopifyB2CLowVisibilityData(Request $request)
    {
        // Fetch all ProductMaster records
        $productMasters = ProductMaster::all();
        $skus = $productMasters->pluck('sku')->toArray();

        // Fetch ShopifySku and ShopifyProduct records for those SKUs
        $shopifySkus = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');
        $shopifyProducts = Shopifyb2cDataView::whereIn('sku', $skus)->get()->keyBy('sku');

        // Fetch data from the Google Sheet using the ApiController method
        $sheetResponse = $this->apiController->fetchShopifyB2CListingData();
        $sheetData = [];
        if ($sheetResponse->getStatusCode() === 200) {
            $sheetRaw = $sheetResponse->getData();
            $sheetData = $sheetRaw->data ?? [];
        }

        // Map ProductMaster SKUs to sheet data (by (Child) sku)
        $sheetSkuMap = collect($sheetData)
            ->filter(function ($item) {
                return !empty($item->{'(Child) sku'} ?? null);
            })
            ->keyBy(function ($item) {
                return $item->{'(Child) sku'};
            });

        // Attach matching ShopifySku, ShopifyProduct, and Sheet data to each ProductMaster record
        $processedData = $productMasters->map(function ($product) use ($shopifySkus, $shopifyProducts, $sheetSkuMap) {
            $sku = $product->sku;
            $product->INV = $shopifySkus->has($sku) ? $shopifySkus[$sku]->inv : 0;
            $product->L30 = $shopifySkus->has($sku) ? $shopifySkus[$sku]->quantity : 0;
            $product->price = $shopifyProducts->has($sku) ? $shopifyProducts[$sku]->price : null;
            $product->sheet_data = $sheetSkuMap->has($sku) ? $sheetSkuMap[$sku] : null;

            // Fetch A_Z_Reason, A_Z_ActionRequired, A_Z_ActionTaken from Shopifyb2cDataView->value
            $product->A_Z_Reason = null;
            $product->A_Z_ActionRequired = null;
            $product->A_Z_ActionTaken = null;
            if ($shopifyProducts->has($sku) && is_array($shopifyProducts[$sku]->value ?? null)) {
                $value = $shopifyProducts[$sku]->value;
                $product->A_Z_Reason = $value['A_Z_Reason'] ?? null;
                $product->A_Z_ActionRequired = $value['A_Z_ActionRequired'] ?? null;
                $product->A_Z_ActionTaken = $value['A_Z_ActionTaken'] ?? null;
            }
            return $product;
        })
            // --- Apply filter: only rows where INV > 0 and VIEWS == 0 ---
            ->filter(function ($product) {
                $views = 0;
                if ($product->sheet_data && isset($product->sheet_data->VIEWS)) {
                    $views = (int) $product->sheet_data->VIEWS;
                }
                return $views >= 1 && $views <= 100;
            })
            ->values();

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $processedData,
            'status' => 200
        ]);
    }

    public function updateReasonAction(Request $request)
    {
        $sku = $request->input('sku');
        $reason = $request->input('reason');
        $actionRequired = $request->input('action_required');
        $actionTaken = $request->input('action_taken');

        if (!$sku) {
            return response()->json([
                'status' => 400,
                'message' => 'SKU is required.'
            ], 400);
        }

        $row = Shopifyb2cDataView::firstOrCreate(['sku' => $sku]);
        $value = $row->value ?? [];
        $value['A_Z_Reason'] = $reason;
        $value['A_Z_ActionRequired'] = $actionRequired;
        $value['A_Z_ActionTaken'] = $actionTaken;
        $row->value = $value;
        $row->save();

        return response()->json([
            'status' => 200,
            'message' => 'Reason and actions updated successfully.'
        ]);
    }
}