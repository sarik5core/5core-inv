@extends('layouts.vertical', ['title' => 'Pricing Masters Analysis'])

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <style>
        #image-hover-preview {
            transition: opacity 0.2s ease;
        }

        /* Table Styling */
        #forecast-table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .inventory-cell {
            font-weight: 600;
            background-color: #f8f9fa;
        }

      
        .inventory-cell.low {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .inventory-cell.medium {
            color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }

        .inventory-cell.high {
            color: #198754;
            background-color: rgba(25, 135, 84, 0.1);
        }

        /* Tooltip styles */
        .cell-tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.2s;
            white-space: nowrap;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .cell-with-tooltip:hover .cell-tooltip {
            visibility: visible;
            opacity: 1;
        }





        .tabulator .tabulator-header {
            background-color: #000000;
            border-bottom: 2px solid #000;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tabulator .tabulator-header .tabulator-col {
            background-color: #000000;
            border-right: 1px solid #373b3e;
            padding: 12px 8px;
            vertical-align: middle;
        }

        .tabulator .tabulator-header .tabulator-col-content {
            font-weight: 600;
            color: #ffffff;
            padding: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tabulator .tabulator-header .tabulator-col-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
        }

        /* Style for header filter inputs */
        .tabulator .tabulator-header .tabulator-col input {
            border: 1px solid #373b3e;
            background-color: #2c3034;
            color: #ffffff;
            border-radius: 4px;
            padding: 4px 8px;
        }

        .tabulator .tabulator-header .tabulator-col input::placeholder {
            color: #6c757d;
        }

        .tabulator .tabulator-row {
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        .tabulator .tabulator-row:hover {
            background-color: #f8f9fa !important;
        }

        .tabulator .tabulator-row.parent-row {
            background-color: #f8f9fa;
            font-weight: 700;
            border-top: 2px solid #0d6efd;
        }

        .tabulator .tabulator-row.parent-row .tabulator-cell {
            color: #0d6efd;
        }

        .tabulator .tabulator-row .tabulator-cell {
            padding: 12px 8px;
            border-right: 1px solid #eee;
            position: relative;
            overflow: visible;
        }

        /* Hover info tooltip */
        .tabulator-cell[data-numeric='true']:hover::after {
            content: attr(data-full-value);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Trending indicators */
        .trend-up::after {
            content: '↑';
            color: #22c55e;
            margin-left: 4px;
        }

        .trend-down::after {
            content: '↓';
            color: #ef4444;
            margin-left: 4px;
        }



        /* Pagination styling */
        .tabulator-footer {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
            padding: 8px;
        }

        .tabulator-paginator {
            font-weight: 500;
        }

        .tabulator-page {
            margin: 0 2px;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            background-color: white;
            color: #495057;
            transition: all 0.2s ease;
        }

        .tabulator-page:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .tabulator-page.active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }



        /* Additional hover effects */
        .hover-effect {
            transition: transform 0.2s;
        }

        .hover-effect:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }



        /* Modal enhancements */
        .modal-xl .modal-body {
            max-height: 80vh;
            overflow-y: auto;
        }

        .analysis-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        /* Enhanced Modal Styling */
        .modal-draggable .modal-dialog {
            cursor: move;
            margin: 0;
            pointer-events: all;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .modal-content {
            box-shadow: 0 5px 15px rgba(0, 0, 0, .5);
            border: none;
            border-radius: 8px;
        }

        .modal-header.bg-gradient {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 1rem;
            border: none;
        }

        .market-summary {
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .summary-stats .badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
            font-weight: 500;
        }

        .view-controls .btn-group .btn {
            padding: 0.375rem 0.75rem;
            transition: all 0.2s;
        }

        .view-controls .btn-group .btn.active {
            background-color: #2a5298;
            color: white;
            border-color: #2a5298;
        }

        .modal-actions .btn-light-secondary {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s;
        }

        .modal-actions .btn-light-secondary:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        #ovl30Modal .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        #ovl30Modal .table thead th {
            background-color: #f8f9fa;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
        }

        #ovl30Modal .table tbody tr {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
        }

        #ovl30Modal .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        #ovl30Modal .table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        /* Value indicators */
        .value-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .value-indicator .trend {
            font-size: 0.8em;
        }

        .value-indicator.positive {
            color: #198754;
        }

        .value-indicator.negative {
            color: #dc3545;
        }

        .value-indicator.neutral {
            color: #6c757d;
        }

        .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-body {
            padding: 1.5rem;
            background: #ffffff;
        }

        /* Enhanced Table Styling for Modal */
        .modal-body .table {
            margin-bottom: 0;
        }

        .modal-body .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .modal-body .table tbody td {
            vertical-align: middle;
            padding: 0.75rem;
            border-color: #e9ecef;
        }

        .modal-body .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-close {
            color: white;
            text-shadow: none;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }
    </style>
@endsection

