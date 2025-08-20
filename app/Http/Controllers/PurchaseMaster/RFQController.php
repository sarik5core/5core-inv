<?php

namespace App\Http\Controllers\PurchaseMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RFQController extends Controller
{
    public function index()
    {
        // Fixed fields
        $fixedFields = [
            ['name' => 'supplier_name', 'label' => 'Supplier Name', 'type' => 'text'],
            ['name' => 'company_name', 'label' => 'Company Name', 'type' => 'text'],
            ['name' => 'supplier_link', 'label' => 'Supplier Link', 'type' => 'url'],
            ['name' => 'product_name', 'label' => 'Product Name', 'type' => 'text'],
            ['name' => 'usd_price', 'label' => 'USD Price', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'rmb_price', 'label' => 'RMB Price', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'moq', 'label' => 'MOQ', 'type' => 'number'],
            ['name' => 'price_type', 'label' => 'Price Type', 'type' => 'select', 'options' => ['Per Unit', 'Per KG', 'Per Set']],
            ['name' => 'main_image_url', 'label' => 'Main Product Image URL', 'type' => 'url'],
            ['name' => 'additional_images_urls', 'label' => 'Additional Images URLs', 'type' => 'textarea'],
        ];

        // Dynamic fields by category
        $dynamicFields = [
            'packaging' => [
                ['name' => 'packing_type', 'label' => 'Packing Type', 'type' => 'text'],
                ['name' => 'packing_gsm', 'label' => 'Packing GSM', 'type' => 'number'],
                ['name' => 'length_cm', 'label' => 'Length (cm)', 'type' => 'number'],
                ['name' => 'width_cm', 'label' => 'Width (cm)', 'type' => 'number'],
                ['name' => 'height_cm', 'label' => 'Height (cm)', 'type' => 'number'],
            ],
            'furniture' => [
                ['name' => 'product_width_cm', 'label' => 'Product Width (cm)', 'type' => 'number'],
                ['name' => 'product_depth_cm', 'label' => 'Product Depth (cm)', 'type' => 'number'],
                ['name' => 'product_height_cm', 'label' => 'Product Height (cm)', 'type' => 'number'],
                ['name' => 'cbm', 'label' => 'CBM', 'type' => 'number', 'step' => '0.001'],
            ],
            'lock' => [
                ['name' => 'locking_mechanism_image', 'label' => 'Locking Mechanism Image URL', 'type' => 'url'],
            ]
        ];

        return view('purchase-master.rfq-form.index', compact('fixedFields', 'dynamicFields'));
    }
}
