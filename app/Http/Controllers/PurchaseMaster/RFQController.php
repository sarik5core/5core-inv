<?php

namespace App\Http\Controllers\PurchaseMaster;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\RfqForm;
use Illuminate\Http\Request;

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
        // Validation
        $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'rfq_form_name' => 'required|string|max:255',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string',
            'main_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        // Handle Image Upload
        $imagePath = null;
        if ($request->hasFile('main_image')) {
            $imagePath = $request->file('main_image')->store('rfq_forms', 'public');
        }

        // Create RFQ Form
        RfqForm::create([
            'category_id'   => $request->category_id,
            'rfq_form_name' => $request->rfq_form_name,
            'title'         => $request->title,
            'subtitle'      => $request->subtitle,
            'main_image'    => $imagePath,
        ]);

        return redirect()->back()->with('flash_message', 'RFQ Form created successfully!');
    }

    public function showRfqForm($slug)
    {
        $rfqForm = RfqForm::with('category')->where('slug', $slug)->firstOrFail();

        return view('purchase-master.rfq-form.rfq-form', compact('rfqForm'));
    }
}