@section('content')
    @include('layouts.shared.page-title', [
        'page_title' => 'Pricing Masters Analysis',
        'sub_title' => 'Pricing Masters Analysis',
    ])

    <!-- Image Preview -->
    <div id="image-hover-preview" style="display: none; position: fixed; z-index: 1000; pointer-events: none;">
        <img id="preview-image"
            style="max-width: 300px; max-height: 300px; border: 2px solid #ddd; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <!-- Play/Pause Controls -->
                        <div class="d-flex align-items-center me-3">
                            <div class="btn-group time-navigation-group" role="group" aria-label="Parent navigation">
                                <button id="play-backward" class="btn btn-light rounded-circle shadow-sm me-1"
                                    style="width: 36px; height: 36px; padding: 6px;">
                                    <i class="fas fa-step-backward"></i>
                                </button>

                                <button id="play-pause" class="btn btn-light rounded-circle shadow-sm me-1"
                                    style="width: 36px; height: 36px; padding: 6px; display: none;">
                                    <i class="fas fa-pause"></i>
                                </button>

                                <button id="play-auto" class="btn btn-primary rounded-circle shadow-sm me-1"
                                    style="width: 36px; height: 36px; padding: 6px;">
                                    <i class="fas fa-play"></i>
                                </button>

                                <button id="play-forward" class="btn btn-light rounded-circle shadow-sm"
                                    style="width: 36px; height: 36px; padding: 6px;">
                                    <i class="fas fa-step-forward"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <!-- Column Management -->
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle d-flex align-items-center gap-1"
                                    type="button" id="hide-column-dropdown" data-bs-toggle="dropdown">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                    Manage Columns
                                </button>
                                <ul class="dropdown-menu p-3 shadow-lg border rounded-3" id="column-dropdown-menu"
                                    style="max-height: 300px; overflow-y: auto; min-width: 250px;">
                                    <li class="fw-semibold text-muted mb-2">Toggle Columns</li>
                                </ul>
                            </div>


                        </div>
                    </div>

                    <div id="forecast-table"></div>

                </div>
            </div>
        </div>
    </div>

    <!-- OVL30 Modal -->
    <div class="modal fade modal-draggable" id="ovl30Modal" tabindex="-1" aria-labelledby="ovl30ModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient">
                    <h5 class="modal-title d-flex align-items-center text-dark">
                        <i class="bi bi-bar-chart-line-fill me-2"></i>
                        OVL30 Analysis
                        <span id="ovl30SkuLabel" class="badge  text-danger ms-2 animate__animated animate__fadeIn fw-bold fs-3"></span>
                    </h5>
                    <div class="modal-actions">
                        <button class="btn btn-sm btn-light-secondary me-2">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-12">
                            <div class="market-summary p-3 bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="summary-stats">
                                        <span class="badge bg-success me-2">Active Markets: <span id="activeMarketsCount">10</span></span>

                                        <span class="badge bg-info me-2">Dil %: <span id="dilPercentage"> </span></span>
                                        <span class="badge bg-danger me-2">Avg Price: <span id="formattedAvgPrice">0%</span></span>
                                         <span class="badge bg-info me-2">Profit % : <span id="formattedProfitPercentage">0%</span></span>
                                          <span class="badge bg-success me-2">ROI % : <span id="formattedRoiPercentage">0%</span></span>
                                          <span class="badge me-2" style="background-color: purple">INV : <span id="ovl30InvLabel">0%</span></span>
                                           <span class="badge bg-warning  text-dark">OV L30  : <span id="ovl30">0%</span></span>



                                    </div>
                                    <div class="view-controls">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary active" data-view="table">
                                                <i class="bi bi-table"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" data-view="chart">
                                                <i class="bi bi-bar-chart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="ovl30Content" class="p-3">
                                <!-- Marketplace data table will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>
    <script>
        document.body.style.zoom = "95%";

        // Helper function to calculate ROI
        function calculateROI(data) {
            const LP = parseFloat(data.LP) || 0;
            if (LP === 0) return 0;

            // Calculate total L30
            const totalL30 = (parseFloat(data.amz_l30) || 0) +
                (parseFloat(data.ebay_l30) || 0) +
                (parseFloat(data.shopifyb2c_l30) || 0) +
                (parseFloat(data.macy_l30) || 0) +
                (parseFloat(data.reverb_l30) || 0) +
                (parseFloat(data.doba_l30) || 0) +
                (parseFloat(data.temu_l30) || 0) +
                (parseFloat(data.wayfair_l30) || 0) +
                (parseFloat(data.ebay3_l30) || 0) +
                (parseFloat(data.ebay2_l30) || 0) +
                (parseFloat(data.walmart_l30) || 0);

            const SHIP = parseFloat(data.SHIP) || 0;

            // Calculate profits
            const amzProfit = data.amz_price ? ((parseFloat(data.amz_price) * 0.80) - LP - SHIP) * (parseFloat(data
                .amz_l30) || 0) : 0;
            const ebayProfit = data.ebay_price ? ((parseFloat(data.ebay_price) * 0.73) - LP - SHIP) * (parseFloat(data
                .ebay_l30) || 0) : 0;
            const shopifyProfit = data.shopifyb2c_price ? ((parseFloat(data.shopifyb2c_price) * 0.75) - LP - SHIP) * (
                parseFloat(data.shopifyb2c_l30) || 0) : 0;
            const macyProfit = data.macy_price ? ((parseFloat(data.macy_price) * 0.76) - LP - SHIP) * (parseFloat(data
                .macy_l30) || 0) : 0;
            const reverbProfit = data.reverb_price ? ((parseFloat(data.reverb_price) * 0.84) - LP - SHIP) * (parseFloat(data
                .reverb_l30) || 0) : 0;
            const dobaProfit = data.doba_price ? ((parseFloat(data.doba_price) * 0.95) - LP - SHIP) * (parseFloat(data
                .doba_l30) || 0) : 0;
            const temuProfit = data.temu_price ? ((parseFloat(data.temu_price) * 0.90) - LP - SHIP) * (parseFloat(data
                .temu_l30) || 0) : 0;
            const wayfairProfit = data.wayfair_price ? ((parseFloat(data.wayfair_price) * 0.90) - LP - SHIP) * (parseFloat(
                data.wayfair_l30) || 0) : 0;
            const ebay3Profit = data.ebay3_price ? ((parseFloat(data.ebay3_price) * 0.72) - LP - SHIP) * (parseFloat(data
                .ebay3_l30) || 0) : 0;
            const ebay2Profit = data.ebay2_price ? ((parseFloat(data.ebay2_price) * 0.81) - LP - SHIP) * (parseFloat(data
                .ebay2_l30) || 0) : 0;
            const walmartProfit = data.walmart_price ? ((parseFloat(data.walmart_price) * 0.80) - LP - SHIP) * (parseFloat(
                data.walmart_l30) || 0) : 0;



            const totalProfit = amzProfit + ebayProfit + shopifyProfit + macyProfit + reverbProfit +
                dobaProfit + temuProfit + wayfairProfit + ebay3Profit + ebay2Profit + walmartProfit;

            return totalL30 > 0 ? (totalProfit / totalL30) / LP * 100 : 0;
        }

        // Helper function to calculate Average Profit
        function calculateAvgProfit(data) {
            const LP = parseFloat(data.LP) || 0;
            const SHIP = parseFloat(data.SHIP) || 0;

            // Calculate profits and revenue for each marketplace
            const marketplaces = [{
                    price: data.amz_price,
                    l30: data.amz_l30,
                    percent: 0.80
                },
                {
                    price: data.ebay_price,
                    l30: data.ebay_l30,
                    percent: 0.73
                },
                {
                    price: data.shopifyb2c_price,
                    l30: data.shopifyb2c_l30,
                    percent: 0.75
                },
                {
                    price: data.macy_price,
                    l30: data.macy_l30,
                    percent: 0.76
                },
                {
                    price: data.reverb_price,
                    l30: data.reverb_l30,
                    percent: 0.84
                },
                {
                    price: data.doba_price,
                    l30: data.doba_l30,
                    percent: 0.95
                },
                {
                    price: data.temu_price,
                    l30: data.temu_l30,
                    percent: 0.90
                },
              
                {
                    price: data.ebay3_price,
                    l30: data.ebay3_l30,
                    percent: 0.72
                },
                {
                    price: data.ebay2_price,
                    l30: data.ebay2_l30,
                    percent: 0.81
                },
                {
                    price: data.walmart_price,
                    l30: data.walmart_l30,
                    percent: 0.80
                }
            ];

            let totalProfit = 0;
            let totalRevenue = 0;

            marketplaces.forEach(mp => {
                const price = parseFloat(mp.price) || 0;
                const l30 = parseFloat(mp.l30) || 0;
                if (price && l30) {
                    totalProfit += ((price * mp.percent) - LP - SHIP) * l30;
                    totalRevenue += price * l30;
                }
            });

            return totalRevenue > 0 ? (totalProfit / totalRevenue) * 100 : 0;
        }

        // Image preview functions
        function showImagePreview(img) {
            const preview = document.getElementById('image-hover-preview');
            const previewImg = document.getElementById('preview-image');

            previewImg.src = img.src;
            preview.style.display = 'block';

            document.addEventListener('mousemove', moveImagePreview);
        }

        function hideImagePreview() {
            const preview = document.getElementById('image-hover-preview');
            preview.style.display = 'none';
            document.removeEventListener('mousemove', moveImagePreview);
        }

        function moveImagePreview(e) {
            const preview = document.getElementById('image-hover-preview');
            const rect = preview.getBoundingClientRect();

            // Calculate position, keeping the preview within viewport
            let x = e.pageX + 20;
            let y = e.pageY + 20;

            // Adjust if preview would go off screen
            if (x + rect.width > window.innerWidth) {
                x = e.pageX - rect.width - 20;
            }
            if (y + rect.height > window.innerHeight) {
                y = e.pageY - rect.height - 20;
            }

            preview.style.left = x + 'px';
            preview.style.top = y + 'px';
        }


        //global variables for play btn
        let groupedSkuData = {};

       const table = new Tabulator("#forecast-table", {
            ajaxURL: "/pricing-analysis-data-views",
            ajaxConfig: "GET",
            layout: "fitDataFill",
            pagination: true,
            paginationSize: 10,
            initialSort: [{
                column: "Parent",
                dir: "asc"
            }],
            groupBy: "Parent",
            groupHeader: function(value, count, data, group) {
                return value + ' <span class="badge text-light" style="background-color: purple;">' + count + ' items</span>';
            },



            rowFormatter: function(row) {
                const data = row.getData();
                if (data.is_parent || (data.SKU && data.SKU.toUpperCase().includes('PARENT'))) {
                    row.getElement().classList.add("parent-row");
                    row.getCells().forEach(cell => cell.getElement().style.fontWeight = "700");
                }
            },
            columns: [{
                    title: "Image",
                    field: "shopifyb2c_image",
                    formatter: function(cell) {
                        const value = cell.getValue();
                        if (!value) return "";
                        return `<img src="${value}" width="40" height="40" class="product-thumb" onmouseover="showImagePreview(this)" onmouseout="hideImagePreview()" style="cursor: pointer">`;
                    },
                    headerSort: false,
                    width: 70,
                    hozAlign: "center"
                },
               
                {
                    title: "Parent",
                    field: "Parent",
                    headerFilter: "input",
                    headerFilterPlaceholder: "Search Parent...",
                    cssClass: "text-muted",
                    tooltip: true,
                    frozen: true
                },
                 {
                    title: "SKU",
                    field: "SKU",
                    headerFilter: "input",
                    headerFilterPlaceholder: "Search SKU...",
                    cssClass: "font-weight-bold",
                    tooltip: true,
                    frozen: true
                },
                {
                    title: "INV",
                    field: "INV",
                    hozAlign: "right",
                    formatter: function(cell) {
                        const value = cell.getValue();
                        return `<strong>${value}</strong>`;
                    }
                    
                },


                 {
                    title: "OVL30",
                    field: "ovl30",
                    hozAlign: "center",
                    headerSort: false,
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        const l30 = data.shopifyb2c_l30 || 0;

                        // Determine button color based on L30 value
                        let btnClass;
                        if (l30 === 0) {
                            btnClass = 'btn-primary'; // gray for zero
                        } else if (l30 < 10) {
                            btnClass = 'btn-warning'; // red for low
                        } else if (l30 < 30) {
                            btnClass = 'btn-warning'; // yellow for medium
                        } else {
                            btnClass = 'btn-success'; // green for good
                        }

                        return `<button class="btn btn-sm ${btnClass} rounded-pill px-3">
                            <i class="bi bi-bar-chart-line me-1"></i>${l30}
                        </button>`;
                    },
                    cellClick: function(e, cell) {
                        showOVL30Modal(cell.getRow());
                    }
                },

                    {
                        title: "DIL%",
                        field: "Dil%",
                        hozAlign: "right",
                        formatter: function (cell) {
                            const data = cell.getRow().getData();
                            const value = cell.getValue() || 0;
                            const element = document.createElement("div");
                        
                            const rounded = Math.round(value);
                            element.textContent = rounded + "%";
                            if (rounded >= 0 && rounded <= 10) {
                                element.style.color = "red"; // red text
                            } else if (rounded >= 11 && rounded <= 15) {
                                element.style.backgroundColor = "yellow"; // yellow background
                                element.style.color = "black";
                                element.style.padding = "2px 4px";
                                element.style.borderRadius = "4px";
                            } else if (rounded >= 16 && rounded <= 20) {
                                element.style.color = "blue"; // blue text
                            } else if (rounded >= 21 && rounded <= 40) {
                                element.style.color = "green"; // green text
                            } else if (rounded >= 41) {
                                element.style.color = "purple"; // purple text (41 and above)
                            }

                            data.dilPercentage = rounded;
                           
                            return element;
                        },
                    }

                    ,
               
              
                
                {
                    title: "AVG PRICE",
                    field: "avgPrice",
                    hozAlign: "right",
                    formatter: function(cell) {
                        const data = cell.getRow().getData();

                        // Calculate weighted average price
                        const calculateAvgPrice = () => {
                            const marketplaces = [{
                                    price: data.amz_price,
                                    l30: data.amz_l30
                                },
                                {
                                    price: data.ebay_price,
                                    l30: data.ebay_l30
                                },
                                {
                                    price: data.macy_price,
                                    l30: data.macy_l30
                                },
                                {
                                    price: data.reverb_price,
                                    l30: data.reverb_l30
                                },
                                {
                                    price: data.doba_price,
                                    l30: data.doba_l30
                                },
                                {
                                    price: data.temu_price,
                                    l30: data.temu_l30
                                },
                                {
                                    price: data.wayfair_price,
                                    l30: data.wayfair_l30
                                },
                                {
                                    price: data.ebay3_price,
                                    l30: data.ebay3_l30
                                },
                                {
                                    price: data.ebay2_price,
                                    l30: data.ebay2_l30
                                },
                                {
                                    price: data.walmart_price,
                                    l30: data.walmart_l30
                                },
                                {
                                    price: data.shopify_price,
                                    l30: data.shopify_l30
                                }
                            ];

                            let totalWeightedPrice = 0;
                            let totalL30 = 0;

                            marketplaces.forEach(mp => {
                                const price = parseFloat(mp.price) || 0;
                                const l30 = parseFloat(mp.l30) || 0;
                                totalWeightedPrice += (price * l30);
                                totalL30 += l30;
                            });

                            return totalL30 > 0 ? (totalWeightedPrice / totalL30).toFixed(2) : '---';
                        };

                        const avgPrice = calculateAvgPrice();
                        const avgPriceValue = parseFloat(avgPrice);

                        // Determine colors based on value
                        let textColor, bgColor;
                        if (!isNaN(avgPriceValue)) {
                            if (avgPriceValue < 10) {
                                textColor = '#dc3545'; // red
                               
                            } else if (avgPriceValue >= 10 && avgPriceValue < 15) {
                                textColor = '#fd7e14'; // orange
                             
                            } else if (avgPriceValue >= 15 && avgPriceValue < 20) {
                                textColor = '#0d6efd'; // blue
                              
                            } else if (avgPriceValue >= 20) {
                                textColor = '#198754'; // green
                               
                            }
                        } else {
                            textColor = '#6c757d'; // gray
                           
                        }

                        const element = document.createElement('div');
                        element.innerHTML = avgPrice === '---' ? avgPrice : `$${avgPrice}`;
                        element.style.color = textColor;
                        element.style.fontWeight = '700'; // Bolder text
                        element.style.backgroundColor = bgColor;
                        element.style.padding = '4px 8px';
                        element.style.borderRadius = '4px';
                        element.style.textAlign = 'center';

                        data.formattedAvgPrice = avgPrice;
                        return element;
                    }
                },

                /* === OVL30 button (VISIBLE) === */
               
                /* === MARKETPLACE COLUMNS (HIDDEN, but kept for modal data) === */
               
               

                {
                    title: "AVG PFT%",
                    field: "avgPftPercent",
                    hozAlign: "right",
                    headerSort: true,
                    sortable: true,
                    sorterParams: {
                        alignEmptyValues: "bottom"
                    },
                    sorter: function(a, b) {
                        let rowA = this.getRow(a);
                        let rowB = this.getRow(b);
                        return calculateAvgProfit(rowA.getData()) - calculateAvgProfit(rowB.getData());
                    },
                    headerSort: true,
                    sorter: function(a, b) {
                        const valA = a || 0;
                        const valB = b || 0;
                        return valA - valB;
                    },
                    formatter: function(cell) {
                        const data = cell.getRow().getData();

                        // Calculate profits per site using L30
                        const LP = parseFloat(data.LP) || 0;
                        const SHIP = parseFloat(data.SHIP) || 0;

                        // Get price and L30 values for each marketplace
                        const amzPrice = parseFloat(data.amz_price) || 0;
                        const ebayPrice = parseFloat(data.ebay_price) || 0;
                        const shopifyPrice = parseFloat(data.shopifyb2c_price) || 0;
                        const macyPrice = parseFloat(data.macy_price) || 0;
                        const reverbPrice = parseFloat(data.reverb_price) || 0;
                        const dobaPrice = parseFloat(data.doba_price) || 0;
                        const temuPrice = parseFloat(data.temu_price) || 0;
                        const wayfairPrice = parseFloat(data.wayfair_price) || 0;
                        const ebay3Price = parseFloat(data.ebay3_price) || 0;
                        const ebay2Price = parseFloat(data.ebay2_price) || 0;
                        const walmartPrice = parseFloat(data.walmart_price) || 0;

                        const amzL30 = parseFloat(data.amz_l30) || 0;
                        const ebayL30 = parseFloat(data.ebay_l30) || 0;
                        const shopifyL30 = parseFloat(data.shopifyb2c_l30) || 0;
                        const macyL30 = parseFloat(data.macy_l30) || 0;
                        const reverbL30 = parseFloat(data.reverb_l30) || 0;
                        const dobaL30 = parseFloat(data.doba_l30) || 0;
                        const temuL30 = parseFloat(data.temu_l30) || 0;
                        const wayfairL30 = parseFloat(data.wayfair_l30) || 0;
                        const ebay3L30 = parseFloat(data.ebay3_l30) || 0;
                        const ebay2L30 = parseFloat(data.ebay2_l30) || 0;
                        const walmartL30 = parseFloat(data.walmart_l30) || 0;

                        // Calculate profit for each marketplace
                        const amzProfit = ((amzPrice * 0.80) - LP - SHIP) ;
                        const ebayProfit = ((ebayPrice * 0.73) - LP - SHIP) ;
                        const shopifyProfit = ((shopifyPrice * 0.75) - LP - SHIP) ;
                        const macyProfit = ((macyPrice * 0.76) - LP - SHIP) ;
                        const reverbProfit = ((reverbPrice * 0.84) - LP - SHIP) ;
                        const dobaProfit = ((dobaPrice * 0.95) - LP - SHIP) ;
                        const temuProfit = ((temuPrice * 0.90) - LP - SHIP);
                        const ebay3Profit = ((ebay3Price * 0.72) - LP - SHIP);
                        const ebay2Profit = ((ebay2Price * 0.81) - LP - SHIP) ;
                        const walmartProfit = ((walmartPrice * 0.80) - LP - SHIP) ;




                        // Calculate total profit
                        const totalProfit = amzProfit + ebayProfit + shopifyProfit + macyProfit +
                            reverbProfit + dobaProfit + temuProfit  +
                            ebay3Profit + ebay2Profit + walmartProfit;

                        // Calculate total revenue
                        const totalRevenue =
                            (amzPrice * amzL30) +
                            (ebayPrice * ebayL30) +
                            (shopifyPrice * shopifyL30) +
                            (macyPrice * macyL30) +
                            (reverbPrice * reverbL30) +
                            (dobaPrice * dobaL30) +
                            (temuPrice * temuL30) +
                            (ebay3Price * ebay3L30) +
                            (ebay2Price * ebay2L30) +
                            (walmartPrice * walmartL30);

                        // Calculate average profit percentage and round to nearest integer
                        let avgPftPercent = totalRevenue > 0 ? (totalProfit / totalRevenue) * 100 : 0;

                        if (!isFinite(avgPftPercent) || isNaN(avgPftPercent)) {
                            avgPftPercent = 0;
                        }

                        avgPftPercent = Math.round(avgPftPercent);



                        // Style based on profit percentage
                        let bgColor, textColor;
                        if (avgPftPercent < 11) {
                          
                            textColor = '#ff0000';
                        } else if (avgPftPercent >= 10 && avgPftPercent < 15) {
                            bgColor = 'yellow'; // orange
                            textColor = '#000000';
                        } else if (avgPftPercent >= 15 && avgPftPercent < 20) {
                           
                            textColor = '#0d6efd';
                        } else if (avgPftPercent >= 21 && avgPftPercent < 50) {
                            textColor = '#198754';
                        }
                        else{
                            textColor = '#800080'; // purple
                        }

                        const element = document.createElement('div');
                        element.textContent = avgPftPercent + '%';
                        element.style.backgroundColor = bgColor;
                        element.style.color = textColor;
                        element.style.padding = '4px 8px';
                        element.style.borderRadius = '4px';
                        element.style.fontWeight = '600';
                        element.style.textAlign = 'center';

                        data.avgPftPercent = avgPftPercent;
                        return element;
                    }
                },

                 {
                    title: "AVG ROI %",
                    field: "avgRoi",
                    hozAlign: "right",
                    headerSort: true,
                    sorter: function(a, b) {
                        const valA = parseFloat(a) || 0;
                        const valB = parseFloat(b) || 0;
                        return valA - valB;
                    },
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        const LP = parseFloat(data.LP) || 0;
                        if (LP === 0) return "N/A";

                        // Calculate total L30 across all marketplaces
                        const totalL30 = (parseFloat(data.amz_l30) || 0) +
                            (parseFloat(data.ebay_l30) || 0) +
                            (parseFloat(data.shopifyb2c_l30) || 0) +
                            (parseFloat(data.macy_l30) || 0) +
                            (parseFloat(data.reverb_l30) || 0) +
                            (parseFloat(data.doba_l30) || 0) +
                            (parseFloat(data.temu_l30) || 0) +
                            (parseFloat(data.wayfair_l30) || 0) +
                            (parseFloat(data.ebay3_l30) || 0) +
                            (parseFloat(data.ebay2_l30) || 0) +
                            (parseFloat(data.walmart_l30) || 0);

                        // Calculate profit for each marketplace
                       const SHIP = parseFloat(data.SHIP) || 0;

                    const amzProfit     = data.amz_price ? ((parseFloat(data.amz_price) * 0.80) - LP - SHIP) : 0;
                    const ebayProfit    = data.ebay_price ? ((parseFloat(data.ebay_price) * 0.73) - LP - SHIP) : 0;
                    const shopifyProfit = data.shopifyb2c_price ? ((parseFloat(data.shopifyb2c_price) * 0.75) - LP - SHIP) : 0;
                    const macyProfit    = data.macy_price ? ((parseFloat(data.macy_price) * 0.76) - LP - SHIP) : 0;
                    const reverbProfit  = data.reverb_price ? ((parseFloat(data.reverb_price) * 0.84) - LP - SHIP) : 0;
                    const dobaProfit    = data.doba_price ? ((parseFloat(data.doba_price) * 0.95) - LP - SHIP) : 0;
                    const temuProfit    = data.temu_price ? ((parseFloat(data.temu_price) * 0.90) - LP - SHIP) : 0;
                    const wayfairProfit = data.wayfair_price ? ((parseFloat(data.wayfair_price) * 0.90) - LP - SHIP) : 0;
                    const ebay3Profit   = data.ebay3_price ? ((parseFloat(data.ebay3_price) * 0.72) - LP - SHIP) : 0;
                    const ebay2Profit   = data.ebay2_price ? ((parseFloat(data.ebay2_price) * 0.81) - LP - SHIP) : 0;
                    const walmartProfit = data.walmart_price ? ((parseFloat(data.walmart_price) * 0.80) - LP - SHIP) : 0;


                        const totalProfit = amzProfit + ebayProfit + shopifyProfit + macyProfit +
                            reverbProfit +
                            dobaProfit + temuProfit + wayfairProfit + ebay3Profit + ebay2Profit +
                            walmartProfit;

                        // Calculate ROI: (Total Profit / Total L30) / LP
                        const roi = totalL30 > 0 ? (totalProfit / totalL30) / LP * 100 : 0;

                        // Style based on ROI percentage
                         let bgColor, textColor;
                        if (roi < 11) {
                          
                            textColor = '#ff0000';
                        } else if (roi >= 10 && roi < 15) {
                            bgColor = 'yellow'; // orange
                            textColor = '#000000';
                        } else if (roi >= 15 && roi < 20) {
                           
                            textColor = '#0d6efd';
                        } else if (roi >= 21 && roi < 50) {
                            textColor = '#198754';
                        }
                        else{
                            textColor = '#800080'; // purple
                        }

                        const element = document.createElement('div');
                        element.textContent = Math.round(roi) + '%';
                        element.style.backgroundColor = bgColor;
                        element.style.color = textColor;
                        element.style.padding = '4px 8px';
                        element.style.borderRadius = '4px';
                        element.style.fontWeight = '600';
                        element.style.textAlign = 'center';

                         data.avgRoi =  Math.round(roi);
                        return element;
                    },
                    visible: true
                },

                 {
                    title: "MSRP",
                    field: "MSRP",
                    hozAlign: "right",
                    formatter: "money",
                    formatterParams: {
                        precision: 2
                    }
                },

                  {
                    title: "MAP",
                    field: "MAP",
                    hozAlign: "right",
                    formatter: "money",
                    formatterParams: {
                        precision: 2
                    }
                },

                {
                    title: "LP",
                    field: "LP",
                    hozAlign: "right",
                    formatter: "money",
                    formatterParams: {
                        precision: 2
                    }
                },
                {
                    title: "SHIP",
                    field: "SHIP",
                    hozAlign: "right",
                    formatter: "money",
                    formatterParams: {
                        precision: 2
                    }
                },
             
            ],

            ajaxResponse: function(url, params, response) {
                groupedSkuData = {}; // clear previous

                // Add calculated fields for sorting
                response.data = response.data.map(item => {
                    return {
                        ...item,
                        calculatedRoi: calculateROI(item),
                        calculatedProfit: calculateAvgProfit(item)
                    };
                });

                const processed = response.data.map((item, index) => {
                    const sku = item.SKU || "";
                    const parentKey = item.Parent || "";
                    const isParent = item.is_parent || sku.toUpperCase().includes("PARENT");

                    const processedItem = {
                        ...item,
                        sl_no: index + 1,
                        is_parent: isParent,
                        isParent: isParent,
                        raw_data: item || {}
                    };

                    // Group for play button use
                    if (!groupedSkuData[parentKey]) {
                        groupedSkuData[parentKey] = [];
                    }
                    groupedSkuData[parentKey].push(processedItem);

                    return processedItem;
                });

                setTimeout(() => {
                    setCombinedFilters();
                }, 0);

                // return processed;
                console.log("Response:", response);
                return response.data;
            },
            ajaxError: function(xhr, textStatus, errorThrown) {
                console.error("Error loading data:", textStatus);
            },
        });

        let currentParentFilter = null;

        function setCombinedFilters() {
            table.setFilter(function(row) {
                return true; // Show all rows by default
            });
        }

        // Function to add trend indicators
        function addTrendIndicators(row) {
            const data = row.getData();
            const cells = row.getCells();

            cells.forEach(cell => {
                const field = cell.getColumn().getField();
                if (field.includes('l30') || field.includes('l60')) {
                    const value = cell.getValue();
                    const prevValue = data[field.replace('l30', 'l60')] || 0;

                    if (value > prevValue) {
                        cell.getElement().classList.add('trend-up');
                    } else if (value < prevValue) {
                        cell.getElement().classList.add('trend-down');
                    }
                }
            });
        }

        // Function to format numbers with commas
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        document.addEventListener("DOMContentLoaded", function() {
            buildColumnDropdown();


            // Play button functionality
            document.getElementById('play-auto').addEventListener('click', () => {
                const parentKeys = Object.keys(groupedSkuData);
                let currentIndex = 0;
                currentParentFilter = parentKeys[currentIndex];
                setCombinedFilters();
                document.getElementById('play-pause').style.display = 'inline-block';
                document.getElementById('play-auto').style.display = 'none';
            });

            document.getElementById('play-forward').addEventListener('click', () => {
                const parentKeys = Object.keys(groupedSkuData);
                let currentIndex = parentKeys.indexOf(currentParentFilter);
                currentIndex = (currentIndex + 1) % parentKeys.length;
                currentParentFilter = parentKeys[currentIndex];
                setCombinedFilters();
            });

            document.getElementById('play-backward').addEventListener('click', () => {
                const parentKeys = Object.keys(groupedSkuData);
                let currentIndex = parentKeys.indexOf(currentParentFilter);
                currentIndex = (currentIndex - 1 + parentKeys.length) % parentKeys.length;
                currentParentFilter = parentKeys[currentIndex];
                setCombinedFilters();
            });

            document.getElementById('play-pause').addEventListener('click', () => {
                currentParentFilter = null;
                setCombinedFilters();
                document.getElementById('play-pause').style.display = 'none';
                document.getElementById('play-auto').style.display = 'inline-block';
            });
        });
    </script>

    <script>
        // Helper: percent formatting
        function fmtPct(v) {
            if (v === null || v === undefined || v === "") return "-";
            const num = parseFloat(v);
            if (isNaN(num)) return "-";

            
            return Math.round(num * 100) + "%";
        }


        // Helper: money formatting
        function fmtMoney(v) {
            if (v === null || v === undefined || v === "") return "-";
            const num = parseFloat(v);
            if (isNaN(num)) return "-";
            return "$" + num.toFixed(2);
        }

        // Marketplace table generator
        function buildOVL30Table(data) {
            const rows = [{
                    label: "Amazon",
                    prefix: "amz"
                },
                {
                    label: "eBay",
                    prefix: "ebay"
                },
                {
                    label: "Doba",
                    prefix: "doba"
                },
                {
                    label: "Macy",
                    prefix: "macy"
                },
                {
                    label: "Reverb",
                    prefix: "reverb"
                },
                {
                    label: "Temu",
                    prefix: "temu"
                },
                {
                    label: "Walmart",
                    prefix: "walmart"
                },
                {
                    label: "eBay2",
                    prefix: "ebay2"
                },
                {
                    label: "eBay3",
                    prefix: "ebay3"
                },
                {
                    label: "Shopify B2C",
                    prefix: "shopify"
                }
            ];

            let html = `
            <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                <tr>
                    <th>Marketplace</th>
                     <th>L60</th>
                    <th>L30</th>
                    <th>Price</th>
                    <th>Profit %</th>
                    <th>ROI %</th>
                    <th>Views L30</th>
                    <th>CVR</th>
                    <th>LMP</th>
                    <th>S Price </th>
                    <th>S PFT %</th>
                    <th>S ROI %</th>
                  
                </tr>
                </thead>
                <tbody>
            `;

            rows.forEach(r => {
                const price = data[`${r.prefix}_price`];
                const l30 = data[`${r.prefix}_l30`];
                const l60 = data[`${r.prefix}_l60`];
                const pft = data[`${r.prefix}_pft`];
                const roi = data[`${r.prefix}_roi`];
                const cvr = data[`${r.prefix}_cvr`];

                const hasAny = price != null || l30 != null || l60 != null || pft != null || roi != null;
                if (!hasAny) return;

               const getColor = (value) => {
                    // Convert to number and handle percentage values
                    const val = typeof value === 'string' ? parseFloat(value.replace('%', '')) : Number(value);
                    console.log('Color value:', value, 'Parsed:', val); // Debug log
                    
                    // Make sure value is a finite number
                    if (!isFinite(val) || isNaN(val)) return '#000000'; // default black
                    
                    // Handle percentage ranges
                    if (val >= 0 && val <= 10) return '#ff0000';        // red
                    if (val > 10 && val <= 14) return '#fd7e14';       // orange
                    if (val > 14 && val <= 19) return '#0d6efd';       // blue
                    if (val > 19 && val <= 40) return '#198754';       // green
                    if (val > 40) return '#800080';                    // purple
                    
                    return '#000000';                                  // default black
                };

                const pftClass = pft > 20 ? 'positive' : pft < 10 ? 'negative' : 'neutral';
                const roiClass = roi > 30 ? 'positive' : roi < 15 ? 'negative' : 'neutral';
                

                html += `
                    <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shop me-2"></i>
                            <span class="fw-semibold">${r.label}</span>
                        </div>
                    </td>
                    <td>
                        <div class="value-indicator">
                            ${l60 ?? "-"}
                        </div>
                    </td>
                    <td>
                        <div class="value-indicator">
                            ${l30 ?? "-"}
                        </div>
                    </td>
                
                    <td>
                        <div class="value-indicator">
                            ${fmtMoney(price)}
                        </div>
                    </td>
                    <td>
                        <div class="value-indicator ${pftClass}" style="color: ${getColor(pft * 100)};">
                            ${fmtPct(pft)}
                        </div>
                    </td>
                    <td>
                        <div class="value-indicator ${roiClass}" style="color: ${getColor(roi * 100)};">
                            ${fmtPct(roi)}
                        </div>
                    </td>
                
                
                    <td>
                        <div class="value-indicator">
                            ${r.prefix === 'amz' ? (data.sessions_l30 ?? "-") : r.prefix === 'ebay' ? (data.ebay_views ?? "-") : r.prefix === 'ebay3' ? (data.ebay3_views ?? "-") : "-"}
                        </div>
                    </td>
                    <td>
                        <div class="value-indicator">
                            ${(() => {
                                if (r.prefix === 'amz' && cvr) {
                                    return `<span style="color: ${cvr.color}">${Math.round(cvr.value)}%</span>`;
                                } else if (r.prefix === 'ebay' && cvr) {
                                    return `<span style="color: ${cvr.color}">${Math.round(cvr.value)}%</span>`;
                                } else if (r.prefix === 'ebay3' && cvr) {
                                    return `<span style="color: ${cvr.color}">${Math.round(cvr.value)}%</span>`;
                                }

                                return "No Views";
                            })()} 
                        </div>
                    </td>
                    <td>
                        <div class="value-indicator">
                            ${r.prefix === 'amz' || r.prefix === 'ebay' ? fmtMoney(data.price_lmpa) : '-'}
                        </div>
                    </td>
                

                <td>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" 
                                class="form-control form-control-sm s-price" 
                                value="${
                                    r.prefix === 'amz' ? (data.amz_sprice || '') 
                                    : r.prefix === 'ebay' ? (data.ebay_sprice || '') 
                                    : r.prefix === 'shopifyb2c' ? (data.shopifyb2c_sprice || '') 
                                    : ''
                                }"
                                style="width: 65px;" 
                                step="any"
                                data-sku="${data.SKU}" 
                                data-lp="${data.LP}" 
                                data-ship="${data.SHIP}" 
                                data-type="${r.prefix}">

                            <!-- Push to Marketplace -->
                            <button class="btn btn-success btn-sm d-flex align-items-center pushPriceBtn" 
                                type="button"
                                data-sku="${data.SKU}" 
                                data-type="${r.prefix}">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </button>
                        </div>
                    </td>

                    <td class="spft-field">
                        ${r.prefix === 'amz' && data.amz_spft ? `${Math.round(data.amz_spft)}%` : 
                        r.prefix === 'ebay' && data.ebay_spft ? `${Math.round(data.ebay_spft)}%` : 
                        r.prefix === 'shopifyb2c' && data.shopifyb2c_spft ? `${Math.round(data.shopifyb2c_spft)}%` : '-'}
                    </td>

                    <td class="sroi-field">
                        ${r.prefix === 'amz' && data.amz_sroi ? `${Math.round(data.amz_sroi)}%` : 
                        r.prefix === 'ebay' && data.ebay_sroi ? `${Math.round(data.ebay_sroi)}%` : 
                        r.prefix === 'shopifyb2c' && data.shopifyb2c_spft ? `${Math.round(data.shopifyb2c_sroi)}%` : '-'}
                    </td>

                </tr>
                `;
            });

            html += "</tbody></table></div>";
            return html;
        }

        // Modal open function
        function showOVL30Modal(row) {
            const data = row.getData();
            document.getElementById('ovl30SkuLabel').textContent = data.SKU ? `${data.SKU}` : "";     
            document.getElementById('ovl30InvLabel').textContent = data.INV ? `${data.INV}` : ""; 
            document.getElementById('ovl30').textContent = data.L30 ? `${data.L30}` : "";        

            document.getElementById('dilPercentage').textContent = data.dilPercentage ? `${data.dilPercentage}` : "";
            document.getElementById('formattedAvgPrice').textContent = data.formattedAvgPrice ? `${data.formattedAvgPrice}` : "";
            document.getElementById('formattedProfitPercentage').textContent = data.avgPftPercent ? `${data.avgPftPercent}` : "";
            document.getElementById('formattedRoiPercentage').textContent = data.avgRoi ? `${data.avgRoi}` : "";



            document.getElementById('ovl30Content').innerHTML = buildOVL30Table(data);

            const modalEl = document.getElementById('ovl30Modal');
            const modal = new bootstrap.Modal(modalEl);

            // Make modal draggable
            const dialogEl = modalEl.querySelector('.modal-dialog');
            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = 0;
            let yOffset = 0;

            dialogEl.addEventListener('mousedown', dragStart);
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', dragEnd);

            function dragStart(e) {
                if (e.target.closest('.modal-header')) {
                    isDragging = true;
                    initialX = e.clientX - xOffset;
                    initialY = e.clientY - yOffset;
                }
            }

            function drag(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                    xOffset = currentX;
                    yOffset = currentY;
                    dialogEl.style.transform = `translate(${currentX}px, ${currentY}px)`;
                }
            }

            function dragEnd() {
                isDragging = false;
            }

            // Reset position when modal is hidden
            modalEl.addEventListener('hidden.bs.modal', function() {
                dialogEl.style.transform = 'none';
                xOffset = 0;
                yOffset = 0;
            });

            modal.show();
        }


        // Push Price
        $(document).on('blur', '.s-price', function() {
            const $input = $(this);
            const sprice = parseFloat($input.val()) || 0;
            const sku = $input.data('sku');
            const type = $input.data('type');
            const LP = parseFloat($input.data('lp')) || 0;
            const SHIP = parseFloat($input.data('ship')) || 0;

            if (!sku || !type) return;

            $.ajax({
                url: '/pricing-master/save-sprice',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    sku: sku,
                    type: type,
                    sprice: sprice,
                    LP: LP,
                    SHIP: SHIP
                },
                 beforeSend: function() {
                        $('#savePricingBtn').html(
                            '<i class="fa fa-spinner fa-spin"></i> Saving...');
                    },
                success: function(res) {
                    if(res.status === 200) {
                        // Update the modal fields dynamically with rounded values
                        const $row = $input.closest('tr');
                        $row.find('.spft-field').text(Math.round(res.data.SPFT) + '%');
                        $row.find('.sroi-field').text(Math.round(res.data.SROI) + '%');
                    } else {
                        console.error('Error saving S Price:', res.message);
                    }
                },
                error: function(err) {
                    console.error('Error saving S Price:', err);
                }
            });
        });


            $(document).on('click', '.pushPriceBtn', function() {
            const $btn = $(this);
            const sku = $btn.data('sku');
            const type = $btn.data('type');

            if(!sku || !type) return;

            // Fetch the latest SPrice from your input field in the modal/table
            const $input = $btn.closest('tr').find('.s-price');
            const sprice = parseFloat($input.val()) || 0;

            if(sprice <= 0) {
                alert('Please enter a valid SPrice before pushing.');
                return;
            }

            $btn.html('<i class="fa fa-spinner fa-spin"></i> Pushing...');

            // Example: Marketplace AJAX
            if(type === 'amz') {
                $.ajax({
                    url: '/update-amazon-price',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        sku: sku,
                        price: sprice
                    },
                    success: function(res) {
                       alert('Amazon price updated successfully!');
                    },
                    error: function(err) {
                        alert('Error updating Amazon price: ' + err);
                    },
                    complete: function() {
                        $btn.html('Push to Marketplace'); // reset button text
                    }
                });
            } else if(type === 'ebay') {
                $.ajax({
                    url: '/update-ebay-price',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        sku: sku,
                        price: sprice
                    },
                    success: function(res) {
                     alert('eBay price updated successfully!');
                    },
                    error: function(err) {
                        alert('Error updating eBay price: ' + err);
                    },
                    complete: function() {
                        $btn.html('Push to Marketplace');
                    }
                });
            }

            else if(type === 'shopifyb2c') {
                $.ajax({
                    url: '/push-shopify-price',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        sku: sku,
                        price: sprice
                    },
                    success: function(res) {
                        if (res.status === "success") {
                            alert(res.message || 'Shopify price updated successfully!');
                        } else {
                            alert("Error: " + (res.message || "Something went wrong"));
                        }
                        console.log(res);
                    },
                    error: function(err) {
                        let errorMsg = "Error updating Shopify price!";
                        if (err.responseJSON && err.responseJSON.message) {
                            errorMsg = err.responseJSON.message;
                        }
                        alert(errorMsg);
                        console.error('Error updating Shopify price:', err);
                    },
                    complete: function() {
                        $btn.html('Push to Marketplace');
                    }
                });
            }

            // You can add more marketplaces here
        });

       
    </script>
@endsection
