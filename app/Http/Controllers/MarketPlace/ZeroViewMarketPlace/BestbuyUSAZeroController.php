<?php

namespace App\Http\Controllers\MarketPlace\ZeroViewMarketPlace;

use App\Http\Controllers\Controller;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use App\Models\BestbuyUSADataView;
use Illuminate\Http\Request;

class BestbuyUSAZeroController extends Controller
{
    public function bestbuyUSAZeroview(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');
        return view('market-places.zero-market-places.bestbuyUSAZeroView', [
            'mode' => $mode,
            'demo' => $demo
        ]);
    }

    public function bestbuyUSAZeroAnalytics(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');
        return view('market-places.zero-market-places.bestbuyUSAAnalytics', [
            'mode' => $mode,
            'demo' => $demo
        ]);
    }

    public function getViewBestbuyUSAZeroData(Request $request)
    {
        $productMasters = ProductMaster::orderBy('parent', 'asc')
            ->orderByRaw("CASE WHEN sku LIKE 'PARENT %' THEN 1 ELSE 0 END")
            ->orderBy('sku', 'asc')
            ->get();

        $skus = $productMasters->pluck('sku')->filter()->unique()->values()->all();
        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy('sku');
        $bestbuyUSADataViews = BestbuyUSADataView::whereIn('sku', $skus)->get()->keyBy('sku');

        $result = [];
        foreach ($productMasters as $pm) {
            $sku = $pm->sku;
            $parent = $pm->parent;
            $shopify = $shopifyData[$sku] ?? null;

            $inv = $shopify ? $shopify->inv : 0;
            $ov_l30 = $shopify ? $shopify->quantity : 0;
            $ov_dil = ($inv > 0) ? round($ov_l30 / $inv, 4) : 0;

            if ($inv > 0) {
                $bestbuyUSAView = $bestbuyUSADataViews[$sku] ?? null;
                $value = $bestbuyUSAView ? $bestbuyUSAView->value : [];
                if (is_string($value)) {
                    $value = json_decode($value, true) ?: [];
                }

                // Get Live value from value array - ADDED
                $live = false;
                if (isset($value['Live'])) {
                    $live = filter_var($value['Live'], FILTER_VALIDATE_BOOLEAN);
                }

                // Get price - use BestBuy price if available, otherwise fall back to Shopify price
                $bestbuy_price = $value['price'] ?? 0;
                $shopify_price = $shopify ? $shopify->price : 0;
                $price = $bestbuy_price > 0 ? $bestbuy_price : $shopify_price;

                $row = [
                    'parent' => $parent,
                    'sku' => $sku,
                    'inv' => $inv,
                    'ov_l30' => $ov_l30,
                    'ov_dil' => $ov_dil,
                    'price' => $price,
                    'NR' => isset($value['NR']) && in_array($value['NR'], ['REQ', 'NR']) ? $value['NR'] : 'REQ',
                    'A_Z_Reason' => $value['A_Z_Reason'] ?? '',
                    'A_Z_ActionRequired' => $value['A_Z_ActionRequired'] ?? '',
                    'A_Z_ActionTaken' => $value['A_Z_ActionTaken'] ?? '',
                    'Live' => $live, // ADDED Live field
                ];
                $result[] = $row;
            }
        }

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $result,
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

        $row = BestbuyUSADataView::firstOrCreate(
            ['sku' => $sku],
            ['value' => json_encode([])]
        );

        $value = $row->value;
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

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

    public function updateListedLive(Request $request)
    {
        $request->validate([
            'sku'   => 'required|string',
            'field' => 'required|in:Listed,Live',
            'value' => 'required|boolean'
        ]);

        // Find or create the product without overwriting existing value
        $product = BestbuyUSADataView::firstOrCreate(
            ['sku' => $request->sku],
            ['value' => []]
        );

        // Decode current value (ensure it's an array)
        $currentValue = is_array($product->value)
            ? $product->value
            : (json_decode($product->value, true) ?? []);

        // Store as actual boolean
        $currentValue[$request->field] = filter_var($request->value, FILTER_VALIDATE_BOOLEAN);

        // Save back to DB
        $product->value = $currentValue;
        $product->save();

        return response()->json(['success' => true]);
    }
}
