<?php

namespace App\Http\Controllers\PurchaseMaster;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\RfqForm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RFQController extends Controller
{
    public function index()
    {
        $categories = Category::all();

        // $rfqForms = RfqForm::all();

        return view('purchase-master.rfq-form.index', compact('categories'));
    }

    public function storeRFQForm(Request $request)
    {
        $request->validate([
            'rfq_form_name' => 'required|string',
            'title' => 'required|string',
            'fields' => 'required|array',
            'main_image' => 'nullable|image|max:2048'
        ]);

        $slug = Str::slug($request->rfq_form_name);

        $imagePath = null;
        if($request->hasFile('main_image')){
            $imagePath = $request->file('main_image')->store('rfq_forms', 'public');
        }
        $fields = collect($request->fields)->map(function($field, $index) {
            $field['order'] = $field['order'] ?? ($index + 1);
            return $field;
        })->toArray();

        RfqForm::create([
            'name' => $request->rfq_form_name,
            'title' => $request->title,
            'slug' => $slug,
            'main_image' => $imagePath,
            'subtitle' => $request->subtitle,
            'fields' => $fields,
            'dimension_inner' => $request->dimension_inner,
            'product_dimension' => $request->product_dimension,
            'package_dimension' => $request->package_dimension,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'RFQ Form created successfully!',
        ]);
    }

    public function showRfqForm($slug)
    {
        $rfqForm = RfqForm::where('slug', $slug)->firstOrFail();

        return view('purchase-master.rfq-form.rfq-form', compact('rfqForm'));
    }
}
