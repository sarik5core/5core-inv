<?php

namespace App\Http\Controllers;

use App\Models\InventoryWarehouse;
use Illuminate\Http\Request;

class InventoryWarehouseController extends Controller
{

    public function index()
    {
        $warehouses = InventoryWarehouse::latest()->get();

        return view('purchase-master.transit_container.inventory_warehouse', compact('warehouses'));
    }

    public function pushInventory(Request $request)
    {
        $tabName = $request->input('tab_name');
        $rows = $request->input('data', []);

        foreach ($rows as $row) {
            InventoryWarehouse::create([
                'tab_name'          => $row['tab_name'] ?? $tabName,
                'supplier_name'     => $row['supplier_name'] ?? null,
                'company_name'      => $row['company_name'] ?? null,
                'our_sku'           => $row['our_sku'] ?? null,
                'parent'            => $row['parent'] ?? null,
                'no_of_units'       => !empty($row['no_of_units']) ? (int) $row['no_of_units'] : null,
                'total_ctn'         => !empty($row['total_ctn']) ? (int) $row['total_ctn'] : null,
                'rate'              => !empty($row['rate']) ? (float) $row['rate'] : null,
                'unit'              => $row['unit'] ?? null,
                'status'            => $row['status'] ?? null,
                'changes'           => $row['changes'] ?? null,
                'values'            => $row['values'] ?? null,
                'package_size'      => $row['package_size'] ?? null,
                'product_size_link' => $row['product_size_link'] ?? null,
                'comparison_link'   => $row['comparison_link'] ?? null,
                'order_link'        => $row['order_link'] ?? null,
                'image_src'         => $row['image_src'] ?? null,
                'photos'            => $row['photos'] ?? null,
                'specification'     => $row['specification'] ?? null,
                'supplier_names'    => $row['supplier_names'] ?? [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory pushed successfully',
            'count'   => count($rows),
        ]);
    }

}
