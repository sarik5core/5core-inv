<?php

namespace App\Http\Controllers\PricingMaster;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\AmazonDatasheet;
use App\Models\DobaMetric;
use App\Models\EbayMetric;
use App\Models\PricingMaster;
use App\Models\ProductMaster;
use App\Models\ShopifySku;
use App\Models\MacyProduct;
use App\Models\ReverbProduct;
use App\Models\TemuProductSheet;
use App\Models\WalmartDataView;
use App\Models\Ebay2Metric;
use App\Models\Ebay3Metric;
use Illuminate\Http\Request;

class PricingMasterViewsController extends Controller
{
    public function __construct(ApiController $apiController)
    {
        $this->apiController = $apiController;
    }


    public function pricingMaster(Request $request)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        // yaha processed data le lo
        $processedData = $this->processPricingData();

        return view('pricing-master.pricing_masters_view', [
            'mode' => $mode,
            'demo' => $demo,
            'records' => $processedData, // processed data table ke liye
        ]);
    }

    protected function processPricingData($searchTerm = '')
    {
        $productData = ProductMaster::whereNull('deleted_at')->get();

        $skus = $productData
            ->pluck('sku')
            ->filter(function ($sku) {
                return stripos($sku, 'PARENT') === false;
            })
            ->unique()
            ->toArray();
        $shopifyData = ShopifySku::whereIn('sku', $skus)->get()->keyBy(function ($item) {
            return trim(strtoupper($item->sku));
        });
        $amazonData  = AmazonDatasheet::whereIn('sku', $skus)->get()->keyBy('sku');
        $ebayData    = EbayMetric::whereIn('sku', $skus)->get()->keyBy('sku');
        $dobaData    = DobaMetric::whereIn('sku', $skus)->get()->keyBy('sku');
        $pricingData = PricingMaster::whereIn('sku', $skus)->get()->keyBy('sku');
        $macyData    = MacyProduct::whereIn('sku', $skus)->get()->keyBy('sku');
        $reverbData  = ReverbProduct::whereIn('sku', $skus)->get()->keyBy('sku');
        $temuLookup  = TemuProductSheet::all()->keyBy('sku');
        $walmartLookup = WalmartDataView::all()->keyBy('sku');
        $ebay2Lookup = Ebay2Metric::all()->keyBy('sku');
        $ebay3Lookup = Ebay3Metric::all()->keyBy('sku');

        $processedData = [];

        foreach ($productData as $product) {
            $sku = $product->sku;

            if (!empty($searchTerm) && stripos($sku, $searchTerm) === false && stripos($product->parent, $searchTerm) === false) {
                continue;
            }

            $isParent = stripos($sku, 'PARENT') !== false;
            $values = is_string($product->Values) ? json_decode($product->Values, true) : $product->Values;
            if (!is_array($values)) {
                $values = [];
            }

            $msrp = (float) ($values['msrp'] ?? 0);
            $map  = (float) ($values['map'] ?? 0);
            $lp   = (float) ($values['lp'] ?? 0);
            $ship = (float) ($values['ship'] ?? 0);

            $amazon  = $amazonData[$sku] ?? null;
            $ebay    = $ebayData[$sku] ?? null;
            $doba    = $dobaData[$sku] ?? null;
            $pricing = $pricingData[$sku] ?? null;
            $macy    = $macyData[$sku] ?? null;
            $reverb  = $reverbData[$sku] ?? null;
            $temu    = $temuLookup[$sku] ?? null;
            $walmart = $walmartLookup[$sku] ?? null;
            $ebay2   = $ebay2Lookup[$sku] ?? null;
            $ebay3   = $ebay3Lookup[$sku] ?? null;

            // Get Shopify data for L30 and INV
            $shopifyItem = $shopifyData[trim(strtoupper($sku))] ?? null;
            $inv = $shopifyItem ? ($shopifyItem->inv ?? 0) : 0;
            $l30 = $shopifyItem ? ($shopifyItem->shopify_l30 ?? 0) : 0;

            $item = (object) [
                'SKU'     => $sku,
                'Parent'  => $product->parent,
                'L30'     => $l30,
                'INV'     => $inv,
                'Dil%'    => $inv > 0 ? round($l30 / $inv, 2) : 0,
                'MSRP'    => $msrp,
                'MAP'     => $map,
                'LP'      => $lp,
                'SHIP'    => $ship,
                'is_parent' => $isParent,
                'inv' => $shopifyData[trim(strtoupper($sku))]->inv ?? 0,

                // Amazon
                'amz_price' => $amazon ? ($amazon->price ?? 0) : 0,
                'amz_l30'   => $amazon ? ($amazon->units_ordered_l30 ?? 0) : 0,
                'amz_l60'   => $amazon ? ($amazon->units_ordered_l60 ?? 0) : 0,
                'amz_pft'   => $amazon && ($amazon->price ?? 0) > 0 ? (($amazon->price * 0.85 - $lp - $ship) / $amazon->price) : 0,
                'amz_roi'   => $amazon && $lp > 0 && ($amazon->price ?? 0) > 0 ? (($amazon->price * 0.85 - $lp - $ship) / $lp) : 0,

                // eBay
                'ebay_price' => $ebay ? ($ebay->ebay_price ?? 0) : 0,
                'ebay_l30'   => $ebay ? ($ebay->ebay_l30 ?? 0) : 0,
                'ebay_pft'   => $ebay && ($ebay->ebay_price ?? 0) > 0 ? (($ebay->ebay_price * 0.87 - $lp - $ship) / $ebay->ebay_price) : 0,
                'ebay_roi'   => $ebay && $lp > 0 && ($ebay->ebay_price ?? 0) > 0 ? (($ebay->ebay_price * 0.87 - $lp - $ship) / $lp) : 0,

                // Doba
                'doba_price' => $doba ? ($doba->anticipated_income ?? 0) : 0,
                'doba_l30'   => $doba ? ($doba->quantity_l30 ?? 0) : 0,
                'doba_pft'   => $doba && ($doba->anticipated_income ?? 0) > 0 ? (($doba->anticipated_income - $lp - $ship) / $doba->anticipated_income) : 0,
                'doba_roi'   => $doba && $lp > 0 && ($doba->anticipated_income ?? 0) > 0 ? (($doba->anticipated_income - $lp - $ship) / $lp) : 0,

                // Macy
                'macy_price' => $macy ? ($macy->price ?? 0) : 0,
                'macy_l30'   => $macy ? ($macy->m_l30 ?? 0) : 0,
                'macy_pft'   => $macy && $macy->price > 0 ? (($macy->price * 0.77 - $lp - $ship) / $macy->price) : 0,
                'macy_roi'   => $macy && $lp > 0 && $macy->price > 0 ? (($macy->price * 0.77 - $lp - $ship) / $lp) : 0,

                // Reverb
                'reverb_price' => $reverb ? ($reverb->price ?? 0) : 0,
                'reverb_l30'   => $reverb ? ($reverb->r_l30 ?? 0) : 0,
                'reverb_l60'   => $reverb ? ($reverb->r_l60 ?? 0) : 0,
                'reverb_pft'   => $reverb && $reverb->price > 0 ? (($reverb->price * 0.77 - $lp - $ship) / $reverb->price) : 0,
                'reverb_roi'   => $reverb && $lp > 0 && $reverb->price > 0 ? (($reverb->price * 0.77 - $lp - $ship) / $lp) : 0,

                // Temu
                'temu_price' => $temu ? (float) ($temu->{'price'} ?? 0) : 0,
                'temu_l30'   => $temu ? (float) ($temu->{'l30'} ?? 0) : 0,
                'temu_dil'   => $temu ? (float) ($temu->{'dil'} ?? 0) : 0,
                'temu_pft'   => $temu && ($temu->price ?? 0) > 0 ? (($temu->price * 0.77 - $lp - $ship) / $temu->price) : 0,
                'temu_roi'   => $temu && $lp > 0 && ($temu->price ?? 0) > 0 ? (($temu->price * 0.77 - $lp - $ship) / $lp) : 0,

                // Walmart
                'walmart_price' => $walmart ? (float) ($walmart->{'walmart_price'} ?? 0) : 0,
                'walmart_l30'   => $walmart ? (float) ($walmart->{'walmart_l30'} ?? 0) : 0,
                'walmart_dil'   => $walmart ? (float) ($walmart->{'walmart_dil'} ?? 0) : 0,
                'walmart_pft'   => $walmart && ($walmart->walmart_price ?? 0) > 0 ? (($walmart->walmart_price * 0.85 - $lp - $ship) / $walmart->walmart_price) : 0,
                'walmart_roi'   => $walmart && $lp > 0 && ($walmart->walmart_price ?? 0) > 0 ? (($walmart->walmart_price * 0.85 - $lp - $ship) / $lp) : 0,

                // eBay2
                'ebay2_price' => $ebay2 ? (float) ($ebay2->{'ebay_price'} ?? 0) : 0,
                'ebay2_l30'   => $ebay2 ? (float) ($ebay2->{'ebay_l30'} ?? 0) : 0,
                'ebay2_dil'   => $ebay2 ? (float) ($ebay2->{'dil'} ?? 0) : 0,
                'ebay2_pft'   => $ebay2 && ($ebay2->ebay_price ?? 0) > 0 ? (($ebay2->ebay_price * 0.87 - $lp - $ship) / $ebay2->ebay_price) : 0,
                'ebay2_roi'   => $ebay2 && $lp > 0 && ($ebay2->ebay_price ?? 0) > 0 ? (($ebay2->ebay_price * 0.87 - $lp - $ship) / $lp) : 0,

                // eBay3
                'ebay3_price' => $ebay3 ? (float) ($ebay3->{'ebay_price'} ?? 0) : 0,
                'ebay3_l30'   => $ebay3 ? (float) ($ebay3->{'ebay_l30'} ?? 0) : 0,
                'ebay3_dil'   => $ebay3 ? (float) ($ebay3->{'dil'} ?? 0) : 0,
                'ebay3_pft'   => $ebay3 && ($ebay3->ebay_price ?? 0) > 0 ? (($ebay3->ebay_price * 0.87 - $lp - $ship) / $ebay3->ebay_price) : 0,
                'ebay3_roi'   => $ebay3 && $lp > 0 && ($ebay3->ebay_price ?? 0) > 0 ? (($ebay3->ebay_price * 0.87 - $lp - $ship) / $lp) : 0,

                // Pricing
                'sprice'          => $pricing->sprice ?? null,
                'sprofit_percent' => $pricing->sprofit_percent ?? null,
                'sroi_percent'    => $pricing->sroi_percent ?? null
            ];

            // Add shopifyb2c fields after $item is created
            $shopify = $shopifyData[trim(strtoupper($sku))] ?? null;
            $item->shopifyb2c_price = $shopify ? $shopify->price : 0;
            $item->shopifyb2c_l30 = $shopify ? $shopify->shopify_l30 : 0;
            $item->shopifyb2c_image = $shopify ? $shopify->image_src : null;
            $item->shopifyb2c_pft = $item->shopifyb2c_price > 0 ? (($item->shopifyb2c_price * 0.75 - $lp - $ship) / $item->shopifyb2c_price) : 0;
            $item->shopifyb2c_roi = ($lp > 0 && $item->shopifyb2c_price > 0) ? (($item->shopifyb2c_price * 0.75 - $lp - $ship) / $lp) : 0;

            // Add analysis action buttons
            $item->l30_analysis = '<button class="btn btn-sm btn-info" onclick="showL30Modal(this)" data-sku="' . $item->SKU . '">L30</button>';


            $processedData[] = $item;
        }

        return $processedData;
    }

    public function getViewPricingAnalysisData(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 'all');
        $dilFilter = $request->input('dil_filter', 'all');
        $dataType = $request->input('data_type', 'all');
        $searchTerm = $request->input('search', '');
        $parentFilter = $request->input('parent', '');
        $skuFilter = $request->input('sku', '');
        $distinctOnly = $request->input('distinct_only', false);

        if ($perPage === 'all') {
            $perPage = 1000000;
        } else {
            $perPage = (int) $perPage;
        }

        $processedData = $this->processPricingData($searchTerm);

        $filteredData = $this->applyFilters($processedData, $dilFilter, $dataType, $parentFilter, $skuFilter);

        if ($distinctOnly) {
            return response()->json([
                'distinct_values' => $this->getDistinctValues($processedData),
                'status' => 200,
            ]);
        }

        $total = count($filteredData);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($filteredData, $offset, $perPage);


        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $paginatedData,
            'distinct_values' => $this->getDistinctValues($processedData),
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'status' => 200,
        ]);
    }

    protected function applyFilters($data, $dilFilter, $dataType, $parentFilter, $skuFilter)
    {
        return array_filter($data, function ($item) use ($dilFilter, $dataType, $parentFilter, $skuFilter) {
            if ($dilFilter !== 'all') {
                $dilPercent = ($item->{'Dil%'} ?? 0) * 100;
                switch ($dilFilter) {
                    case 'red':
                        if ($dilPercent >= 16.66) {
                            return false;
                        }
                        break;
                    case 'yellow':
                        if ($dilPercent < 16.66 || $dilPercent >= 25) {
                            return false;
                        }
                        break;
                    case 'green':
                        if ($dilPercent < 25 || $dilPercent >= 50) {
                            return false;
                        }
                        break;
                    case 'pink':
                        if ($dilPercent < 50) {
                            return false;
                        }
                        break;
                }
            }

            if ($dataType !== 'all') {
                $isParent = stripos($item->SKU ?? '', 'PARENT') !== false;
                if ($dataType === 'parent' && !$isParent) {
                    return false;
                }
                if ($dataType === 'sku' && $isParent) {
                    return false;
                }
            }

            if ($parentFilter && $item->Parent !== $parentFilter) {
                return false;
            }
            if ($skuFilter && $item->SKU !== $skuFilter) {
                return false;
            }

            return true;
        });
    }


    protected function getDistinctValues($data)
    {
        $parents = [];
        $skus = [];

        foreach ($data as $item) {
            if (!empty($item->Parent)) {
                $parents[$item->Parent] = true;
            }
            if (!empty($item->SKU)) {
                $skus[$item->SKU] = true;
            }
        }

        return [
            'parents' => array_keys($parents),
            'skus' => array_keys($skus),
        ];
    }

    public function getL30Analysis(Request $request)
    {
        $sku = $request->input('sku');
        $processedData = $this->processPricingData();

        $item = collect($processedData)->firstWhere('SKU', $sku);
        if (!$item) {
            return response()->json([
                'message' => 'SKU not found',
                'status' => 404
            ]);
        }

        $analysis = [
            'amazon' => [
                'l30' => $item->amz_l30,
                'l60' => $item->amz_l60,
                'profit' => $item->amz_price > 0 ? ($item->amz_price * 0.85 - $item->LP - $item->SHIP) / $item->amz_price : 0,
                'roi' => $item->LP > 0 ? ($item->amz_price * 0.85 - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'ebay' => [
                'l30' => $item->ebay_l30,
                'profit' => $item->ebay_price > 0 ? ($item->ebay_price * 0.87 - $item->LP - $item->SHIP) / $item->ebay_price : 0,
                'roi' => $item->LP > 0 ? ($item->ebay_price * 0.87 - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'doba' => [
                'l30' => $item->doba_l30,
                'l60' => $item->doba_l60,
                'profit' => $item->doba_price > 0 ? ($item->doba_price - $item->LP - $item->SHIP) / $item->doba_price : 0,
                'roi' => $item->LP > 0 ? ($item->doba_price - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'macy' => [
                'l30' => $item->macy_l30,
                'profit' => $item->macy_pft,
                'roi' => $item->macy_roi
            ],
            'reverb' => [
                'l30' => $item->reverb_l30,
                'profit' => $item->reverb_pft,
                'roi' => $item->reverb_roi
            ],
            'temu' => [
                'l30' => $item->temu_l30,
                'profit' => $item->temu_pft,
                'roi' => $item->temu_roi
            ],
            'walmart' => [
                'l30' => $item->walmart_l30,
                'profit' => $item->walmart_pft,
                'roi' => $item->walmart_roi
            ],
            'ebay2' => [
                'l30' => $item->ebay2_l30,
                'profit' => $item->ebay2_pft,
                'roi' => $item->ebay2_roi
            ],
            'ebay3' => [
                'l30' => $item->ebay3_l30,
                'profit' => $item->ebay3_pft,
                'roi' => $item->ebay3_roi
            ]
        ];

        return response()->json([
            'data' => $analysis,
            'status' => 200
        ]);
    }

    public function getSiteAnalysis(Request $request)
    {
        $sku = $request->input('sku');
        $processedData = $this->processPricingData();

        $item = collect($processedData)->firstWhere('SKU', $sku);
        if (!$item) {
            return response()->json([
                'message' => 'SKU not found',
                'status' => 404
            ]);
        }

        $analysis = [
            'amazon' => [
                'price' => $item->amz_price,
                'l30' => $item->amz_l30,
                'profit' => $item->amz_price > 0 ? ($item->amz_price * 0.85 - $item->LP - $item->SHIP) / $item->amz_price : 0,
                'roi' => $item->LP > 0 ? ($item->amz_price * 0.85 - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'ebay' => [
                'price' => $item->ebay_price,
                'l30' => $item->ebay_l30,
                'profit' => $item->ebay_price > 0 ? ($item->ebay_price * 0.87 - $item->LP - $item->SHIP) / $item->ebay_price : 0,
                'roi' => $item->LP > 0 ? ($item->ebay_price * 0.87 - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'doba' => [
                'price' => $item->doba_price,
                'l30' => $item->doba_l30,
                'profit' => $item->doba_price > 0 ? ($item->doba_price - $item->LP - $item->SHIP) / $item->doba_price : 0,
                'roi' => $item->LP > 0 ? ($item->doba_price - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'macy' => [
                'price' => $item->macy_price,
                'l30' => $item->macy_l30,
                'profit' => $item->macy_pft,
                'roi' => $item->macy_roi
            ],
            'reverb' => [
                'price' => $item->reverb_price,
                'l30' => $item->reverb_l30,
                'profit' => $item->reverb_pft,
                'roi' => $item->reverb_roi
            ],
            'temu' => [
                'price' => $item->temu_price,
                'l30' => $item->temu_l30,
                'profit' => $item->temu_pft,
                'roi' => $item->temu_roi
            ],
            'walmart' => [
                'price' => $item->walmart_price,
                'l30' => $item->walmart_l30,
                'profit' => $item->walmart_pft,
                'roi' => $item->walmart_roi
            ],
            'ebay2' => [
                'price' => $item->ebay2_price,
                'l30' => $item->ebay2_l30,
                'profit' => $item->ebay2_pft,
                'roi' => $item->ebay2_roi
            ],
            'ebay3' => [
                'price' => $item->ebay3_price,
                'l30' => $item->ebay3_l30,
                'profit' => $item->ebay3_pft,
                'roi' => $item->ebay3_roi
            ]
        ];

        return response()->json([
            'data' => $analysis,
            'status' => 200
        ]);
    }

    public function getProfitAnalysis(Request $request)
    {
        $sku = $request->input('sku');
        $processedData = $this->processPricingData();

        $item = collect($processedData)->firstWhere('SKU', $sku);
        if (!$item) {
            return response()->json([
                'message' => 'SKU not found',
                'status' => 404
            ]);
        }

        $analysis = [
            'amazon' => [
                'price' => $item->amz_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->amz_price * 0.85 - $item->LP - $item->SHIP,
                'margin' => $item->amz_price > 0 ? ($item->amz_price * 0.85 - $item->LP - $item->SHIP) / $item->amz_price : 0
            ],
            'ebay' => [
                'price' => $item->ebay_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->ebay_price * 0.87 - $item->LP - $item->SHIP,
                'margin' => $item->ebay_price > 0 ? ($item->ebay_price * 0.87 - $item->LP - $item->SHIP) / $item->ebay_price : 0
            ],
            'doba' => [
                'price' => $item->doba_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->doba_price - $item->LP - $item->SHIP,
                'margin' => $item->doba_price > 0 ? ($item->doba_price - $item->LP - $item->SHIP) / $item->doba_price : 0
            ],
            'macy' => [
                'price' => $item->macy_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->macy_price * 0.77 - $item->LP - $item->SHIP,
                'margin' => $item->macy_pft
            ],
            'reverb' => [
                'price' => $item->reverb_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->reverb_price * 0.77 - $item->LP - $item->SHIP,
                'margin' => $item->reverb_pft
            ],
            'temu' => [
                'price' => $item->temu_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->temu_price * $item->temu_pft,
                'margin' => $item->temu_pft
            ],
            'walmart' => [
                'price' => $item->walmart_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->walmart_price * $item->walmart_pft,
                'margin' => $item->walmart_pft
            ],
            'ebay2' => [
                'price' => $item->ebay2_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->ebay2_price * $item->ebay2_pft,
                'margin' => $item->ebay2_pft
            ],
            'ebay3' => [
                'price' => $item->ebay3_price,
                'cost' => $item->LP + $item->SHIP,
                'profit' => $item->ebay3_price * $item->ebay3_pft,
                'margin' => $item->ebay3_pft
            ]
        ];

        return response()->json([
            'data' => $analysis,
            'status' => 200
        ]);
    }

    public function getRoiAnalysis(Request $request)
    {
        $sku = $request->input('sku');
        $processedData = $this->processPricingData();

        $item = collect($processedData)->firstWhere('SKU', $sku);
        if (!$item) {
            return response()->json([
                'message' => 'SKU not found',
                'status' => 404
            ]);
        }

        $analysis = [
            'amazon' => [
                'revenue' => $item->amz_price * $item->amz_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->amz_l30,
                'return' => $item->amz_price * 0.85 * $item->amz_l30 - ($item->LP + $item->SHIP) * $item->amz_l30,
                'roi' => $item->LP > 0 ? ($item->amz_price * 0.85 - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'ebay' => [
                'revenue' => $item->ebay_price * $item->ebay_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->ebay_l30,
                'return' => $item->ebay_price * 0.87 * $item->ebay_l30 - ($item->LP + $item->SHIP) * $item->ebay_l30,
                'roi' => $item->LP > 0 ? ($item->ebay_price * 0.87 - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'doba' => [
                'revenue' => $item->doba_price * $item->doba_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->doba_l30,
                'return' => $item->doba_price * $item->doba_l30 - ($item->LP + $item->SHIP) * $item->doba_l30,
                'roi' => $item->LP > 0 ? ($item->doba_price - $item->LP - $item->SHIP) / $item->LP : 0
            ],
            'macy' => [
                'revenue' => $item->macy_price * $item->macy_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->macy_l30,
                'return' => $item->macy_price * 0.77 * $item->macy_l30 - ($item->LP + $item->SHIP) * $item->macy_l30,
                'roi' => $item->macy_roi
            ],
            'reverb' => [
                'revenue' => $item->reverb_price * $item->reverb_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->reverb_l30,
                'return' => $item->reverb_price * 0.77 * $item->reverb_l30 - ($item->LP + $item->SHIP) * $item->reverb_l30,
                'roi' => $item->reverb_roi
            ],
            'temu' => [
                'revenue' => $item->temu_price * $item->temu_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->temu_l30,
                'return' => $item->temu_price * $item->temu_l30 * $item->temu_pft,
                'roi' => $item->temu_roi
            ],
            'walmart' => [
                'revenue' => $item->walmart_price * $item->walmart_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->walmart_l30,
                'return' => $item->walmart_price * $item->walmart_l30 * $item->walmart_pft,
                'roi' => $item->walmart_roi
            ],
            'ebay2' => [
                'revenue' => $item->ebay2_price * $item->ebay2_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->ebay2_l30,
                'return' => $item->ebay2_price * $item->ebay2_l30 * $item->ebay2_pft,
                'roi' => $item->ebay2_roi
            ],
            'ebay3' => [
                'revenue' => $item->ebay3_price * $item->ebay3_l30,
                'investment' => ($item->LP + $item->SHIP) * $item->ebay3_l30,
                'return' => $item->ebay3_price * $item->ebay3_l30 * $item->ebay3_pft,
                'roi' => $item->ebay3_roi
            ]
        ];

        return response()->json([
            'data' => $analysis,
            'status' => 200
        ]);
    }
}
