@extends('layouts.vertical', ['title' => 'Account Health Master Dashboard', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('css')
    @vite(['node_modules/admin-resources/rwd-table/rwd-table.min.css'])
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 12px;
            --box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa !important;
            color: var(--dark-color) !important;
        }

        .container {
            max-width: 1200px !important;
            margin-top: 30px !important;
            margin-bottom: 50px !important;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        .header h4 {
            font-weight: 600;
            margin: 0;
        }

        .metric-value {
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }

        /* Negative growth (Red) */
        .negative-growth {
            background-color: rgb(255, 0, 0);
            color: rgb(0, 0, 0);
            width: 60px;
            text-align: center;
        }

        /* Zero growth (Yellow) */
        .zero-growth {
            background-color: rgb(255, 196, 0);
            color: rgb(0, 0, 0);
            width: 60px;
            text-align: center;
        }

        /* EXACTLY 100% (Magenta) */
        .exact-100 {
            background-color: #ff00ff;
            color: rgb(0, 0, 0);
            width: 60px;
            text-align: center;
        }

        .search-box {
            max-width: 350px;
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 5px;
            transition: var(--transition);
        }

        .dataTables_wrapper .dataTables_filter input {
            border: none;
            border-radius: 50px;
            padding: 8px 15px;
            margin-left: 10px;
        }

        .table>thead {
            vertical-align: bottom;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        th.sorting {
            color: white !important;
            font-size: 10px;
        }

        /* Rest of your existing styles... */
        /* Keep all your existing styles, just add these new ones below */

        /* DataTables custom styling */
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #333;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
            margin: 0 2px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary-color);
            color: white !important;
            border: 1px solid var(--primary-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e9ecef;
            border: 1px solid #ddd;
        }

        /* Loading indicator */
        .dataTables_processing {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        /* Responsive adjustments for DataTables */
        @media (max-width: 768px) {
            .dataTables_wrapper .dataTables_filter {
                float: none;
                text-align: left;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
    <style>
        /* Right-to-Left Modal Animation */
        .modal.right-to-left .modal-dialog {
            margin: 0;
            right: 0;
            width: 400px;
            max-width: 80%;
            height: 100%;
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
        }

        .modal.right-to-left.show .modal-dialog {
            transform: translateX(0);
        }

        .modal.right-to-left .modal-content {
            height: 100%;
            overflow-y: auto;
            border-radius: 0;
            border: none;
        }

        /* Keep your existing modal styling */
        .modal.right-to-left .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal.right-to-left .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* Sticky Dashboard Cards */
        .dashboard-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 1030;
            padding-top: 10px;
        }

        /* Scrollable table */
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: auto;
        }

        /* Sticky Table Header */
        thead.sticky-top th {
            position: sticky;
            top: 0;
            z-index: 1020;
            background-color: #fff;
            box-shadow: 0 2px 2px rgba(0, 0, 0, 0.05);
        }

        /* Optional Cleanup */
        table th,
        table td {
            white-space: nowrap;
        }

        .dropdown-search-item {
            padding: 6px 10px;
            cursor: pointer;
        }

        .dropdown-search-item:hover {
            background-color: #eee;
        }

        /* ========== PLAY/PAUSE NAVIGATION BUTTONS ========== */
        .time-navigation-group {
            margin-left: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            overflow: hidden;
            padding: 2px;
            background: #f8f9fa;
            display: inline-flex;
            align-items: center;
        }

        .time-navigation-group button {
            padding: 0;
            border-radius: 50% !important;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: all 0.2s ease;
            border: 1px solid #dee2e6;
            background: white;
            cursor: pointer;
        }

        .time-navigation-group button:hover {
            background-color: #f1f3f5 !important;
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .time-navigation-group button:active {
            transform: scale(0.95);
        }

        .time-navigation-group button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .time-navigation-group button i {
            font-size: 1.1rem;
            transition: transform 0.2s ease;
        }

        /* Play button */
        #play-auto {
            color: #28a745;
        }

        #play-auto:hover {
            background-color: #28a745 !important;
            color: white !important;
        }

        /* Pause button */
        #play-pause {
            color: #ffc107;
            display: none;
        }

        #play-pause:hover {
            background-color: #ffc107 !important;
            color: white !important;
        }

        /* Navigation buttons */
        #play-backward,
        #play-forward {
            color: #007bff;
        }

        #play-backward:hover,
        #play-forward:hover {
            background-color: #007bff !important;
            color: white !important;
        }

        /* Button state colors - must come after hover styles */
        #play-auto.btn-success,
        #play-pause.btn-success {
            background-color: #28a745 !important;
            color: white !important;
        }

        #play-auto.btn-warning,
        #play-pause.btn-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        #play-auto.btn-danger,
        #play-pause.btn-danger {
            background-color: #dc3545 !important;
            color: white !important;
        }

        #play-auto.btn-light,
        #play-pause.btn-light {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }

        /* Ensure hover doesn't override state colors */
        #play-auto.btn-success:hover,
        #play-pause.btn-success:hover {
            background-color: #28a745 !important;
            color: white !important;
        }

        #play-auto.btn-warning:hover,
        #play-pause.btn-warning:hover {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        #play-auto.btn-danger:hover,
        #play-pause.btn-danger:hover {
            background-color: #dc3545 !important;
            color: white !important;
        }

        /* Active state styling */
        .time-navigation-group button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .time-navigation-group button {
                width: 36px;
                height: 36px;
            }

            .time-navigation-group button i {
                font-size: 1rem;
            }
        }

        /* Add to your CSS file or style section */
        .hide-column {
            display: none !important;
        }

        .dataTables_length,
        .dataTables_filter {
            display: none;
        }

        #play-auto.green-btn {
            background-color: green !important;
            color: white;
        }

        #play-auto.red-btn {
            background-color: red !important;
            color: white;
        }

        th small.badge {
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .dataTables_processing {
            top: 150px !important;
            /* Try 80–100px depending on your header height */
            z-index: 1000 !important;
            background: none !important;
            border: none;
        }

        #channelTable {
            width: 100% !important;
            table-layout: fixed;
        }

        #channelTable thead th {
            color: white !important;
            font-size: 10px !important;
        }

        #channelTable th {
            text-transform: none !important;
        }
    </style>
