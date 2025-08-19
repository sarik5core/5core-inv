<?php

namespace App\Http\Controllers\PurchaseMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\ProductMaster;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderController extends Controller
{
    
    public function index()
    {
        $poNumber = $this->generateOrderNumber();
        $suppliers = Supplier::select('id', 'name')->get();
        $orders = PurchaseOrder::with('supplier')->latest()->get();
        return view('purchase-master.purchase-order.purchase-order',compact('suppliers','orders','poNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_number' => 'required|string|unique:purchase_orders,po_number',
            'supplier' => 'required|exists:suppliers,id',
            'sku' => 'required|array',
            'sku.*' => 'nullable|string',
        ]);

        $poNumber = $request->po_number;
        $supplierId = $request->supplier;
        $today = now()->toDateString();

        // Extract arrays from request
        $skus = $request->sku;
        $supplierSkus = $request->supplier_sku ?? [];
        $qtys = $request->qty ?? [];
        $prices = $request->price ?? [];
        $techs = $request->tech ?? [];
        $currencies = $request->currency ?? [];
        $priceTypes = $request->price_type ?? [];
        $nws = $request->nw ?? [];
        $gws = $request->gw ?? [];
        $cbms = $request->cbm ?? [];

        $photos = $request->file('photo') ?? [];
        $barcodes = $request->file('barcode') ?? [];

        $items = [];

        foreach ($skus as $index => $sku) {
            $photoPath = isset($photos[$index]) ? $photos[$index]->store('purchase_orders/photos', 'public') : null;
            $barcodePath = isset($barcodes[$index]) ? $barcodes[$index]->store('purchase_orders/barcodes', 'public') : null;

            $items[] = [
                'sku' => $sku,
                'supplier_sku' => $supplierSkus[$index] ?? null,
                'qty' => $qtys[$index] ?? null,
                'price' => $prices[$index] ?? null,
                'tech' => $techs[$index] ?? null,
                'currency' => $currencies[$index] ?? null,
                'price_type' => $priceTypes[$index] ?? null,
                'nw' => $nws[$index] ?? null,
                'gw' => $gws[$index] ?? null,
                'cbm' => $cbms[$index] ?? null,
                'photo' => $photoPath,
                'barcode' => $barcodePath,
            ];
        }

        PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $supplierId,
            'po_date' => $today,
            'items' => json_encode($items),
        ]);

        return redirect()->back()->with('flash_message', 'PO with all items saved as one row successfully.');
    }

    public function updateField(Request $request)
    {
        $id = $request->input('id');
        $column = $request->input('column');

        if (!$id || !$column) {
            return response()->json(['error' => 'Invalid data'], 422);
        }

        $data = [];

        if ($request->hasFile('photo') && $column === 'photo') {
            $path = $request->file('photo')->store('purchase_orders/photos', 'public');
            $data['photo'] = $path;
            $url = asset("storage/{$path}");
        }

        if ($request->hasFile('barcode') && $column === 'barcode') {
            $path = $request->file('barcode')->store('purchase_orders/barcodes', 'public');
            $data['barcode'] = $path;
            $url = asset("storage/{$path}");
        }

        if (!empty($data)) {
            DB::table('purchase_orders')->where('id', $id)->update($data);
            return response()->json(['success' => true, 'url' => $url]);
        }

        $value = $request->input('value');
        DB::table('purchase_orders')->where('id', $id)->update([$column => $value]);

        return response()->json(['success' => true]);
    }



    public function getPurchaseOrdersData()
    {
        $orders = PurchaseOrder::select('id', 'po_number', 'po_date', 'supplier_id', 'items')
        ->with('supplier:id,name')
        ->get();

        $orders = $orders->map(function ($order) {
            $items = collect(json_decode($order->items));
            $firstItem = $items->first();

            // Multiple SKUs string (limit to 3 for display)
            $skuList = $items->pluck('sku')->take(3)->implode(', ');
            if ($items->count() > 3) {
                $skuList .= '...';
            }

            return [
                'id' => $order->id,
                'po_number' => $order->po_number,
                'po_date' => $order->po_date,
                'supplier_name' => $order->supplier->name ?? '',
                'sku_list' => $skuList,
                'photo' => $firstItem->photo ?? '',
                'barcode' => $firstItem->barcode ?? '',
                'items_json' => $order->items, // full list for modal
            ];
        });

        return response()->json($orders);
    }

    public function generatePdf($orderId){
        $order = DB::table('purchase_orders')->where('id', $orderId)->first();
        if (!$order) abort(404, 'Purchase Order not found');

        $items = json_decode($order->items ?? '[]');

        $supplier = DB::table('suppliers')->where('id', $order->supplier_id)->first();

        return view('purchase-master.purchase-order.proforma', [
            'order'    => $order,
            'items'    => $items,
            'supplier' => $supplier,
        ]);
    }

    public function convert(Request $request)
    {
        $amount = $request->query('amount', 1);
        $from = $request->query('from', 'USD');
        $to = $request->query('to', 'CNY');

        try {
            $apiUrl = "https://api.frankfurter.app/latest?amount=$amount&from=$from&to=$to";
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Frankfurter API error'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    function generateOrderNumber()
    {
        $datePart = Carbon::now()->format('dmy'); 
        $prefix = 'PO-' . $datePart;

        $latestOrder = PurchaseOrder::select('po_number')
            ->where('po_number', 'like', "$prefix-%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($latestOrder) {
            $parts = explode('-', $latestOrder->po_number);
            $lastSerial = intval(end($parts));
            $newSerial = str_pad($lastSerial + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $newSerial = '01';
        }
        return "$prefix-$newSerial";
    }
}
