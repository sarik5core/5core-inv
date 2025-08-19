@extends('layouts.vertical', ['title' => 'Categories', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
@include('layouts.shared.page-title', ['page_title' => 'Categories', 'sub_title' => 'Categories'])

{{-- ✅ Flash Message --}}
@if(Session::has('flash_message'))
<div class="alert alert-primary bg-primary text-white alert-dismissible fade show" role="alert" style="background-color: #03a744 !important; color: #fff !important;">
    {{ Session::get('flash_message') }}
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif


<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                {{-- ✅ Add Category Button --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Categories</h4>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="select-all">
                            <label class="form-check-label" for="select-all">Select All</label>
                        </div>
                        <button id="bulkDeleteBtn" class="btn btn-danger btn-sm" disabled>
                            <i class="mdi mdi-delete"></i> Delete Selected
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="mdi mdi-plus me-1"></i> Add Category
                        </button>
                    </div>
                </div>


                <!-- Add Category Modal -->
                <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered shadow-none">
                        <div class="modal-content border-0">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="addCategoryModalLabel">
                                    <i class="mdi mdi-plus-circle me-1"></i> <span id="modalTitle">Add New
                                        Category</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form action="{{ route('category.create') }}" method="POST" id="addCategoryForm">
                                @csrf
                                <div class="modal-body p-4">
                                    <div class="mb-3">
                                        <label for="category_name" class="form-label fw-semibold">Category Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="category_name" name="category_name"
                                            placeholder="Enter category name">
                                    </div>
                                    <div class="mb-3">
                                        <label for="category_status" class="form-label fw-semibold">Status </label>
                                        <select class="form-select" id="category_status" name="status">
                                            <option value="" disabled selected>Select Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="mdi mdi-content-save me-1"></i> Save Category
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- 🔍 Search Bar --}}
                <div class="row d-flex justify-content-end align-items-center mb-3">
                    <div class="col-md-4">
                        <label for="search-input" class="form-label fw-semibold">Search</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control" placeholder="Search categories...">
                            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-centered table-hover mb-0 border" id="category-table-body">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center align-middle" style="width: 5%">#</th>
                                <th class="text-center align-middle" style="width: 30%">Category Name</th>
                                <th class="text-center align-middle" style="width: 30%">Suppliers</th>
                                <th class="text-center align-middle" style="width: 15%">Status</th>
                                <th class="text-center align-middle" style="width: 20%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- rows partial --}}
                            @include('purchase-master.category.partials.rows', ['categories' => $categories])
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <div class="pagination-wrapper" id="pagination-wrapper">
                        {{ $categories->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>

                <style>
                    .pagination-wrapper {
                        width: auto;
                        overflow-x: auto;
                    }

                    .pagination-wrapper .pagination {
                        margin: 0;
                        background: #fff;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
                        border-radius: 4px;
                        display: flex;
                        flex-wrap: nowrap;
                        gap: 4px;
                    }

                    .pagination-wrapper .page-item .page-link {
                        padding: 0.5rem 1rem;
                        min-width: 40px;
                        text-align: center;
                        color: #464646;
                        border: 1px solid #f1f1f1;
                        font-weight: 500;
                        transition: all 0.2s ease;
                        border-radius: 6px;
                    }

                    .pagination-wrapper .page-item.active .page-link {
                        background: linear-gradient(135deg, #727cf5, #6366f1);
                        border: none;
                        color: white;
                        font-weight: 600;
                        box-shadow: 0 2px 4px rgba(114, 124, 245, 0.2);
                    }

                    .pagination-wrapper .page-item .page-link:hover:not(.active) {
                        background-color: #f8f9fa;
                        color: #727cf5;
                        border-color: #e9ecef;
                    }

                    /* Hide the "Showing x to y of z results" text */
                    /* Hide the "Showing x to y of z results" text */
                    .pagination-wrapper p.small,
                    .pagination-wrapper div.flex.items-center.justify-between {
                        display: none !important;
                    }

                    @media (max-width: 576px) {
                        .pagination-wrapper .page-item .page-link {
                            padding: 0.4rem 0.8rem;
                            min-width: 35px;
                            font-size: 0.875rem;
                        }
                    }
                </style>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // ✅ Search functionality with debounce
        let searchTimer;
        $('#search-input').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const value = $(this).val().toLowerCase();
                $("#category-table-body tbody tr").each(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            }, 300);
        });
    });

    $(document).ready(function () {
        // Toggle All
        $('#select-all').on('change', function () {
            const isChecked = $(this).is(':checked');
            $('.category-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Enable/disable delete button
        $(document).on('change', '.category-checkbox', function () {
            const anyChecked = $('.category-checkbox:checked').length > 0;
            $('#bulkDeleteBtn').prop('disabled', !anyChecked);
        });

        // Handle bulk delete
        $('#bulkDeleteBtn').click(function () {
            const ids = $('.category-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (!ids.length) return;

            $.ajax({
                url: "{{ route('category.bulk-delete') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function (res) {
                    if (res.success) {
                        location.reload(); // or manually remove rows
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function () {
                    alert('Server error occurred.');
                }
            });
        });
    });
</script>
@endsection