@endsection


@section('content')
    @include('layouts.shared/page-title', [
        'page_title' => 'Account Health Master Dashboard',
        'sub_title' => 'Manage your channels and monitor their performance metrics',
    ])
    <div class="container-fluid">
        <div class="col-md-12 mt-0 pt-0 mb-1 pb-1">
            <div class="row justify-content-center align-items-center g-3">
                <div class="col d-flex align-items-center gap-2 flex-wrap">
                    <div class="btn-group time-navigation-group" role="group" aria-label="Parent navigation">
                        <button id="play-backward" class="btn btn-light rounded-circle" title="Previous parent">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button id="play-pause" class="btn btn-light rounded-circle" title="Show all products"
                            style="display: none;">
                            <i class="fas fa-pause"></i>
                        </button>
                        <button id="play-auto" class="btn btn-light rounded-circle" title="Show all products">
                            <i class="fas fa-play"></i>
                        </button>
                        <button id="play-forward" class="btn btn-light rounded-circle" title="Next parent">
                            <i class="fas fa-step-forward"></i>
                        </button>
                    </div>
                    <button id="addChannelBtn" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addChannelModal"
                        style="background: linear-gradient(135deg, #4361ee, #3f37c9); border: none;">
                        <i class="fas fa-plus-circle me-2"></i> Add Channel
                    </button>

                    <!-- Import/Export Buttons -->
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <!-- Export Button -->
                        <a href="{{ route('account-health-master.export') }}" class="btn btn-success">
                            <i class="fas fa-file-export me-1"></i> Export Health Data
                        </a>

                        <!-- Import Button -->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#accountHealthImportModal">
                            <i class="fas fa-file-import me-1"></i> Import Health Data
                        </button>
                    </div>

                </div>
                <div class="col-auto">
                    <div class="dropdown-search-container" style="position: relative;">
                        <input type="text" class="form-control form-control-sm channel-search"
                            placeholder="Search Channel" id="channelSearchInput">
                        <div class="dropdown-search-results" id="channelSearchDropdown"
                            style="position: absolute; top: 100%; left: 0; right: 0; z-index: 9999; background: #fff; border: 1px solid #ccc; display: none; max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Channel Modal -->
        <div class="modal fade right-to-left" id="addChannelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-side">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Add New Channel</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="channelForm">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="channelName" class="form-label">Channel Name</label>
                                    <input type="text" class="form-control" id="channelName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="channelUrl" class="form-label">Sheet Link</label>
                                <input type="url" class="form-control" id="channelUrl">
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Type</label>
                                <input type="text" class="form-control" id="type">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveChannelBtn">Save Channel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Channel Modal -->
        <div class="modal fade" id="editChannelModal" tabindex="-1" aria-labelledby="editChannelModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editChannelForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editChannelModalLabel">Edit Channel</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="originalChannel" name="original_channel">
                            <div class="mb-3">
                                <label for="editChannelName" class="form-label">Channel Name</label>
                                <input type="text" class="form-control" id="editChannelName" name="channel_name"
                                    readonly>
                            </div>
                            <div class="mb-3">
                                <label for="editChannelUrl" class="form-label">Sheet URL</label>
                                <input type="text" class="form-control" id="editChannelUrl" name="sheet_url"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editType" class="form-label">Type</label>
                                <input type="text" class="form-control" id="editType" name="type" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Update Channel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div class="modal fade" id="accountHealthImportModal" tabindex="-1"
            aria-labelledby="accountHealthImportModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('account-health-master.import') }}" method="POST" enctype="multipart/form-data"
                    class="modal-content" id="accountHealthImportForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="accountHealthImportModalLabel">Import Account Health Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- File Input -->
                        <div class="mb-3">
                            <label for="accountHealthExcelFile" class="form-label">Select Excel File</label>
                            <input type="file" class="form-control" id="accountHealthExcelFile" name="excel_file"
                                accept=".xlsx,.xls,.csv" required>
                        </div>

                        <!-- Import Type Selection -->
                        <div class="mb-3">
                            <label for="importType" class="form-label">Import Type</label>
                            <select class="form-control" id="importType" name="import_type" required>
                                <option value="">Select Import Type</option>
                                <option value="channel_data">Channel Performance Data</option>
                                <option value="health_rates">Health Rates (ODR, Fulfillment, etc.)</option>
                                <option value="both">Both Channel Data & Health Rates</option>
                            </select>
                        </div>

                        <!-- Update Mode -->
                        <div class="mb-3">
                            <label for="updateMode" class="form-label">Update Mode</label>
                            <select class="form-control" id="updateMode" name="update_mode" required>
                                <option value="update">Update Existing Records</option>
                                <option value="create">Create New Records Only</option>
                                <option value="replace">Replace All Data</option>
                            </select>
                        </div>

                        <!-- Sample File Links -->
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Download sample files:
                                <br>
                                • <a href="{{ route('account-health-master.sample', 'channel') }}"
                                    class="alert-link">Channel Data Sample</a>
                                <br>
                                • <a href="{{ route('account-health-master.sample', 'rates') }}" class="alert-link">Health
                                    Rates Sample</a>
                                <br>
                                • <a href="{{ route('account-health-master.sample', 'combined') }}"
                                    class="alert-link">Combined Sample</a>
                            </small>
                        </div>

                        <!-- Progress Bar (hidden initially) -->
                        <div class="progress mb-3" id="importProgress" style="display: none;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: 0%">0%</div>
                        </div>

                        <!-- Import Results (hidden initially) -->
                        <div id="importResults" class="alert alert-success" style="display: none;">
                            <h6>Import Results:</h6>
                            <ul id="importResultsList"></ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="importSubmitBtn">
                            <i class="fas fa-file-import me-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="customLoader" style="display: flex; justify-content: center; align-items: center; height: 300px;">
            <div class="spinner-border text-info" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <span class="ms-2">Loading datatable, please wait...</span>
        </div>

        <div class="mb-4">
            <div id="channelSalesChart" style="width: 100%; height: 400px;"></div>
        </div>

        <!-- Table Container -->
        <div class="table-container" id="channelTableWrapper" style="display: none;">
            <div class="table-responsive" style="max-height: 500px; overflow: auto;">
                <table class="table table-hover table-striped mb-0" id="channelTable">
                    <thead class="table sticky-top">
                        <tr>
                            <th>Channel</th>
                            <th class="text-center align-middle">
                                <small id="l30OrdersCountBadgeHeader" class="badge bg-dark text-white mb-1"
                                    style="font-size: 13px;">
                                    0
                                </small><br>
                                L30 Orders
                            </th>
                            <th>NR</th>
                            <th>ODR Rate</th>
                            <th>Fulfillment Rate</th>
                            <th>Valid Tracking Rate</th>
                            <th>On Time Delivery</th>
                            <th>A-Z Claims</th>
                            <th>Voilation/Compliance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Load DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <!-- Load Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Load Google Charts -->
    <script src="https://www.gstatic.com/charts/loader.js"></script>

    <script>
        var jq = jQuery.noConflict(true);
        let originalChannelData = [];
        let table;
        let isPlaying = false;
        let currentChannelIndex = 0;
        let uniqueChannels = [];
        let uniqueChannelRows = [];
        let tableData = [];

        function parseNumber(value) {
            if (value === null || value === undefined || value === '' || value === '#DIV/0!' || value === 'N/A') return 0;
            if (typeof value === 'number') return value;
            const cleaned = String(value).replace(/[^0-9.-]/g, '');
            return parseFloat(cleaned) || 0;
        }

        function updateL30OrdersTotal(data) {
            let l30OrdersTotal = 0;

            data.forEach(row => {
                const l30Orders = parseNumber(row['L30 Orders'] || 0);
                l30OrdersTotal += l30Orders;
            });
            console.log('Total L30 Orders:', l30OrdersTotal);

            document.getElementById('l30OrdersCountBadge').textContent = Math.round(l30OrdersTotal).toLocaleString('en-US');
            document.getElementById('l30OrdersCountBadgeHeader').textContent = Math.round(l30OrdersTotal).toLocaleString(
                'en-US');
        }

        function initializeDataTable() {
            if (!jq('#channelTable').length) {
                console.error('Table element not found');
                return null;
            }

            if (jq.fn.DataTable.isDataTable('#channelTable')) {
                jq('#channelTable').DataTable().clear().destroy();
                jq('#channelTable').empty();
            }

            const toNum = (v, def = 0) => {
                const n = parseFloat(String(v).replace(/,/g, ''));
                return Number.isFinite(n) ? n : def;
            };
            const pick = (obj, keys, def = '') => {
                for (const k of keys) {
                    const v = obj[k];
                    if (v !== undefined && v !== null && v !== '') return v;
                }
                return def;
            };

            table = jq('#channelTable').DataTable({
                processing: true,
                serverSide: false,
                ordering: false,
                searching: true,
                pageLength: 50,
                destroy: true,
                ajax: {
                    url: '/account-health-master/dashboard-data',
                    type: "GET",
                    data: function(d) {
                        d.channel = jq('#channelSearchInput').val();
                    },
                    dataSrc: function(json) {
                        // Hide loader and show table only after data is loaded
                        jq('#customLoader').hide();
                        jq('#channelTableWrapper').show();

                        if (!json || !json.data) return [];
                        tableData = json.data; // Store for graph
                        drawChannelChart(tableData);
                        return json.data.map(item => ({
                            'Channel': pick(item, ['channel', 'Channel', 'Channel '], ''),
                            'L30 Orders': toNum(pick(item, ['L30 Orders', 'l30_orders'], 0), 0),
                            'NR': toNum(pick(item, ['nr', 'NR'], 0), 0),
                            'ODR Rate': pick(item, ['ODR'], 'N/A'),
                            'Fulfillment Rate': pick(item, ['Fulfillment Rate'], 'N/A'),
                            'Valid Tracking Rate': pick(item, ['Valid Tracking Rate'], 'N/A'),
                            'On Time Delivery': pick(item, ['On Time Delivery Rate'], 'N/A'),
                            'A-Z Claims': pick(item, ['AtoZ Claims Rate'], 'N/A'),
                            'Voilation/Compliance': pick(item, ['Voilation Rate'], 'N/A'),
                        }));
                    },
                    error: function(xhr, error, thrown) {
                        console.log("AJAX error:", error, thrown);
                        // Hide loader on error too
                        jq('#customLoader').hide();
                        jq('#channelTableWrapper').show();
                    },
                    // Show loader when request starts
                    beforeSend: function() {
                        jq('#customLoader').show();
                        jq('#channelTableWrapper').hide();
                    }
                },
                columns: [{
                        data: 'Channel',
                        render: function(data, type, row) {
                            if (!data) return '';
                            const channelName = data.trim().toLowerCase();
                            const routeMap = {
                                'amazon': '/overall-amazon',
                                'amazon fba': '/overall-amazon-fba',
                                'ebay': '/ebay',
                                'ebaytwo': '/ebayTwoAnalysis',
                                'ebaythree': '/ebayThreeAnalysis',
                                'temu': '/temu',
                                'macys': '/macys',
                                'wayfair': '/Wayfair',
                                'reverb': '/reverb',
                                'shopify b2c': '/shopifyB2C',
                                'doba': '/doba',
                                'walmart': '/walmartAnalysis',
                                'bestbuy usa': '/bestbuyusa-analytics',
                            };
                            const routeUrl = routeMap[channelName];
                            return routeUrl ?
                                `<a href="${routeUrl}" target="_blank" style="color: #007bff; text-decoration: underline;">${data}</a>` :
                                `<div class="d-flex align-items-center"><span>${data}</span></div>`;
                        }
                    },
                    {
                        data: 'L30 Orders',
                        render: function(data, type) {
                            const n = parseFloat(String(data).replace(/,/g, '')) || 0;
                            if (type === 'sort' || type === 'type') return n;
                            return `<span class="metric-value">${n.toLocaleString('en-US')}</span>`;
                        }
                    },
                    {
                        data: 'NR',
                        render: function(v, t, row) {
                            const checked = toNum(v) === 1 ? 'checked' : '';
                            return `<input type="checkbox" class="checkbox-nr" data-channel="${row['Channel']}" ${checked}>`;
                        }
                    },
                    {
                        data: 'ODR Rate',
                        render: v => `<span class="metric-value">${v}</span>`
                    },
                    {
                        data: 'Fulfillment Rate',
                        render: v => `<span class="metric-value">${v}</span>`
                    },
                    {
                        data: 'Valid Tracking Rate',
                        render: v => `<span class="metric-value">${v}</span>`
                    },
                    {
                        data: 'On Time Delivery',
                        render: v => `<span class="metric-value">${v}</span>`
                    },
                    {
                        data: 'A-Z Claims',
                        render: v => `<span class="metric-value">${v}</span>`
                    },
                    {
                        data: 'Voilation/Compliance',
                        render: v => `<span class="metric-value">${v}</span>`
                    },
                    // {
                    //     data: null,
                    //     render: function(_d, _t, row, meta) {
                    //         return `
                    //             <div class="d-flex justify-content-center">
                    //                 <button class="btn btn-sm btn-outline-primary edit-btn me-1" title="Edit" data-index="${meta.row}" data-channel="${row['Channel'] || ''}">
                    //                     <i class="fas fa-edit"></i>
                    //                 </button>
                    //                 <button class="btn btn-sm btn-outline-danger delete-btn" title="Archive">
                    //                     <i class="fa fa-archive"></i>
                    //                 </button>
                    //             </div>`;
                    //     }
                    // }
                ],
                // responsive: true,
                // language: {
                //     processing: "Loading data, please wait...",
                //     emptyTable: "",
                //     zeroRecords: "",
                // }
            });

            return table;
        }

        function drawChannelChart(data) {
            if (!data || data.length === 0) return;

            google.charts.load('current', {
                packages: ['corechart']
            });
            google.charts.setOnLoadCallback(function() {
                renderChart(data);
            });
        }

        function renderChart(data) {
            let chartData = [
                ['Channel', 'L30 Orders', {
                    role: 'annotation'
                }]
            ];

            data.forEach(row => {
                let channel = row['Channel'] || row['Channel '] || '';
                let l30Orders = parseFloat(row['L30 Orders'] || 0);

                let rowData = [channel, l30Orders, l30Orders > 0 ? channel : null];
                chartData.push(rowData);
            });

            let dataTable = google.visualization.arrayToDataTable(chartData);
            let options = {
                curveType: 'function',
                legend: {
                    position: 'bottom'
                },
                hAxis: {
                    textPosition: 'none'
                },
                vAxis: {
                    title: 'Value',
                    minValue: 0
                },
                pointSize: 5,
                annotations: {
                    alwaysOutside: true,
                    textStyle: {
                        fontSize: 11,
                        bold: true,
                        color: '#000'
                    }
                },
                series: {
                    0: {
                        color: '#1E88E5',
                        lineWidth: 4
                    }
                }
            };
            let chart = new google.visualization.LineChart(document.getElementById('channelSalesChart'));
            chart.draw(dataTable, options);
        }

        function updatePlayButtonColor() {
            if (!table || !table.rows) return;
            const visibleRow = table.rows({
                search: 'applied'
            }).nodes().to$();
            if (!visibleRow || !visibleRow.length) return;
            const raCheckbox = visibleRow.find('.ra-checkbox');
            if (raCheckbox.length) {
                const isChecked = raCheckbox.prop('checked');
                jq('#play-pause').removeClass('btn-light btn-success btn-danger').addClass(isChecked ? 'btn-success' :
                    'btn-danger').css('color', 'white');
            }
        }

        function startPlayback() {
            if (!originalChannelData.length) return;
            uniqueChannels = [...new Set(originalChannelData.map(item => item['Channel ']?.trim()))].filter(Boolean);
            uniqueChannelRows = uniqueChannels.map(channel => originalChannelData.find(item => item['Channel ']?.trim() ===
                channel));
            if (!uniqueChannelRows.length) return;

            currentChannelIndex = 0;
            isPlaying = true;
            table.page.len(1).draw();
            table.search('').columns().search('').draw();
            showCurrentChannel();

            document.getElementById('play-auto').style.display = 'none';
            document.getElementById('play-pause').style.display = 'block';
            setTimeout(() => updatePlayButtonColor(), 500);
        }

        function stopPlayback() {
            isPlaying = false;
            table.clear().rows.add(originalChannelData).draw();
            table.page.len(25).draw();
            document.getElementById('play-auto').style.display = 'block';
            document.getElementById('play-pause').style.display = 'none';
        }

        function showCurrentChannel() {
            if (!isPlaying || !uniqueChannelRows.length) return;
            const currentRow = uniqueChannelRows[currentChannelIndex];
            if (currentRow) {
                table.clear().rows.add([currentRow]).draw();
                document.getElementById('channelSearchInput').value = currentRow['Channel ']?.trim() || '';
                const tableContainer = document.getElementById('channelTable')?.parentElement;
                if (tableContainer) tableContainer.scrollTop = 0;
                setTimeout(() => updatePlayButtonColor(), 500);
            }
        }

        function nextChannel() {
            if (!isPlaying) return;
            if (currentChannelIndex < uniqueChannelRows.length - 1) {
                currentChannelIndex++;
                showCurrentChannel();
            } else {
                stopPlayback();
            }
        }

        function previousChannel() {
            if (!isPlaying) return;
            if (currentChannelIndex > 0) {
                currentChannelIndex--;
                showCurrentChannel();
            }
        }

        function populateChannelDropdown(searchTerm = '') {
            const channelData = originalChannelData.map(row => row['Channel ']);
            const uniqueChannels = [...new Set(channelData)].filter(ch => ch && ch.trim() !== '').sort();
            const lowerSearch = searchTerm.toLowerCase();
            const sortedChannels = uniqueChannels.sort((a, b) => {
                const aMatch = a.toLowerCase().includes(lowerSearch);
                const bMatch = b.toLowerCase().includes(lowerSearch);
                if (aMatch && !bMatch) return -1;
                if (!aMatch && bMatch) return 1;
                return a.localeCompare(b);
            });

            const dropdown = document.getElementById('channelSearchDropdown');
            if (!dropdown) return;
            dropdown.innerHTML = '';
            uniqueChannels.forEach(channel => {
                const item = document.createElement('div');
                item.className = 'dropdown-search-item';
                item.dataset.value = channel;
                item.textContent = channel;
                dropdown.appendChild(item);
            });
            dropdown.style.display = 'block';
        }

        // Import functionality
        document.addEventListener('DOMContentLoaded', function() {
            const importForm = document.getElementById('accountHealthImportForm');
            const progressBar = document.getElementById('importProgress');
            const resultsDiv = document.getElementById('importResults');
            const submitBtn = document.getElementById('importSubmitBtn');

            if (importForm) {
                importForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    // Show progress bar and hide results
                    progressBar.style.display = 'block';
                    resultsDiv.style.display = 'none';
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Importing...';

                    // Simulate progress
                    let progress = 0;
                    const progressInterval = setInterval(() => {
                        progress += 10;
                        const progressBarInner = progressBar.querySelector('.progress-bar');
                        progressBarInner.style.width = progress + '%';
                        progressBarInner.textContent = progress + '%';

                        if (progress >= 90) {
                            clearInterval(progressInterval);
                        }
                    }, 200);

                    // Make AJAX request
                    fetch(this.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            clearInterval(progressInterval);

                            // Complete progress bar
                            const progressBarInner = progressBar.querySelector('.progress-bar');
                            progressBarInner.style.width = '100%';
                            progressBarInner.textContent = '100%';

                            setTimeout(() => {
                                progressBar.style.display = 'none';

                                if (data.success) {
                                    // Show results
                                    resultsDiv.className = 'alert alert-success';
                                    const resultsList = document.getElementById(
                                        'importResultsList');
                                    resultsList.innerHTML = '';

                                    if (data.results) {
                                        Object.keys(data.results).forEach(key => {
                                            const li = document.createElement('li');
                                            li.textContent =
                                                `${key}: ${data.results[key]}`;
                                            resultsList.appendChild(li);
                                        });
                                    }

                                    resultsDiv.style.display = 'block';

                                    // Reload table data
                                    if (typeof table !== 'undefined' && table) {
                                        table.ajax.reload();
                                    }

                                    // Auto close modal after 3 seconds
                                    setTimeout(() => {
                                        jq('#accountHealthImportModal').modal('hide');
                                        location
                                            .reload(); // Refresh the page to show updated data
                                    }, 3000);

                                } else {
                                    resultsDiv.className = 'alert alert-danger';
                                    resultsDiv.innerHTML =
                                        `<strong>Error:</strong> ${data.message || 'Import failed'}`;
                                    resultsDiv.style.display = 'block';
                                }
                            }, 500);
                        })
                        .catch(error => {
                            clearInterval(progressInterval);
                            progressBar.style.display = 'none';

                            resultsDiv.className = 'alert alert-danger';
                            resultsDiv.innerHTML =
                                '<strong>Error:</strong> Network error occurred during import';
                            resultsDiv.style.display = 'block';

                            console.error('Import error:', error);
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-file-import me-1"></i> Import';
                        });
                });
            }

            // Reset modal when closed
            jq('#accountHealthImportModal').on('hidden.bs.modal', function() {
                if (importForm) {
                    importForm.reset();
                    progressBar.style.display = 'none';
                    resultsDiv.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-file-import me-1"></i> Import';
                }
            });
        });

        window.csrfToken = '{{ csrf_token() }}';

        jq(document).ready(function() {
            // Show loader initially
            jq('#customLoader').show();
            jq('#channelTableWrapper').hide();

            // Initialize DataTable first
            table = initializeDataTable();
            if (!table) return;

            // Load initial data separately to populate originalChannelData
            jq.ajax({
                url: '/account-health-master/dashboard-data',
                type: "GET",
                success: function(json) {
                    if (json && json.data) {
                        originalChannelData = json.data;

                        // Setup event handlers after data is loaded
                        setupEventHandlers();
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('Error loading initial data:', error, thrown);
                    jq('#customLoader').hide();
                    jq('#channelTableWrapper').show();
                }
            });
        });

        function setupEventHandlers() {
            // Play button handlers
            document.getElementById('play-auto').addEventListener('click', startPlayback);
            document.getElementById('play-pause').addEventListener('click', stopPlayback);
            document.getElementById('play-forward').addEventListener('click', nextChannel);
            document.getElementById('play-backward').addEventListener('click', previousChannel);
            document.getElementById('play-pause').style.display = 'none';

            // Search functionality
            const channelSearchInput = document.getElementById('channelSearchInput');
            const channelSearchDropdown = document.getElementById('channelSearchDropdown');

            if (channelSearchInput) {
                channelSearchInput.addEventListener('focus', () => populateChannelDropdown());
                channelSearchInput.addEventListener('input', function() {
                    const val = this.value.trim();
                    if (val === '') {
                        table.column(0).search('').draw();
                    }
                    populateChannelDropdown(val);
                });
            }

            if (channelSearchDropdown) {
                channelSearchDropdown.addEventListener('click', function(e) {
                    if (e.target.classList.contains('dropdown-search-item')) {
                        const selectedChannel = e.target.dataset.value;
                        document.getElementById('channelSearchInput').value = selectedChannel;
                        this.style.display = 'none';
                        table.column(0).search(selectedChannel, true, false).draw();
                    }
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown-search-container')) {
                    document.getElementById('channelSearchDropdown').style.display = 'none';
                }
            });

            // Update L30 orders total when table is redrawn
            table.on('draw', function() {
                var data = table.rows({
                    search: 'applied'
                }).data().toArray();
                updateL30OrdersTotal(data);
            });

            // Modal handlers
            setupModalHandlers();

            // Checkbox handlers
            setupCheckboxHandlers();
        }

        function setupModalHandlers() {
            // Add channel modal
            jq('#addChannelModal .btn-primary').on('click', function() {
                const channelName = jq('#channelName').val().trim();
                const channelUrl = jq('#channelUrl').val().trim();
                const type = jq('#type').val().trim();

                if (!channelName || !channelUrl || !type) {
                    alert("Channel Name, URL, and Type are required.");
                    return;
                }

                jq.ajax({
                    url: '/channel_master/store',
                    method: 'POST',
                    data: {
                        channel: channelName,
                        sheet_link: channelUrl,
                        type: type,
                        _token: jq('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            jq('#addChannelModal').modal('hide');
                            jq('#channelForm')[0].reset();
                            location.reload();
                        } else {
                            alert("Error: " + (res.message || 'Something went wrong.'));
                        }
                    },
                    error: function() {
                        alert("Error submitting form.");
                    }
                });
            });

            // Edit channel modal
            jq(document).on('click', '.edit-btn', function() {
                const index = jq(this).data('index');
                const rowData = tableData[index];
                const channel = rowData["Channel "]?.trim() || rowData["channel"] || '';
                const sheetUrl = rowData["sheet_link"] || '';
                const type = rowData["type"]?.trim() || '';

                jq('#editChannelName').val(channel);
                jq('#editChannelUrl').val(sheetUrl);
                jq('#editType').val(type);
                jq('#originalChannel').val(channel);
                jq('#editChannelModal').modal('show');
            });

            // Edit form submission
            jq('#editChannelForm').on('submit', function(e) {
                e.preventDefault();
                const channel = jq('#editChannelName').val().trim();
                const sheetUrl = jq('#editChannelUrl').val().trim();
                const type = jq('#editType').val().trim();
                const originalChannel = jq('#originalChannel').val().trim();

                if (!channel || !sheetUrl) {
                    alert("Channel Name and Sheet URL are required.");
                    return;
                }

                jq.ajax({
                    url: '/channel_master/update',
                    method: 'POST',
                    data: {
                        channel: channel,
                        sheet_url: sheetUrl,
                        type: type,
                        original_channel: originalChannel,
                        _token: jq('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            jq('#editChannelModal').modal('hide');
                            jq('#editChannelForm')[0].reset();
                            location.reload();
                        } else {
                            alert("Error: " + (res.message || 'Update failed.'));
                        }
                    },
                    error: function() {
                        alert("Something went wrong while updating.");
                    }
                });
            });
        }

        function setupCheckboxHandlers() {
            jq(document).on('change', '.checkbox-nr', function() {
                const channel = jq(this).data('channel');
                const value = jq(this).is(':checked') ? 1 : 0;

                jq.ajax({
                    url: '/channels-master/toggle-flag',
                    method: 'POST',
                    data: {
                        channel: channel,
                        field: 'nr',
                        value: value
                    },
                    headers: {
                        'X-CSRF-TOKEN': jq('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (!res.success) {
                            alert('Failed to update: ' + res.message);
                        }
                    },
                    error: function() {
                        alert('Server error while updating checkbox.');
                    }
                });
            });
        }
    </script>
@endsection
