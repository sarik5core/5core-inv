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
                    @forelse($rfqForms as $form)
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card border-1 border-primary shadow-sm h-100">
                                <div class="position-relative">
                                    @if($form->main_image)
                                        <img src="{{ asset('storage/' . $form->main_image) }}" 
                                             class="card-img-top" 
                                             alt="{{ $form->title }}" 
                                             style="height: 100px; object-fit: cover;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 100px;">
                                            <i class="fas fa-file-alt fa-lg text-muted"></i>
                                        </div>
                                    @endif
                                    <span class="badge bg-primary position-absolute top-0 end-0 m-2" style="font-size: 0.7rem;">
                                        {{ $form->category->name }}
                                    </span>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1" style="font-size: 0.9rem;">{{ Str::limit($form->title, 40) }}</h6>
                                    <p class="card-text text-muted mb-2" style="font-size: 0.75rem;">{{ Str::limit($form->subtitle, 60) }}</p>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('rfq-form.show', $form->slug) }}" class="btn btn-soft-primary btn-sm py-1 px-2" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="" class="btn btn-soft-info btn-sm py-1 px-2" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-soft-danger btn-sm py-1 px-2" 
                                                    onclick="return confirm('Are you sure?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-folder-open text-muted opacity-50" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mt-2 mb-0">No RFQ forms available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createRFQFormModal" tabindex="-1" aria-labelledby="createRFQFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered shadow-none">
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
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Select Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="rfq_form_name" class="form-label">RFQ Form Name <span class="text-danger">*</span></label>
                                <input type="text" name="rfq_form_name" id="rfq_form_name" class="form-control" placeholder="Enter form name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Form Heading / Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="Enter form heading" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="main_image" class="form-label">Form Image (optional)</label>
                                <input type="file" name="main_image" id="main_image" class="form-control" accept="image/*">
                                <img id="mainImagePreview" src="#" alt="Preview" class="img-fluid mt-2" style="display:none; max-height:150px;">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Description -->
                    <div class="border p-3 rounded">
                        <h6 class="fw-bold mb-3">Form Description</h6>

                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Form Subtitle / Description</label>
                            <textarea name="subtitle" id="subtitle" class="form-control" rows="4" placeholder="Enter form description"></textarea>
                        </div>
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
        
    });
</script>
@endsection