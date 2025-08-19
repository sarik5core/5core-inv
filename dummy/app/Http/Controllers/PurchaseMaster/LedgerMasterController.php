<?php

namespace App\Http\Controllers\PurchaseMaster;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LedgerMasterController extends Controller
{
    public function advanceAndPayments(){
        return view('purchase-master.ledger-master.advance-payments');
    }

    public function supplierLedger(){
        $suppliers = Supplier::where('type', 'Supplier')->get();
        return view('purchase-master.ledger-master.supplier-ledger', compact('suppliers'));
    }

    public function supplierStore(Request $request){
        $validated = $request->validate([
            'supplier' => 'required|integer|exists:suppliers,id',
            'pm_image' => 'nullable|image|max:2048',
            'purchase_link' => 'nullable|url',
            'dr' => 'nullable|numeric',
            'cr' => 'nullable|numeric',
            'balance' => 'required|numeric',
        ]);

        $ledger = new SupplierLedger();
        $ledger->supplier_id = $validated['supplier'];

        if ($request->hasFile('pm_image')) {
            $path = $request->file('pm_image')->store('supplier_ledgers', 'public');
            $ledger->pm_image = $path;
        }

        $ledger->purchase_link = $validated['purchase_link'] ?? null;
        $ledger->dr = $validated['dr'] ?? 0;
        $ledger->cr = $validated['cr'] ?? 0;
        $ledger->balance = $validated['balance'];
        $ledger->save();

        return redirect()->back()->with('flash_message', 'Supplier Ledger entry created successfully.');
    }

    public function fetchSupplierLedgerData(Request $request)
    {
        $ledgers = SupplierLedger::with('supplier:id,name')->orderBy('id', 'desc')->get();

        $data = $ledgers->map(function ($ledger) {
            return [
                'id' => $ledger->id,
                'supplier_name' => $ledger->supplier->name ?? '',
                'pm_image' => $ledger->pm_image ? asset('storage/' . $ledger->pm_image) : '',
                'purchase_link' => $ledger->purchase_link,
                'dr' => $ledger->dr,
                'cr' => $ledger->cr,
                'balance' => $ledger->balance,
            ];
        });

        return response()->json($data);
    }


    public function getSupplierBalance(Request $request)
    {
        $supplierId = $request->input('supplier_id');

        $balance = SupplierLedger::where('supplier_id', $supplierId)
                    ->orderBy('id', 'desc')
                    ->value('balance');

        return response()->json([
            'balance' => $balance ?? 0
        ]);
    }


}
