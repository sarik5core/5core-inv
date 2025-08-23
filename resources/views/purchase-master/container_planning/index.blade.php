@extends('layouts.vertical', ['title' => 'Container Planning'])
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
    @include('layouts.shared.page-title', [
        'page_title' => 'Container Planning',
        'sub_title' => 'Container Planning',
    ])

    @if (Session::has('flash_message'))
        <div class="alert alert-primary bg-primary text-white alert-dismissible fade show" role="alert"
            style="background-color: #169e28 !important; color: #fff !important;">
            {{ Session::get('flash_message') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-nowrap">
                        <!-- Search -->
                        <div class="input-group" style="max-width: 225px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="container-planning-search" class="form-control border-start-0"
                                placeholder="Search Container Number, PO Number...">
                        </div>

                        <!-- Container Filter -->
                        <select id="filter-container" class="form-select" style="width: 180px;">
                            <option value="">Filter by Container</option>
                            @foreach ($containers as $container)
                                <option value="{{ $container->tab_name }}">{{ $container->tab_name }}</option>
                            @endforeach
                        </select>

                        <!-- Supplier Filter -->
                        <select id="filter-supplier" class="form-select" style="width: 180px;">
                            <option value="">Filter by Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>

                        <!-- Balance Info -->
                        <div class="d-flex align-items-center gap-3">
                            <span id="supplier-balance-container" class="fw-bold d-none">Supplier Balance: <span id="supplier-balance" class="text-primary">0.00</span></span>
                            <span class="fw-bold">Total Balance: <span id="total-balance" class="text-primary">0.00</span></span>
                        </div>

                        <!-- Buttons -->
                        <button class="btn btn-sm btn-danger d-none" id="delete-selected-btn">
                            <i class="fas fa-trash-alt me-1"></i> Delete Selected
                        </button>
                        <button id="add-new-row" class="btn btn-sm btn-success" data-bs-toggle="modal"
                            data-bs-target="#createContainerPlanning">
                            <i class="fas fa-plus-circle me-1"></i> Add Container Planning
                        </button>
                    </div>

                    <div id="container-planning"></div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="createContainerPlanning" tabindex="-1" aria-labelledby="createContainerPlanningLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered shadow-none">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="createContainerPlanningLabel">
                        <i class="fas fa-file-invoice me-2"></i> Create Container Planning
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('container.planning.save') }}" enctype="multipart/form-data"
                    autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Container Number -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Container Number</label>
                                <select name="container_number" class="form-select" required>
                                    <option value="">Select Container</option>
                                    @foreach ($containers as $container)
                                        <option value="{{ $container->tab_name }}">{{ $container->tab_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- PO Number Link -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PO Number</label>
                                <select name="po_number" class="form-select" required>
                                    <option value="">Select PO Number</option>
                                    @foreach ($purchaseOrders as $order)
                                        <option value="{{ $order->po_number }}">{{ $order->po_number }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Supplier Name -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Supplier Name</label>
                                <select name="supplier_id" class="form-select" required>
                                    <option value="">Select Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Area -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Area</label>
                                <input type="text" name="area" class="form-control">
                            </div>

                            <!-- Packing List Link -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Packing List Link</label>
                                <input type="url" name="packing_list_link" class="form-control">
                            </div>

                            <!-- Invoice Value -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Invoice Value</label>
                                <input type="number" step="0.01" name="invoice_value" class="form-control"
                                    step="any">
                            </div>

                            <!-- Paid -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Paid</label>
                                <input type="number" step="0.01" name="paid" class="form-control"
                                    step="any">
                            </div>

                            <!-- Balance -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Balance</label>
                                <input type="number" step="0.01" name="balance" class="form-control" readonly>
                            </div>

                            <!-- Pay Term -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Pay Term</label>
                                <select name="pay_term" class="form-select">
                                    <option value="">Select Term</option>
                                    <option value="EXW">EXW</option>
                                    <option value="FOB">FOB</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const table = new Tabulator("#container-planning", {
                ajaxURL: "/container-planning/data",
                ajaxConfig: "GET",
                layout: "fitData",
                pagination: true,
                paginationSize: 50,
                paginationMode: "local",
                movableColumns: false,
                resizableColumns: true,
                height: "500px",
                columns: [{
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        width: 50
                    },
                    {
                        title: "Sr. No",
                        formatter: "rownum",
                        hozAlign: "center",
                        width: 90
                    },
                    {
                        title: "Container No",
                        field: "container_number"
                    },
                    {
                        title: "PO Number",
                        field: "po_number"
                    },
                    {
                        title: "Supplier",
                        field: "supplier_name"
                    },
                    {
                        title: "Area",
                        field: "area"
                    },
                    {
                        title: "Packing List",
                        field: "packing_list_link",
                        formatter: "link",
                        formatterParams: {
                            labelField: "packing_list_link",
                            urlField: "packing_list_link",
                            target: "_blank"
                        }
                    },
                    {
                        title: "Invoice",
                        field: "invoice_value"
                    },
                    {
                        title: "Paid",
                        field: "paid"
                    },
                    {
                        title: "Balance",
                        field: "balance"
                    },
                    {
                        title: "Pay Term",
                        field: "pay_term"
                    },
                    {
                        title: "Created At",
                        field: "created_at"
                    },
                ],
                ajaxResponse: function(url, params, response) {
                    updateBalances();
                    return response;
                }
            });

            table.on("dataLoaded", updateBalances);
            table.on("dataFiltered", updateBalances);

            function updateBalances() {
                const allData = table.getData();
                const filteredData = table.getData("active");

                let totalBalance = 0;
                let supplierBalance = 0;

                const supplierId = document.getElementById("filter-supplier").value;

                allData.forEach(row => {
                    totalBalance += parseFloat(row.balance || 0);
                });

                filteredData.forEach(row => {
                    if (supplierId && row.supplier_id == supplierId) {
                        supplierBalance += parseFloat(row.balance || 0);
                    }
                });

                document.getElementById("total-balance").innerText = totalBalance.toFixed(0);

                const supplierContainer = document.getElementById("supplier-balance-container");
                if (supplierId) {
                    supplierContainer.classList.remove("d-none");
                    document.getElementById("supplier-balance").innerText = supplierBalance.toFixed(0);
                } else {
                    supplierContainer.classList.add("d-none");
                }
            }


            document.getElementById("container-planning-search").addEventListener("input", function(e) {
                const keyword = e.target.value.toLowerCase();

                table.setFilter([
                    [{
                            field: "container_number",
                            type: "like",
                            value: keyword
                        },
                        {
                            field: "po_number",
                            type: "like",
                            value: keyword
                        },
                    ],

                ]);
                updateBalances();
            });

            document.getElementById("filter-container").addEventListener("change", function() {
                const container = this.value;
                table.setFilter("container_number", container ? "=" : "like", container);
                updateBalances();

                const rows = table.searchRows("container_number", "=", container);
                if (rows.length > 0) {
                    const supplierId = rows[0].getData().supplier_id;
                    document.getElementById("filter-supplier").value = supplierId;
                    table.setFilter("supplier_id", "=", supplierId);
                    updateBalances();
                }
            });

            document.getElementById("filter-supplier").addEventListener("change", function() {
                const supplierId = this.value;
                table.setFilter("supplier_id", supplierId ? "=" : "like", supplierId);
                updateBalances();
            });

            table.on("rowSelectionChanged", function(data, rows) {
                if (data.length > 0) {
                    $('#delete-selected-btn').removeClass('d-none');
                } else {
                    $('#delete-selected-btn').addClass('d-none');
                }
            });

            $('#delete-selected-btn').on('click', function() {
                const selectedData = table.getSelectedData();

                if (selectedData.length === 0) {
                    alert('Please select at least one record to delete.');
                    return;
                }

                if (!confirm(`Are you sure you want to delete ${selectedData.length} selected records?`)) {
                    return;
                }

                const ids = selectedData.map(row => row.id);

                $.ajax({
                    url: '/container-planning/delete',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: ids
                    },
                    success: function(response) {
                        table.deleteRow(ids);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            fetchPurchaseDetails();

            function fetchPurchaseDetails() {
                const poSelect = document.querySelector('select[name="po_number"]');
                const supplierSelect = document.querySelector('select[name="supplier_id"]');
                const invoiceValue = document.querySelector('input[name="invoice_value"]');
                const paid = document.querySelector('input[name="paid"]');
                const balance = document.querySelector('input[name="balance"]');

                poSelect.addEventListener('change', function() {
                    let poId = this.value;
                    if (!poId) return;

                    fetch(`/container-planning/po-details/${poId}`)
                        .then(res => res.json())
                        .then(data => {
                            supplierSelect.value = data.supplier_id;
                            invoiceValue.value = data.total_amount;
                            paid.value = data.advance_amount;
                            balance.value = data.balance;
                        })
                        .catch(err => console.error(err));
                });
            }
        });
    </script>
@endsection
