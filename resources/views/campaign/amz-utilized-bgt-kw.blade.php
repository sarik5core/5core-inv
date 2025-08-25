@extends('layouts.vertical', ['title' => 'Amazon - UTILIZED BGT KW', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css" rel="stylesheet">
    <style>
        .tabulator .tabulator-header {
            background: linear-gradient(90deg, #D8F3F3 0%, #D8F3F3 100%);
            border-bottom: 1px solid #403f3f;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.10);
        }

        .tabulator .tabulator-header .tabulator-col {
            text-align: center;
            background: #D8F3F3;
            border-right: 1px solid #262626;
            padding: 16px 10px;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.08rem;
            letter-spacing: 0.02em;
            transition: background 0.2s;
        }

        .tabulator .tabulator-header .tabulator-col:hover {
            background: #D8F3F3;
            color: #2563eb;
        }

        .tabulator-row {
            background-color: #fff !important;
            transition: background 0.18s;
        }

        .tabulator-row:nth-child(even) {
            background-color: #f8fafc !important;
        }

        .tabulator .tabulator-cell {
            text-align: center;
            padding: 14px 10px;
            border-right: 1px solid #262626;
            border-bottom: 1px solid #262626;
            font-size: 1rem;
            color: #22223b;
            vertical-align: middle;
            transition: background 0.18s, color 0.18s;
        }

        .tabulator .tabulator-cell:focus {
            outline: 1px solid #262626;
            background: #e0eaff;
        }

        .tabulator-row:hover {
            background-color: #dbeafe !important;
        }

        .parent-row {
            background-color: #e0eaff !important;
            font-weight: 700;
        }

        #account-health-master .tabulator {
            border-radius: 18px;
            box-shadow: 0 6px 24px rgba(37, 99, 235, 0.13);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .tabulator .tabulator-row .tabulator-cell:last-child,
        .tabulator .tabulator-header .tabulator-col:last-child {
            border-right: none;
        }

        .tabulator .tabulator-footer {
            background: #f4f7fa;
            border-top: 1px solid #262626;
            font-size: 1rem;
            color: #4b5563;
            padding: 5px;
            height: 100px;
        }

        .tabulator .tabulator-footer:hover {
            background: #e0eaff;
        }

        @media (max-width: 768px) {

            .tabulator .tabulator-header .tabulator-col,
            .tabulator .tabulator-cell {
                padding: 8px 2px;
                font-size: 0.95rem;
            }
        }

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

        .green-bg {
            background-color: #05bd30 !important;
            color: #ffffff !important;
        }

        .pink-bg {
            background-color: #ff01d0 !important;
            color: #ffffff !important;
        }

        .red-bg {
            background-color: #ff2727 !important;
            color: #ffffff !important;
        }
    </style>
@endsection
@section('content')
    @include('layouts.shared.page-title', [
        'page_title' => 'Amazon - Budget',
        'sub_title' => 'Amazon - Budget',
    ])
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Header Row with improved spacing and alignment -->
                    <div class="row align-items-center mb-4">
                        <!-- Title Column -->
                        <div class="col-md-4">
                            <h4 class="mb-0 fw-bold text-primary d-flex align-items-center">
                                <i class="fa-solid fa-chart-line me-2"></i>
                                UTILIZED BGT KW
                            </h4>
                        </div>

                        <!-- Stats Column -->
                        <div class="col-md-3">
                            <div class="d-flex gap-3">
                                <div class="px-3 py-2 border rounded-3 bg-white shadow-sm text-center flex-grow-1">
                                    <small class="text-dark fs-5 d-block">Need to increase bids in: </small>
                                    <span id="total-campaigns" class="fw-bold text-success fs-5">0</span>
                                </div>
                                <div class="px-3 py-2 border rounded-3 bg-white shadow-sm text-center flex-grow-1">
                                    <small class="text-muted d-block">% of Total</small>
                                    <span id="percentage-campaigns" class="fw-bold text-primary fs-5">0%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Filters Column -->
                        <div class="col-md-5">
                            <div class="d-flex gap-2 justify-content-end">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>
                                    <input type="text" id="global-search" class="form-control border-start-0"
                                        placeholder="Search campaigns...">
                                </div>
                                <select id="status-filter" class="form-select" style="width: 140px;">
                                    <option value="">All Status</option>
                                    <option value="ENABLED">ENABLED</option>
                                    <option value="PAUSED">PAUSED</option>
                                    <option value="ARCHIVED">ARCHIVED</option>
                                </select>
                                <button type="button" class="btn btn-light border"
                                    onclick="$('#global-search').val('');$('#status-filter').val('');table.clearFilter();">
                                    <i class="fa fa-refresh"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Section with improved styling -->
                    <div id="budget-under-table"></div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var table = new Tabulator("#budget-under-table", {
                ajaxURL: "/campaigns/get-amz-utilized-bgt-kw",
                layout: "fitData",
                pagination: "local",
                paginationSize: 25,
                movableColumns: true,
                resizableColumns: true,
                columns: [
                    // {
                    //     title: "AD TYPE",
                    //     field: "ad_type"
                    // },
                    {title:"Parent", field:"parent"},
                    {title:"SKU", field:"sku"},
                    {title:"INV", field:"INV"},
                    {title:"OV L30", field:"L30"},
                    {
                        title: "DIL %",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var l30 = parseFloat(row.L30) || 0;
                            var inv = parseFloat(row.INV) || 0;
                            var dilPercent = inv > 0 ? (l30/inv) * 100 : 0;
                            return dilPercent.toFixed(0) + "%";
                        }
                    },
                    {title:"AL 30", field:"A_L30"},
                    {
                        title: "A DIL %",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var a_l30 = parseFloat(row.A_L30) || 0;
                            var inv = parseFloat(row.INV) || 0;
                            var dilPercent = inv > 0 ? (a_l30/inv) * 100 : 0;
                            return dilPercent.toFixed(0) + "%";
                        }
                    },
                    {title:"NRL", field:"NR"},
                    {title:"NRA", field:"NRA"},
                    {title:"FBA", field:"fba"},
                    {
                        title: "CAMPAIGN",
                        field: "campaignName"
                    },
                    {
                        title: "BGT",
                        field: "campaignBudgetAmount",
                        hozAlign: "right",
                        formatter: (cell) => parseFloat(cell.getValue() || 0)
                    }, 
                    {
                        title: "7 UB%",
                        field: "l7_spend",
                        hozAlign: "right",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var l7_spend = parseFloat(row.l7_spend) || 0;
                            var budget = parseFloat(row.campaignBudgetAmount) || 0;
                            var ub7 = budget > 0 ? (l7_spend / (budget * 7)) * 100 : 0;

                            // Set cell background color based on UB%
                            var td = cell.getElement();
                            td.classList.remove('green-bg', 'pink-bg', 'red-bg');
                            if (ub7 >= 70 && ub7 <= 90) {
                                td.classList.add('green-bg');
                            } else if (ub7 > 90) {
                                td.classList.add('pink-bg');
                            } else if (ub7 < 70) {
                                td.classList.add('red-bg');
                            }

                            return ub7.toFixed(0) + "%";
                        }
                    },
                    {
                        title: "1 UB%",
                        field: "l1_spend",
                        hozAlign: "right",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var l1_spend = parseFloat(row.l1_spend) || 0;
                            var budget = parseFloat(row.campaignBudgetAmount) || 0;
                            var ub1 = budget > 0 ? (l1_spend / budget) * 100 : 0;

                            // Set cell background color based on UB%
                            var td = cell.getElement();
                            td.classList.remove('green-bg', 'pink-bg', 'red-bg');
                            if (ub1 >= 70 && ub1 <= 90) {
                                td.classList.add('green-bg');
                            } else if (ub1 > 90) {
                                td.classList.add('pink-bg');
                            } else if (ub1 < 70) {
                                td.classList.add('red-bg');
                            }

                            return ub1.toFixed(0) + "%";
                        }
                    },
                    {
                        title: "L7 CPC",
                        field: "l7_cpc",
                        hozAlign: "center",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var l7_cpc = parseFloat(row.l7_cpc) || 0;
                            return l7_cpc.toFixed(2);
                        }
                    },
                    {
                        title: "L1 CPC",
                        field: "l1_cpc",
                        hozAlign: "center",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var l1_cpc = parseFloat(row.l1_cpc) || 0;
                            return l1_cpc.toFixed(2);
                        }
                    },
                    {
                        title: "SBID",
                        field: "sbid",
                        hozAlign: "center",
                        formatter: function(cell) {
                            var row = cell.getRow().getData();
                            var crnt_bid = parseFloat(row.crnt_bid) || 0;
                            return crnt_bid * 0.9;
                        }
                    },
                    {
                        title: "APR BID",
                        field: "apr_bid",
                        hozAlign: "center"
                    },
                    {
                        title: "CRNT BID",
                        field: "crnt_bid",
                        hozAlign: "center"
                    },
                    {
                        title: "SBGT",
                        field: "sbgt",
                        hozAlign: "center"
                    },
                    {
                        title: "APR BGT",
                        field: "apr_bgt",
                        hozAlign: "center"
                    },
                ],
                ajaxResponse: function(url, params, response) {
                    return response.data;
                }
            });

            table.on("tableBuilt", function() {
                table.setFilter(function(data) {
                    var budget = parseFloat(data.campaignBudgetAmount) || 0;
                    var l7_spend = parseFloat(data.l7_spend || 0);
                    var l1_spend = parseFloat(data.l1_spend || 0);
            
                    var ub7 = budget > 0 ? (l7_spend / (budget * 7)) * 100 : 0;
                    var ub1 = budget > 0 ? (l1_spend / budget) * 100 : 0;
            
                    return ub7 > 90 && ub1 > 90;
                });
            });


            document.body.style.zoom = "80%";
        });
    </script>
@endsection
