@extends('layouts.vertical', ['title' => 'RFQ Form'])
@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
<style>
    /* Pagination styling */
    .tabulator .tabulator-footer .tabulator-paginator .tabulator-page {
        padding: 8px 16px;
        margin: 0 4px;
        border-radius: 6px;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .tabulator .tabulator-footer .tabulator-paginator .tabulator-page:hover {
        background: #e0eaff;
        color: #2563eb;
    }

    .tabulator .tabulator-footer .tabulator-paginator .tabulator-page.active {
        background: #2563eb;
        color: white;
    }
</style>
@endsection
@section('content')
@include('layouts.shared.page-title', ['page_title' => 'RFQ Form', 'sub_title' => 'RFQ Form'])

@if (Session::has('flash_message'))
    <div class="alert alert-primary bg-primary text-white alert-dismissible fade show" role="alert"
        style="background-color: #03a744 !important; color: #fff !important;">
        {{ Session::get('flash_message') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
                    <div class="d-flex flex-wrap gap-2">
                        <button id="add-new-row" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createRFQFormModal">
                            <i class="fas fa-plus-circle me-1"></i> Create RFQ Form
                        </button>
                    </div>
                </div>
                <div class="row">
                    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createRFQFormModal" tabindex="-1" aria-labelledby="createRFQFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered shadow-none">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="createRFQFormModalLabel">
                    <i class="fas fa-file-invoice me-2"></i> Create RFQ Form
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="rfqFormCreate" method="POST" action="{{ route('rfq-form.store') }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div class="modal-body">

                    <!-- Section 1: Basic Info -->
                    <div class="border p-3 rounded mb-3">
                        <h6 class="fw-bold mb-3">Basic Information</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="rfq_form_name" class="form-label">RFQ Form Name <span class="text-danger">*</span></label>
                                <input type="text" name="rfq_form_name" id="rfq_form_name" class="form-control" placeholder="Stand Quotation Form" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="title" class="form-label">Form Heading / Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="Enter form heading" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="main_image" class="form-label">Form Image (optional)</label>
                                <input type="file" name="main_image" id="main_image" class="form-control" accept="image/*">
                                <img id="mainImagePreview" src="#" alt="Preview" class="img-fluid mt-2" style="display:none; max-height:150px;">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label for="subtitle" class="form-label">Form Subtitle / Description</label>
                                <textarea name="subtitle" id="subtitle" class="form-control" rows="3" placeholder="Enter form description"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Dynamic Fields -->
                    <div class="border p-3 rounded">
                        <h6 class="fw-bold mb-3">Add Fields</h6>

                        <div id="dynamicFieldsWrapper">
                            <div class="row g-3 mb-2 field-item">
                                <div class="col-md-3">
                                    <input type="text" name="fields[0][label]" class="form-control field-label" placeholder="Field Label" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="fields[0][name]" class="form-control field-name" placeholder="Field Name (auto)" disabled>
                                </div>
                                <div class="col-md-2">
                                    <select name="fields[0][type]" class="form-select field-type">
                                        <option value="text">Text</option>
                                        <option value="number">Number</option>
                                        <option value="select">Select</option>
                                    </select>
                                </div>
                                <div class="col-md-3 select-options-wrapper" style="display:none;">
                                    <input type="text" name="fields[0][options]" class="form-control" placeholder="Options (comma separated)">
                                </div>
                                <div class="col-md-1">
                                    <input type="checkbox" name="fields[0][required]" class="form-check-input mt-2" value="1"> Required
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-field">X</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success btn-sm mt-2" id="addFieldBtn">
                            <i class="fas fa-plus"></i> Add Field
                        </button>
                    </div>


                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Form</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('script')
<script src="https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        let fieldCount = 1;

function slugify(text){
    return text.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
}

function createFieldRow(index){
    return `
    <div class="row g-3 mb-2 field-item">
        <div class="col-md-3">
            <input type="text" name="fields[${index}][label]" class="form-control field-label" placeholder="Field Label" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="fields[${index}][name]" class="form-control field-name" placeholder="Field Name (auto)" required>
        </div>
        <div class="col-md-2">
            <select name="fields[${index}][type]" class="form-select field-type">
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="select">Select</option>
            </select>
        </div>
        <div class="col-md-3 select-options-wrapper" style="display:none;">
            <input type="text" name="fields[${index}][options]" class="form-control" placeholder="Options (comma separated)">
        </div>
        <div class="col-md-1">
            <input type="checkbox" name="fields[${index}][required]" class="form-check-input mt-2" value="1"> Required
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-sm remove-field">X</button>
        </div>
    </div>
    `;
}

// Add new field
document.getElementById('addFieldBtn').addEventListener('click', function(){
    let wrapper = document.getElementById('dynamicFieldsWrapper');
    wrapper.insertAdjacentHTML('beforeend', createFieldRow(fieldCount));
    fieldCount++;
});

// Remove field
document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('remove-field')){
        e.target.closest('.field-item').remove();
    }
});

// Show/hide options input if type is select
document.addEventListener('change', function(e){
    if(e.target && e.target.classList.contains('field-type')){
        let optionsWrapper = e.target.closest('.field-item').querySelector('.select-options-wrapper');
        if(e.target.value === 'select'){
            optionsWrapper.style.display = 'block';
        } else {
            optionsWrapper.style.display = 'none';
        }
    }
});

// Auto-fill field name from label
document.addEventListener('input', function(e){
    if(e.target && e.target.classList.contains('field-label')){
        let nameInput = e.target.closest('.field-item').querySelector('.field-name');
        nameInput.value = slugify(e.target.value);
    }
});




    });
</script>
@endsection