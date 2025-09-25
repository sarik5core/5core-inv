<?php

namespace App\Http\Controllers\ProductMaster;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\AmazonDatasheet;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use App\Models\JungleScoutProductData;
use App\Models\Supplier;

class ForecastAnalysisController extends Controller
{
    protected $apiController;

    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }

    private function buildForecastAnalysisData()
    {
        $jungleScoutData = JungleScoutProductData::query()
            ->get()
            ->groupBy(fn($item) => strtoupper(trim($item->parent)))
            ->map(function ($group) {
                $validPrices = $group->filter(function ($item) {
                    $data = is_array($item->data) ? $item->data : [];
                    $price = $data['price'] ?? null;
                    return is_numeric($price) && $price > 0;
                })->pluck('data.price');

                return [
                    'scout_parent' => $group->first()->parent,
                    'min_price' => $validPrices->isNotEmpty() ? $validPrices->min() : null,
                    'product_count' => $group->count(),
                    'all_data' => $group->map(function ($item) {
                        $data = is_array($item->data) ? $item->data : [];
                        if (isset($data['price'])) {
                            $data['price'] = is_numeric($data['price']) ? (float) $data['price'] : null;
                        }
                        return $data;
                    })->toArray()
                ];
            });

        $productListData = DB::table('product_master')->get()->keyBy(fn($item) => strtoupper(trim($item->sku)));
        $skus = $productListData->keys()->toArray();

        $shopifyData = ShopifySku::whereIn(DB::raw('UPPER(sku)'), array_map('strtoupper', $skus))
            ->get()
            ->keyBy(function ($item) {
                return strtoupper($item->sku);
            });

        $supplierRows = Supplier::where('type', 'Supplier')->get();
        $supplierMapByParent = [];

        foreach ($supplierRows as $row) {
            $parents = array_map('trim', explode(',', strtoupper($row->parent ?? '')));
            foreach ($parents as $parent) {
                if (!empty($parent)) {
                    $supplierMapByParent[$parent][] = $row->name;
                }
            }
        }

        $forecastMap = DB::table('forecast_analysis')->get()->keyBy(function ($item) {
            return strtoupper(trim($item->parent)) . '|' . strtoupper(trim($item->sku));
        });

        $movementMap = DB::table('movement_analysis')->get()->keyBy(fn($item) => strtoupper(trim($item->sku)));

        $processedData = [];

        foreach ($productListData as $sheetSku => $prodData) {
            if (empty($sheetSku)) continue;

            $item = new \stdClass();
            $item->SKU = $sheetSku;
            $item->Parent = strtoupper(trim($prodData->parent ?? ''));
            $item->is_parent = stripos($sheetSku, 'PARENT') !== false;
            $item->{'Supplier Tag'} = isset($supplierMapByParent[$item->Parent]) ? implode(', ', array_unique($supplierMapByParent[$item->Parent])) : '';

            $valuesRaw = $prodData->Values ?? '{}';
            $values = json_decode($valuesRaw, true);

            $item->{'CP'} = $values['cp'] ?? '';
            $item->{'LP'} = $values['lp'] ?? '';
            $item->{'SH'} = $values['ship'] ?? '';
            $item->{'Freight'} = $values['frght'] ?? '';
            $item->{'CBM MSL'} = $values['cbm'] ?? '';
            $item->{'GW (LB)'} = $values['wt_act'] ?? '';
            $item->{'GW (KG)'} = is_numeric($values['wt_act'] ?? null) ? round($values['wt_act'] * 0.45, 2) : '';

            // Image logic: prefer Shopify image_src, fallback to product_master.Values.image_path
            $imageFromShopify = $shopifyData[$sheetSku]->image_src ?? null;
            $imageFromProductMaster = $values['image_path'] ?? null;
            $item->Image = $imageFromShopify ?: $imageFromProductMaster;

            $item->INV = $shopifyData[$sheetSku]->inv ?? 0;
            $item->L30 = $shopifyData[$sheetSku]->quantity ?? 0;

            if (!empty($item->Parent) && $jungleScoutData->has($item->Parent)) {
                $item->scout_data = json_decode(json_encode($jungleScoutData[$item->Parent]), true);
            }

            if ($forecastMap->has($item->Parent . '|' . $sheetSku)) {
                $forecast = $forecastMap->get($item->Parent . '|' . $sheetSku);
                $item->{'s-msl'} = $forecast->s_msl ?? '';
                $item->{'Approved QTY'} = $forecast->approved_qty ?? '';
                $item->order_given = $forecast->order_given ?? '';
                $item->transit = $forecast->transit ?? '';
                $item->nr = $forecast->nr ?? '';
                $item->req = $forecast->req ?? '';
                $item->hide = $forecast->hide ?? '';
                $item->notes = $forecast->notes ?? '';
                $item->{'Clink'} = $forecast->clink ?? '';
                $item->{'Olink'} = $forecast->olink ?? '';
                $item->rfq_form_link = $forecast->rfq_form_link ?? '';
                $item->rfq_report = $forecast->rfq_report ?? '';
                $item->date_apprvl = $forecast->date_apprvl ?? '';
            }

            if ($movementMap->has($sheetSku)) {
                $months = json_decode($movementMap->get($sheetSku)->months ?? '{}', true);
                $months = is_array($months) ? $months : [];

                $monthNames = ['Dec', 'jan', 'feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'];
                $totalMonthCount = 0;
                $totalSum = 0;

                foreach ($monthNames as $month) {
                    $value = isset($months[$month]) && is_numeric($months[$month]) ? (int)$months[$month] : 0;
                    $item->{$month} = $value;
                    if ($value !== 0) $totalMonthCount++;
                    $totalSum += $value;
                }

                $item->{'Total'} = ($item->L30 ?? 0) + $totalSum;
                $item->{'Total month'} = $totalMonthCount + ((isset($item->L30) && $item->L30 != 0) ? 1 : 0);
            }

            $processedData[] = $item;
        }

        return $processedData;
    }

    
    public function getViewForecastAnalysisData(Request $request)
    {
        try {
            $processedData = $this->buildForecastAnalysisData();

            return response()->json([
                'message' => 'Data fetched successfully',
                'data' => $processedData,
                'status' => 200,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forecastAnalysis(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        return view('purchase-master.forecastAnalysis', [
            'mode' => $mode,
            'demo' => $demo,
        ]);
    }

    public function updateForcastSheet(Request $request)
    {        
        $sku = trim($request->input('sku'));
        $parent = trim($request->input('parent'));
        $column = trim($request->input('column'));
        $value = $request->input('value');

        $columnMap = [
            'S-MSL' => 's_msl',
            'Approved QTY' => 'approved_qty',
            'NR' => 'nr',
            'REQ' => 'req',
            'Hide' => 'hide',
            'Notes' => 'notes',
            'Clink' => 'clink',
            'Olink' => 'olink',
            'rfq_form_link' => 'rfq_form_link',
            'rfq_report' => 'rfq_report',
            'ORDER given' => 'order_given',
            'Transit' => 'transit',
            'Date of Appr' => 'date_apprvl',
        ];

        $columnKey = $columnMap[$column] ?? null;

        if (!$columnKey) {
            return response()->json(['success' => false, 'message' => 'Invalid column']);
        }

        $existing = DB::table('forecast_analysis')
            ->select('*')
            ->whereRaw('TRIM(LOWER(sku)) = ?', [strtolower($sku)])
            ->whereRaw('TRIM(LOWER(parent)) = ?', [strtolower($parent)])
            ->first();

        if ($existing) {
            $currentValue = $existing->{$columnKey} ?? null;

            if ((string)$currentValue !== (string)$value) {
                DB::table('forecast_analysis')
                    ->where('id', $existing->id)
                    ->update([$columnKey => $value, 'updated_at' => now()]);
            }

            if (strtolower($column) === 'approved qty' && !empty($value) && (float)$value != 0){
                $orderQty = $existing->order_given ?? null;
                DB::table('to_order_analysis')->updateOrInsert(
                    ['sku' => $sku, 'parent' => $parent],
                    [
                        'approved_qty' => $value,
                        'order_qty'    => $orderQty,
                        'date_apprvl' => now()->toDateString(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            if (strtolower($column) === 'transit' && !empty($value) && (float)$value != 0){
                $supplierRows = DB::table('suppliers')->where('type', 'Supplier')->get();

                $supplier = null;
                $upperParent = strtoupper(trim($parent));
                foreach ($supplierRows as $row) {
                    $parents = array_map('trim', explode(',', strtoupper($row->parent ?? '')));
                    if (in_array($upperParent, $parents)) {
                        $supplier = $row->name;
                        break;
                    }
                }

                DB::table('ready_to_ship')->updateOrInsert(
                    ['sku' => $sku, 'parent' => $parent],
                    [
                        'qty' => $value,
                        'supplier' => $supplier ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            return response()->json(['success' => true, 'message' => 'Updated or already up-to-date']);
        } else {
            DB::table('forecast_analysis')->insert([
                'sku' => $sku,
                'parent' => $parent,
                $columnKey => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (strtolower($column) === 'approved qty' && !empty($value) && (float)$value != 0) {
                $orderQty = DB::table('forecast_analysis')
                    ->whereRaw('TRIM(LOWER(sku)) = ?', [strtolower($sku)])
                    ->whereRaw('TRIM(LOWER(parent)) = ?', [strtolower($parent)])
                    ->value('order_given');

                DB::table('to_order_analysis')->updateOrInsert(
                    ['sku' => $sku, 'parent' => $parent],
                    [
                        'approved_qty' => $value,
                        'order_qty'    => $orderQty,  
                        'date_apprvl'  => now()->toDateString(),
                        'updated_at'   => now(),
                        'created_at'   => now(),
                    ]
                );
            }
            if (strtolower($column) === 'order given' && !empty($value)) {
                $approvedQty = DB::table('forecast_analysis')
                    ->whereRaw('TRIM(LOWER(sku)) = ?', [strtolower($sku)])
                    ->whereRaw('TRIM(LOWER(parent)) = ?', [strtolower($parent)])
                    ->value('approved_qty');

                DB::table('to_order_analysis')->updateOrInsert(
                    ['sku' => $sku, 'parent' => $parent],
                    [
                        'approved_qty' => $approvedQty,
                        'order_qty'    => $value,
                        'date_apprvl'  => now()->toDateString(),
                        'updated_at'   => now(),
                        'created_at'   => now(),
                    ]
                );
            }

            if (strtolower($column) === 'transit' && !empty($value)) {
                $supplierRows = DB::table('suppliers')->where('type', 'Supplier')->get();

                $supplier = null;
                $upperParent = strtoupper(trim($parent));
                foreach ($supplierRows as $row) {
                    $parents = array_map('trim', explode(',', strtoupper($row->parent ?? '')));
                    if (in_array($upperParent, $parents)) {
                        $supplier = $row->name;
                        break;
                    }
                }

                DB::table('ready_to_ship')->updateOrInsert(
                    ['sku' => $sku, 'parent' => $parent],
                    [
                        'qty' => $value,
                        'supplier' => $supplier ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            return response()->json(['success' => true, 'message' => 'Inserted new row']);
        }
    }

    public function invetoryStagesView(){
        return view('purchase-master.inventory-stages');
    }

    public function invetoryStagesData(){
        try {
            $jungleScoutData = JungleScoutProductData::query()
            ->get()
            ->groupBy(fn($item) => strtoupper(trim($item->parent)))
            ->map(function ($group) {
                $validPrices = $group->filter(function ($item) {
                    $data = is_array($item->data) ? $item->data : [];
                    $price = $data['price'] ?? null;
                    return is_numeric($price) && $price > 0;
                })->pluck('data.price');

                return [
                    'scout_parent' => $group->first()->parent,
                    'min_price' => $validPrices->isNotEmpty() ? $validPrices->min() : null,
                    'product_count' => $group->count(),
                    'all_data' => $group->map(function ($item) {
                        $data = is_array($item->data) ? $item->data : [];
                        if (isset($data['price'])) {
                            $data['price'] = is_numeric($data['price']) ? (float) $data['price'] : null;
                        }
                        return $data;
                    })->toArray()
                ];
            });

            $productListData = DB::table('product_master')->get()->keyBy(fn($item) => strtoupper(trim($item->sku)));
            $skus = $productListData->keys()->toArray();

            $shopifyData = ShopifySku::whereIn(DB::raw('UPPER(sku)'), array_map('strtoupper', $skus))
                ->get()
                ->keyBy(function ($item) {
                    return strtoupper($item->sku);
                });

            $supplierRows = Supplier::where('type', 'Supplier')->get();
            $supplierMapByParent = [];

            foreach ($supplierRows as $row) {
                $parents = array_map('trim', explode(',', strtoupper($row->parent ?? '')));
                foreach ($parents as $parent) {
                    if (!empty($parent)) {
                        $supplierMapByParent[$parent][] = $row->name;
                    }
                }
            }

            $forecastMap = DB::table('forecast_analysis')->get()->keyBy(function ($item) {
                return strtoupper(trim($item->parent)) . '|' . strtoupper(trim($item->sku));
            });

            $movementMap = DB::table('movement_analysis')->get()->keyBy(fn($item) => strtoupper(trim($item->sku)));

            $mfrgProgressMap = DB::table('mfrg_progress')
                ->select('sku', DB::raw('SUM(qty) as total_qty'))
                ->groupBy('sku')
                ->get()
                ->keyBy(fn($item) => strtoupper(trim($item->sku)));

            $readyToShip = DB::table('ready_to_ship')
                ->select('sku', DB::raw('SUM(qty) as total_qty'))
                ->groupBy('sku')
                ->get()
                ->keyBy(fn($item) => strtoupper(trim($item->sku)));

            $processedData = [];

            foreach ($productListData as $sheetSku => $prodData) {
                if (empty($sheetSku)) continue;

                $item = new \stdClass();
                $item->SKU = $sheetSku;
                $item->Parent = strtoupper(trim($prodData->parent ?? ''));
                $item->is_parent = stripos($sheetSku, 'PARENT') !== false;
                $item->{'Supplier Tag'} = isset($supplierMapByParent[$item->Parent]) ? implode(', ', array_unique($supplierMapByParent[$item->Parent])) : '';

                $valuesRaw = $prodData->Values ?? '{}';
                $values = json_decode($valuesRaw, true);

                $item->{'CP'} = $values['cp'] ?? '';
                $item->{'LP'} = $values['lp'] ?? '';
                $item->{'SH'} = $values['ship'] ?? '';
                $item->{'Freight'} = $values['frght'] ?? '';
                $item->{'CBM MSL'} = $values['cbm'] ?? '';
                $item->{'GW (LB)'} = $values['wt_act'] ?? '';
                $item->{'GW (KG)'} = is_numeric($values['wt_act'] ?? null) ? round($values['wt_act'] * 0.45, 2) : '';

                // Image logic: prefer Shopify image_src, fallback to product_master.Values.image_path
                $imageFromShopify = $shopifyData[$sheetSku]->image_src ?? null;
                $imageFromProductMaster = $values['image_path'] ?? null;
                $item->Image = $imageFromShopify ?: $imageFromProductMaster;

                $item->INV = $shopifyData[$sheetSku]->inv ?? 0;
                $item->L30 = $shopifyData[$sheetSku]->quantity ?? 0;

                if (!empty($item->Parent) && $jungleScoutData->has($item->Parent)) {
                    $item->scout_data = json_decode(json_encode($jungleScoutData[$item->Parent]), true);
                }

                if ($forecastMap->has($item->Parent . '|' . $sheetSku)) {
                    $forecast = $forecastMap->get($item->Parent . '|' . $sheetSku);
                    $item->{'s-msl'} = $forecast->s_msl ?? '';
                    $item->{'Approved QTY'} = $forecast->approved_qty ?? '';

                    if ($mfrgProgressMap->has($sheetSku)) {
                        $item->order_given = $mfrgProgressMap[$sheetSku]->total_qty ?? 0;
                    } else {
                        $item->order_given = 0;
                    }

                    if ($readyToShip->has($sheetSku)) {
                        $item->transit = $readyToShip[$sheetSku]->total_qty ?? 0;
                    } else {
                        $item->transit = 0;
                    }
                    $item->nr = $forecast->nr ?? '';
                    $item->req = $forecast->req ?? '';
                    $item->hide = $forecast->hide ?? '';
                    $item->notes = $forecast->notes ?? '';
                    $item->{'Clink'} = $forecast->clink ?? '';
                    $item->{'Olink'} = $forecast->olink ?? '';
                    $item->rfq_form_link = $forecast->rfq_form_link ?? '';
                    $item->rfq_report = $forecast->rfq_report ?? '';
                    $item->date_apprvl = $forecast->date_apprvl ?? '';
                }

                if ($movementMap->has($sheetSku)) {
                    $months = json_decode($movementMap->get($sheetSku)->months ?? '{}', true);
                    $months = is_array($months) ? $months : [];

                    $monthNames = ['Dec', 'jan', 'feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'];
                    $totalMonthCount = 0;
                    $totalSum = 0;

                    foreach ($monthNames as $month) {
                        $value = isset($months[$month]) && is_numeric($months[$month]) ? (int)$months[$month] : 0;
                        $item->{$month} = $value;
                        if ($value !== 0) $totalMonthCount++;
                        $totalSum += $value;
                    }

                    $item->{'Total'} = ($item->L30 ?? 0) + $totalSum;
                    $item->{'Total month'} = $totalMonthCount + ((isset($item->L30) && $item->L30 != 0) ? 1 : 0);
                }

                $processedData[] = $item;
            }

            return response()->json([
                'message' => 'Data fetched successfully',
                'data' => $processedData,
                'status' => 200,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
