@extends('layouts.vertical', ['title' => 'Pricing masters', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

<meta name="csrf-token" content="{{ csrf_token() }}">
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== TABLE STRUCTURE ========== */
        .table-container {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            max-height: 600px;
        }

        .custom-resizable-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .parent-row .avg-price-cell {
            display: none;
        }

        .custom-resizable-table th,
        .custom-resizable-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            position: relative;
            white-space: nowrap;
            overflow: visible !important;
        }

        .custom-resizable-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            user-select: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* ========== RESIZABLE COLUMNS ========== */
        .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            cursor: col-resize;
            z-index: 100;
        }

        .resize-handle:hover,
        .resize-handle.resizing {
            background: rgba(0, 0, 0, 0.3);
        }

        /* ========== TOOLTIP SYSTEM ========== */
        .tooltip-container {
            position: relative;
            display: inline-block;
            margin-left: 8px;
        }

        .tooltip-icon {
            cursor: pointer;
            transform: translateY(1px);
        }

        .tooltip {
            z-index: 9999 !important;
            pointer-events: none;
        }

        .tooltip-inner {
            transform: translate(-5px, -5px) !important;
            max-width: 300px;
            padding: 6px 10px;
            font-size: 13px;
        }

        .bs-tooltip-top .tooltip-arrow {
            bottom: 0;
        }

        .bs-tooltip-top .tooltip-arrow::before {
            transform: translateX(5px) !important;
            border-top-color: var(--bs-tooltip-bg);
        }

        /* ========== COLOR CODED CELLS ========== */
        .dil-percent-cell {
            padding: 8px 4px !important;
        }

        .dil-percent-value {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        .dil-percent-value.red {
            background-color: #dc3545;
            color: white;
        }

        .dil-percent-value.blue {
            background-color: #3591dc;
            color: white;
        }

        .dil-percent-value.yellow {
            background-color: #ffc107;
            color: #212529;
        }

        .dil-percent-value.green {
            background-color: #28a745;
            color: white;
        }

        .dil-percent-value.pink {
            background-color: #e83e8c;
            color: white;
        }

        .dil-percent-value.gray {
            background-color: #6c757d;
            color: white;
        }

        /* ========== TABLE CONTROLS ========== */
        .table-controls {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }

        /* ========== SORTING ========== */
        .sortable {
            cursor: pointer;
        }

        .sortable:hover {
            background-color: #f1f1f1;
        }

        .sort-arrow {
            display: inline-block;
            margin-left: 5px;
        }

        /* ========== PARENT ROWS ========== */
        .parent-row {
            background-color: rgba(69, 233, 255, 0.1) !important;
        }

        /* ========== SKU TOOLTIPS ========== */
        .sku-tooltip-container {
            position: relative;
            display: inline-block;
        }

        .sku-tooltip {
            visibility: hidden;
            width: auto;
            min-width: 120px;
            background-color: #fff;
            color: #333;
            text-align: left;
            border-radius: 4px;
            padding: 8px;
            position: absolute;
            z-index: 1001;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            white-space: nowrap;
        }

        .sku-tooltip-container:hover .sku-tooltip {
            visibility: visible;
            opacity: 1;
        }

        .sku-link {
            padding: 4px 0;
            white-space: nowrap;
        }

        .sku-link a {
            color: #0d6efd;
            text-decoration: none;
        }

        .sku-link a:hover {
            text-decoration: underline;
        }

        /* ========== DROPDOWNS ========== */
        .custom-dropdown {
            position: relative;
            display: inline-block;
        }

        .custom-dropdown-menu {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .custom-dropdown-menu.show {
            display: block;
        }

        .column-toggle-item {
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .column-toggle-item:hover {
            background-color: #f8f9fa;
        }

        .column-toggle-checkbox {
            margin-right: 8px;
        }

        /* ========== LOADER ========== */
        .card-loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 0.25rem;
        }

        .loader-content {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .loader-text {
            margin-top: 15px;
            font-weight: 500;
            color: #333;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* ========== CARD BODY ========== */
        .card-body {
            position: relative;
        }

        /* ========== SEARCH DROPDOWNS ========== */
        .dropdown-search-container {
            position: relative;
        }

        .dropdown-search-results {
            position: absolute;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .dropdown-search-item {
            padding: 8px 12px;
            cursor: pointer;
        }

        .dropdown-search-item:hover {
            background-color: #f8f9fa;
        }

        .no-results {
            color: #6c757d;
            font-style: italic;
        }

        /* ========== STATUS INDICATORS ========== */
        .status-circle {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
            border: 1px solid #fff;
        }

        .status-circle.default {
            background-color: #6c757d;
        }

        .status-circle.red {
            background-color: #dc3545;
        }

        .status-circle.yellow {
            background-color: #ffc107;
        }

        .status-circle.blue {
            background-color: #007bff;
        }

        .status-circle.green {
            background-color: #28a745;
        }

        .status-circle.pink {
            background-color: #e83e8c;
        }

        /* ========== FILTER CONTROLS ========== */
        .d-flex.flex-wrap.gap-2 {
            gap: 0.5rem !important;
            margin-bottom: 1rem;
        }

        .btn-sm i.fas {
            margin-right: 5px;
        }

        .manual-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .manual-dropdown-container .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            min-width: 160px;
            padding: 5px 0;
            margin: 2px 0 0;
            background-color: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 4px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        }

        .manual-dropdown-container.show .dropdown-menu {
            display: block;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 8px 16px;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
        }

        .dropdown-item:hover {
            color: #16181b;
            text-decoration: none;
            background-color: #f8f9fa;
        }

        /* ========== MODAL SYSTEM ========== */
        .custom-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1050;
            overflow: hidden;
            outline: 0;
            pointer-events: none;
        }

        .custom-modal.show {
            display: block;
        }

        .custom-modal-dialog {
            position: fixed;
            width: auto;
            min-width: 600px;
            max-width: 90vw;
            margin: 1.75rem auto;
            pointer-events: auto;
            z-index: 1051;
            transition: transform 0.3s ease-out;
            background-color: white;
            border-radius: 0.3rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .custom-modal-content {
            pointer-events: auto;
        }

        .custom-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            border-top-left-radius: 0.3rem;
            border-top-right-radius: 0.3rem;
            background-color: #f8f9fa;
        }

        .custom-modal-title {
            margin-bottom: 0;
            line-height: 1.5;
            font-size: 1.25rem;
        }

        .custom-modal-close {
            padding: 0;
            background-color: transparent;
            border: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: 0.5;
            cursor: pointer;
        }

        .custom-modal-close:hover {
            opacity: 0.75;
        }

        .custom-modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
            overflow-y: auto;
            max-height: 70vh;
        }

        /* Multiple Modal Stacking */
        .custom-modal:nth-child(1) .custom-modal-dialog {
            top: 20px;
            right: 20px;
            z-index: 1051;
        }

        .custom-modal:nth-child(2) .custom-modal-dialog {
            top: 40px;
            right: 40px;
            z-index: 1052;
        }

        .custom-modal:nth-child(3) .custom-modal-dialog {
            top: 60px;
            right: 60px;
            z-index: 1053;
        }

        .custom-modal:nth-child(4) .custom-modal-dialog {
            top: 80px;
            right: 80px;
            z-index: 1054;
        }

        .custom-modal:nth-child(5) .custom-modal-dialog {
            top: 100px;
            right: 100px;
            z-index: 1055;
        }

        /* For more than 5 modals - dynamic calculation */
        .custom-modal:nth-child(n+6) .custom-modal-dialog {
            top: calc(100px + (var(--modal-offset) * 20px));
            right: calc(100px + (var(--modal-offset) * 20px));
            z-index: calc(1055 + var(--modal-offset));
        }

        /* Animations */
        @keyframes modalSlideIn {
            from {
                transform: translateX(30px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .custom-modal.show .custom-modal-dialog {
            animation: modalSlideIn 0.3s ease-out;
        }

        .custom-modal-backdrop.show {
            display: block;
            animation: modalFadeIn 0.15s linear;
        }

        /* Body scroll lock */
        body.custom-modal-open {
            overflow: hidden;
            padding-right: 15px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .custom-modal-dialog {
                min-width: 95vw;
                max-width: 95vw;
                margin: 0.5rem auto;
            }

            .custom-modal:nth-child(1) .custom-modal-dialog,
            .custom-modal:nth-child(2) .custom-modal-dialog,
            .custom-modal:nth-child(3) .custom-modal-dialog,
            .custom-modal:nth-child(4) .custom-modal-dialog,
            .custom-modal:nth-child(5) .custom-modal-dialog,
            .custom-modal:nth-child(n+6) .custom-modal-dialog {
                top: 10px;
                right: 10px;
                left: 10px;
                margin: 0 auto;
            }
        }

        .image-wrapper {
            width: 40px;
            height: 40px;
            overflow: visible;
            /* show zoom outside cell */
            position: relative;
        }

        .zoom-image {
            width: 100%;
            height: auto;
            border-radius: 4px;
            transition: transform 0.3s ease;
            display: block;
        }

        .zoom-image:hover {
            transform: scale(2);
            z-index: 999;
            position: absolute;
            top: 0;
            left: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            background: #fff;
        }

        /* Status color overlays */
        .custom-modal .card.card-bg-red {
            background: linear-gradient(135deg, rgba(245, 0, 20, 0.69), rgba(255, 255, 255, 0.85));
            border-color: rgba(220, 53, 70, 0.72);
        }

        .custom-modal .card.card-bg-green {
            background: linear-gradient(135deg, rgba(3, 255, 62, 0.424), rgba(255, 255, 255, 0.85));
            border-color: rgba(40, 167, 69, 0.3);
        }

        .custom-modal .card.card-bg-yellow {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 255, 255, 0.85));
            border-color: rgba(255, 193, 7, 0.3);
        }

        .custom-modal .card.card-bg-blue {
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.15), rgba(255, 255, 255, 0.85));
            border-color: rgba(0, 123, 255, 0.3);
        }

        .custom-modal .card.card-bg-pink {
            background: linear-gradient(135deg, rgba(232, 62, 140, 0.15), rgba(255, 255, 255, 0.85));
            border-color: rgba(232, 62, 141, 0.424);
        }

        .custom-modal .card.card-bg-gray {
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.15), rgba(255, 255, 255, 0.85));
            border-color: rgba(108, 117, 125, 0.3);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .custom-modal.show .custom-modal-dialog {
            animation: slideInRight 0.3s ease-out;
        }

        /* Close All button */
        #close-all-modals {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1060;
        }

        .custom-modal-dialog {
            position: fixed !important;
            top: 20px;
            right: 20px;
            margin: 0 !important;
            transform: none !important;
            cursor: move;
        }

        .custom-modal-header {
            cursor: move;
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

        /*popup modal style*/

        .choose-file {
            background-color: #ff6b2c;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            width: 100%;
            display: block;
            transition: background-color 0.3s;
        }

        .choose-file:hover {
            background-color: #e65c1e;
        }

        .modal-content {
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: 600;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }

        option[value="Todo"] {
            background-color: #2196f3;
        }

        option[value="Not Started"] {
            background-color: #ffff00;
            color: #000;
        }

        option[value="Working"] {
            background-color: #ff00ff;
        }

        option[value="In Progress"] {
            background-color: #f1c40f;
            color: #000;
        }

        option[value="Monitor"] {
            background-color: #5c6bc0;
        }

        option[value="Done"] {
            background-color: #00ff00;
            color: #000;
        }

        option[value="Need Help"] {
            background-color: #e91e63;
        }

        option[value="Review"] {
            background-color: #ffffff;
            color: #000;
        }

        option[value="Need Approval"] {
            background-color: #d4ff00;
            color: #000;
        }

        option[value="Dependent"] {
            background-color: #ff9999;
        }

        option[value="Approved"] {
            background-color: #ffeb3b;
            color: #000;
        }

        option[value="Hold"] {
            background-color: #ffffff;
            color: #000;
        }

        option[value="Rework"] {
            background-color: #673ab7;
        }

        option[value="Urgent"] {
            background-color: #f44336;
        }

        option[value="Q-Task"] {
            background-color: #ff00ff;
        }

        /*popup modal style end */

        /* Search input group styling */
        .input-group {
            width: auto;
            max-width: 300px;
        }

        .input-group .form-control {
            border-right: 0;
        }

        .input-group-append .btn {
            padding: 0.25rem 0.5rem;
            border-left: 0;
        }

        .input-group-append .btn:first-child {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .input-group-append .btn:not(:first-child) {
            border-left: 1px solid #ced4da;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        /* Loading indicator for full data load */
        .full-data-loading {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            display: flex;
            align-items: center;
        }

        .full-data-loading .spinner {
            margin-right: 10px;
        }

        /* Auto-closing success notification */
        .alert-dismissible-auto-close {
            animation: fadeOut 5s forwards;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                opacity: 0;
                display: none;
            }
        }

        /* Pagination button styling */
        .pagination-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination-controls button {
            min-width: 80px;
            padding: 5px 10px;
        }

        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #page-info {
            margin: 0 15px;
            font-weight: 500;
        }

        #visible-rows {
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            margin-left: 10px;
        }

        /* Notification styling */
        .custom-notification {
            transition: opacity 0.5s;
        }

        .custom-notification.fade-out {
            opacity: 0;
        }
    </style>
@endsection

@section('content')
    @include('layouts.shared/page-title', [
        'page_title' => 'Pricing masters',
        'sub_title' => 'Product masters',
    ])

    <!-- Add this somewhere in HTML -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="mainToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">Saved successfully</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Pricing masters Analysis</h4>

                    <!-- Filter Controls -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <!-- Dil% Filter -->
                        <div class="dropdown manual-dropdown-container">
                            <button class="btn btn-light dropdown-toggle" type="button" id="dilFilterDropdown">
                                <span class="status-circle default"></span>DIL%
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dilFilterDropdown">
                                <li><a class="dropdown-item column-filter" href="#" data-column="Dil%"
                                        data-color="all">
                                        <span class="status-circle default"></span> All DIL</a></li>
                                <li><a class="dropdown-item column-filter" href="#" data-column="Dil%"
                                        data-color="red">
                                        <span class="status-circle red"></span> Red</a></li>
                                <li><a class="dropdown-item column-filter" href="#" data-column="Dil%"
                                        data-color="yellow">
                                        <span class="status-circle yellow"></span> Yellow</a></li>
                                <li><a class="dropdown-item column-filter" href="#" data-column="Dil%"
                                        data-color="green">
                                        <span class="status-circle green"></span> Green</a></li>
                                <li><a class="dropdown-item column-filter" href="#" data-column="Dil%"
                                        data-color="pink">
                                        <span class="status-circle pink"></span> Pink</a></li>
                            </ul>
                        </div>

                        <!-- Task Board Button -->
                        <button type="button" class="btn btn-primary btn-sm" id="createTaskBtn">
                            <i class="bi bi-plus-circle me-2"></i>Create Task
                        </button>

                        <!-- Modal -->

                        <div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="createTaskModalLabel">📝 Create New Task Ebay to Task
                                            Manager</h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <form id="taskForm">
                                            <div class="form-section">
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label">Group</label>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter Group">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Title<span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter Title">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Priority</label>
                                                        <select class="form-select">
                                                            <option>Low</option>
                                                            <option>Medium</option>
                                                            <option>High</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-section">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Assignor<span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select">
                                                            <option selected disabled>Select Assignor</option>
                                                            <option>Srabani Ghosh</option>
                                                            <option>Rahul Mehta</option>
                                                            <option>Anjali Verma</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select">
                                                            <option disabled selected>Select Status</option>
                                                            <option value="Todo">Todo</option>
                                                            <option value="Not Started">Not Started</option>
                                                            <option value="Working">Working</option>
                                                            <option value="In Progress">In Progress</option>
                                                            <option value="Monitor">Monitor</option>
                                                            <option value="Done">Done</option>
                                                            <option value="Need Help">Need Help</option>
                                                            <option value="Review">Review</option>
                                                            <option value="Need Approval">Need Approval</option>
                                                            <option value="Dependent">Dependent</option>
                                                            <option value="Approved">Approved</option>
                                                            <option value="Hold">Hold</option>
                                                            <option value="Rework">Rework</option>
                                                            <option value="Urgent">Urgent</option>
                                                            <option value="Q-Task">Q-Task</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Assign To<span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select">
                                                            <option>Please Select</option>
                                                            <option>Dev Team</option>
                                                            <option>QA Team</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Duration<span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" id="duration" class="form-control"
                                                            placeholder="Select start and end date/time">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-section">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">L1</label>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter L1">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">L2</label>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter L2">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" rows="4" placeholder="Enter Description"></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Image</label>
                                                        <label class="choose-file">
                                                            Choose File
                                                            <input type="file" class="form-control d-none">
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-warning text-white"
                                            id="createBtn">Create</button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Close All Modals Button -->
                        <button id="close-all-modals" class="btn btn-danger btn-sm" style="display: none;">
                            <i class="fas fa-times"></i> Close All Modals
                        </button>
                    </div>

                    <!-- play backward forwad  -->
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

                    <!-- Controls Row -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Left Side Controls -->
                        <div class="form-inline">
                            <div class="form-group mr-2">
                                <label for="row-data-type" class="mr-2">Data Type:</label>
                                <select id="row-data-type" class="form-control form-control-sm">
                                    <option value="all">All</option>
                                    <option value="sku">SKU (Child)</option>
                                    <option value="parent">Parent</option>
                                </select>
                            </div>
                        </div>

                        <!-- Column Controls -->
                        <div>
                            <div class="form-group mr-2 custom-dropdown">
                                <button id="hideColumnsBtn" class="btn btn-sm btn-outline-secondary">
                                    Hide Columns
                                </button>
                                <div class="custom-dropdown-menu" id="columnToggleMenu">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                            <div class="form-group">
                                <button id="showAllColumns" class="btn btn-sm btn-outline-secondary">
                                    Show All
                                </button>
                            </div>
                        </div>

                        <!-- Search on Right -->
                        <div class="form-inline">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" id="search-input" class="form-control form-control-sm"
                                        placeholder="Search all columns...">
                                    <div class="input-group-append">
                                        <button id="search-button" class="btn btn-lg btn-outline-primary" type="button">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <button id="clear-search" class="btn btn-lg btn-outline-secondary" type="button"
                                            style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container">
                        <table class="custom-resizable-table" id="amazon-table">
                            <thead>
                                <tr>
                                    <th data-field="sl_no">SL No. <span class="sort-arrow">↓</span></th>
                                    <th data-field="sl_no">Image. <span class="sort-arrow">↓</span></th>
                                    <th data-field="parent" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center sortable-header">
                                                Parent <span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="dropdown-search-container position-relative">
                                                <div class="input-group">
                                                    <input type="text"
                                                        class="form-control form-control-sm parent-search"
                                                        placeholder="Search parent..." id="parentSearch" value="">
                                                    <button class="btn btn-sm btn-outline-secondary clear-parent-filter"
                                                        type="button" style="display: none;">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="dropdown-search-results" id="parentSearchResults"></div>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="child_sku" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center sortable">
                                            <div class="d-flex align-items-center">
                                                SKU <span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="dropdown-search-container position-relative">
                                                <div class="input-group">
                                                    <input type="text" class="form-control form-control-sm sku-search"
                                                        placeholder="Search SKU..." id="skuSearch" value="">
                                                    <button class="btn btn-sm btn-outline-secondary clear-sku-filter"
                                                        type="button" style="display: none;">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="dropdown-search-results" id="skuSearchResults"></div>
                                            </div>
                                        </div>
                                    </th>




                                    <th data-field="inv" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                INV <span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="metric-total" id="inv-total">0</div>
                                        </div>
                                    </th>
                                    <th data-field="l30" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                OV L30 <span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="metric-total" id="l30-total">0</div>
                                        </div>
                                    </th>
                                    <th data-field="analytics" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Analytics<span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>

                                    <th data-field="dil_pct" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Dil% <span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="metric-total" id="dil-total">0%</div>
                                        </div>
                                    </th>

                                    <th data-field="msrp" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                MSRP<span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="metric-total" id="inv-total">0</div>
                                        </div>
                                    </th>


                                    <th data-field="mapp" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                MAP<span class="sort-arrow">↓</span>
                                            </div>
                                            <div class="metric-total" id="inv-total">0</div>
                                        </div>
                                    </th>
                                    <th data-field="av_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                AVG PRICE <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>

                                       <th data-field="sprice" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                SPRICE<span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>


                                    <th data-field="sprofit" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                SPROFIT<span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>


                                    <th data-field="sroi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                SROI<span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>




                                    <!--<th data-field="ttl_prf" style="vertical-align: middle; white-space: nowrap;">-->
                                    <!--    <div class="d-flex flex-column align-items-center">-->
                                    <!--        <div class="d-flex align-items-center">-->
                                    <!--            TOTAL PROFIT-->
                                    <!--            <span class="sort-arrow">↓</span>-->
                                    <!--        </div>-->
                                    <!--    </div>-->
                                    <!--</th>-->
                                    <th data-field="av_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                AVG PFT%
                                                <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="av_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                AVG ROI %<span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>






                                    <th data-field="amz_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                AMZ-P <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="amz_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                AMZ-PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="amz_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                AMZ-ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="ebay_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay-P <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="ebay_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay-PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="ebay_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay-ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="shopifyb2c_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                ShopifyB2C-P <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="shopifyb2c_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                ShopifyB2C-PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="shopifyb2c_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                ShopifyB2C-ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="Macy_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Macy-P <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="Macy_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Macy-PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="Macy_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Macy-ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    {{-- <th data-field="Macy_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                NeweeggB2C-P <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="Macy_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                NeweeggB2C-PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="Macy_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                NeweeggB2C-ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th> --}}
                                    <th data-field="reverb_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Reverb Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="reverb_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Reverb PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="reverb_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Reverb ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>

                                    <th data-field="doba_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Doba Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="doba_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Doba PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="doba_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Doba ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>



                                 

                                    <th data-field="temu_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Temu Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="temu_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Temu PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="temu_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Temu ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>

                                    <th data-field="wayfair_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Wayfair Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="wayfair_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Wayfair PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>

                                    </th>
                                    <th data-field="wayfair_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Wayfair ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>


                                    <th data-field="ebay3_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay3 Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="ebay3_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay3 PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>

                                    </th>
                                    <th data-field="ebay3_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay3 ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>

                                    <th data-field="ebay2_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay2 Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>

                                    </th>

                                    <th data-field="ebay2_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay2 PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="ebay2_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Ebay2 ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>




                                    <th data-field="walmart_p" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Walmart Price <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="walmart_pft" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Walmart PFT <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th data-field="walmart_roi" style="vertical-align: middle; white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="d-flex align-items-center">
                                                Walmart ROI <span class="sort-arrow">↓</span>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="pagination-controls mt-2 d-flex justify-content-between align-items-center">
                        <div class="form-inline">
                            <div class="form-group mr-3">
                                <label for="rows-per-page" class="mr-2">Rows per page:</label>
                                <select id="rows-per-page" class="form-control form-control-sm">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="250">250</option>
                                    <option value="500">500</option>
                                    <option value="all">All</option> <!-- New option -->
                                </select>
                            </div>
                            <span id="visible-rows" class="badge badge-light" style="color: #dc3545;">Showing 1-25 of
                                150</span>
                        </div>

                        <div>
                            <button id="first-page" class="btn btn-sm btn-outline-secondary mr-1">First</button>
                            <button id="prev-page" class="btn btn-sm btn-outline-secondary mr-1">Previous</button>
                            <span id="page-info" class="mx-2">Page 1 of 6</span>
                            <button id="next-page" class="btn btn-sm btn-outline-secondary ml-1">Next</button>
                            <button id="last-page" class="btn btn-sm btn-outline-secondary ml-1">Last</button>
                        </div>
                    </div>

                    <div id="data-loader" class="card-loader-overlay" style="display: none;">
                        <div class="loader-content">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loader-text">Loading Pricing masters data...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="l30Modal" tabindex="-1" aria-labelledby="l30ModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="l30ModalLabel">OV L30 Breakdown</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="l30ModalBody">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="siteAnalysisModal" tabindex="-1" aria-labelledby="siteAnalysisModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Site Analysis: Price, L30, ROI%, PFT%</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <canvas id="siteAnalysisChart" height="120"></canvas>
                        <hr />
                        <div id="siteSummaryTableContainer" class="table-responsive mt-3">
                            <table class="table table-bordered table-sm text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Site</th>
                                        <th>Price ($)</th>
                                        <th>L30</th>
                                        <th>ROI %</th>
                                        <th>PFT %</th>
                                    </tr>
                                </thead>
                                <tbody id="siteSummaryTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>








        <div class="modal fade" id="profitModal" tabindex="-1" aria-labelledby="profitModalLabel" aria-hidden="true">
            <div class="modal-dialog  modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="profitModalLabel">Channel Price & Profit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="profitModalBody">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="roiModal" tabindex="-1" aria-labelledby="roiModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="roiModalLabel">Channel Price & ROI</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="roiModalBody">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.body.style.zoom = "85%";
        $(document).ready(function() {
            // Add global filter variables
            let currentParentFilter = '';
            let currentSkuFilter = '';
            let isInitialLoad = true;

            // Initialize the modal
            const createTaskModal = new bootstrap.Modal(document.getElementById('createTaskModal'));

            // Handle create task button click
            $('#createTaskBtn').on('click', function() {
                createTaskModal.show();
            });

            // Handle create button inside modal
            $('#createBtn').on('click', function() {
                const form = document.getElementById("taskForm");
                const title = form.querySelector('input[placeholder="Enter Title"]').value.trim();
                const assignor = form.querySelectorAll('select')[0].value;
                const assignee = form.querySelectorAll('select')[2].value;
                const duration = form.querySelector('#duration').value;

                if (!title || assignor === "Select Assignor" || assignee === "Please Select" || !duration) {
                    alert("Please fill in all required fields marked with *");
                    return;
                }

                alert("🎉 Task Created Successfully!");
                form.reset();
                createTaskModal.hide();
            });

            // Filter state
            const state = {
                filters: {
                    'Dil%': 'all',
                }
            };

            // Current state
            let currentPage = 1;
            let rowsPerPage = 10000;
            let currentSort = {
                field: null,
                direction: 1
            };
            let tableData = [];
            let paginationInfo = {};
            let isResizing = false;
            let isLoading = false;
            let dilFilter = 'all';

            //
            let filteredData = [];
            let isNavigationActive = false;
            let currentParentIndex = -1;
            let uniqueParents = [];



            function initPlaybackControls() {

                console.log("initPlaybackControls called"); // ADD THIS
                // Get all unique parent ASINs
                uniqueParents = [...new Set(tableData.map(item => item.Parent))];

                // Set up event handlers
                $('#play-forward').click(nextParent);
                $('#play-backward').click(previousParent);
                $('#play-pause').click(stopNavigation);
                $('#play-auto').click(startNavigation);

                // Initialize button states
                updateButtonStates();
            }

            function startNavigation() {
                console.log("🔥 startNavigation called"); // Make sure this stands out

                if (uniqueParents.length === 0) {
                    console.warn("No parents found!");
                    return;
                }

                isNavigationActive = true;
                currentParentIndex = 0;

                $('th[data-field="r&a"], td:nth-child(4)').removeClass('hide-column');

                showCurrentParent();

                $('#play-auto').hide();
                $('#play-pause').show().removeClass('btn-light');

                checkParentRAStatus();
            }


            function stopNavigation() {
                isNavigationActive = false;
                currentParentIndex = -1;

                // Hide R&A column
                $('th[data-field="r&a"], td:nth-child(4)').addClass('hide-column');

                // Update button visibility and reset color
                $('#play-pause').hide();
                $('#play-auto').show()
                    .removeClass('btn-success btn-warning btn-danger')
                    .addClass('btn-light');

                // Show all products
                filteredData = [...tableData];
                currentPage = 1;
                renderTable();
                calculateTotals();
                console.log("stopNavigation" + filteredData);
            }

            function nextParent() {
                if (!isNavigationActive) return;
                if (currentParentIndex >= uniqueParents.length - 1) return;

                currentParentIndex++;
                showCurrentParent();

                console.log("Next btn click");
            }

            function previousParent() {
                if (!isNavigationActive) return;
                if (currentParentIndex <= 0) return;

                currentParentIndex--;
                showCurrentParent();
                console.log("previousParent");
            }

            function showCurrentParent() {
                if (!isNavigationActive || currentParentIndex === -1) return;

                const currentParent = uniqueParents[currentParentIndex];
                console.log("🔄 Showing parent:", currentParent);

                // Filter data for current parent
                filteredData = tableData.filter(item => item.Parent === currentParent);

                // Sort to move "PARENT" rows to the bottom
                filteredData.sort((a, b) => {
                    const isAParentRow = a.SKU?.toUpperCase().includes('PARENT');
                    const isBParentRow = b.SKU?.toUpperCase().includes('PARENT');
                    if (isAParentRow && !isBParentRow) return 1;
                    if (!isAParentRow && isBParentRow) return -1;
                    return 0;
                });

                currentPage = 1;
                renderTable();
                calculateTotals();
                updateButtonStates();
                checkParentRAStatus();

                // Scroll to bottom after DOM is updated
                setTimeout(() => {
                    const $lastRow = $('#amazon-table tbody tr:last-child');
                    if ($lastRow.length > 0) {
                        $lastRow[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'end'
                        });
                    }
                }, 150);
            }



            function updateButtonStates() {
                // Enable/disable navigation buttons based on position
                $('#play-backward').prop('disabled', !isNavigationActive || currentParentIndex <= 0);
                $('#play-forward').prop('disabled', !isNavigationActive || currentParentIndex >= uniqueParents
                    .length - 1);

                // Update button tooltips
                $('#play-auto').attr('title', isNavigationActive ? 'Show all products' : 'Start parent navigation');
                $('#play-pause').attr('title', 'Stop navigation and show all');
                $('#play-forward').attr('title', isNavigationActive ? 'Next parent' : 'Start navigation first');
                $('#play-backward').attr('title', isNavigationActive ? 'Previous parent' :
                    'Start navigation first');

                // Update button colors based on state
                if (isNavigationActive) {
                    $('#play-forward, #play-backward').removeClass('btn-light').addClass('btn-primary');
                } else {
                    $('#play-forward, #play-backward').removeClass('btn-primary').addClass('btn-light');
                }
            }

            function checkParentRAStatus() {
                if (!isNavigationActive || currentParentIndex === -1) return;

                const currentParent = uniqueParents[currentParentIndex];
                const parentRows = tableData.filter(item => item.Parent === currentParent);

                if (parentRows.length === 0) return;

                let checkedCount = 0;
                let rowsWithRAData = 0;

                parentRows.forEach(row => {
                    // Only count rows that have R&A data (not undefined/null/empty)
                    if (row['R&A'] !== undefined && row['R&A'] !== null && row['R&A'] !== '') {
                        rowsWithRAData++;
                        if (row['R&A'] === true || row['R&A'] === 'true' || row['R&A'] === '1') {
                            checkedCount++;
                        }
                    }
                });

                // Determine which button is currently visible
                const $activeButton = $('#play-pause').is(':visible') ? $('#play-pause') : $('#play-auto');

                // Remove all state classes first
                $activeButton.removeClass('btn-success btn-warning btn-danger btn-light');

                if (rowsWithRAData === 0) {
                    // No rows with R&A data at all (all empty)
                    $activeButton.addClass('btn-light');
                } else if (checkedCount === rowsWithRAData) {
                    // All rows with R&A data are checked (green)
                    $activeButton.addClass('btn-success');
                } else if (checkedCount > 0) {
                    // Some rows with R&A data are checked (yellow)
                    $activeButton.addClass('btn-warning');
                } else {
                    // No rows with R&A data are checked (red)
                    $activeButton.addClass('btn-danger');
                }
            }

            // Initialize everything
            function initTable() {
                loadData().then(() => {
                    // Hide R&A column initially
                    $('th[data-field="r&a"], td:nth-child(4)').addClass('hide-column');

                    renderTable();
                    initResizableColumns();
                    initSorting();
                    initPagination();
                    initSearch();
                    initColumnToggle();
                    initFilters();
                    initManualDropdowns();
                    initPlaybackControls();
                    // Load distinct values after initial render
                    loadDistinctValues();
                });
            }

            function loadDistinctValues() {
                $.ajax({
                    url: '/pricing-analysis-data-view',
                    type: 'GET',
                    data: {
                        distinct_only: true,
                        dil_filter: dilFilter,
                        data_type: $('#row-data-type').val(),
                        search: $('#search-input').val().trim(),
                        parent: currentParentFilter,
                        sku: currentSkuFilter
                    },
                    success: function(response) {
                        if (response) {
                            window.distinctParents = response.distinct_values?.parents || [];
                            window.distinctSkus = response.distinct_values?.skus || [];
                            initEnhancedDropdowns();
                        }
                    }
                });
            }

            function loadData() {
                showLoader();
                return $.ajax({
                    url: '/pricing-analysis-data-view',
                    type: 'GET',
                    data: {
                        page: currentPage,
                        per_page: rowsPerPage,
                        dil_filter: dilFilter,
                        data_type: $('#row-data-type').val(),
                        search: $('#search-input').val().trim(),
                        parent: currentParentFilter,
                        sku: currentSkuFilter
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response) {
                            tableData = response.data || [];
                            paginationInfo = response.pagination || {};
                            renderTable(); // ✅ Table updates here
                            filteredData = [...tableData];
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading data:', error);
                        showNotification('danger', 'Failed to load data. Please try again.');
                    },
                    complete: function() {
                        hideLoader();
                        if (paginationInfo) {
                            updatePaginationButtons();
                        }
                    }
                });
            }



            // Render table with current data
            function renderTable() {
                const $tbody = $('#amazon-table tbody');
                $tbody.empty();

                console.log("🧾 Rendering", filteredData.length, "rows");

                if (filteredData.length === 0) {
                    console.log("⚠️ No matching records to display.");
                    $tbody.append('<tr><td colspan="15" class="text-center">No matching records found</td></tr>');
                    return;
                }

                function safeDisplay(value, defaultValue = '0', decimals = 2) {
                    if (value === null || value === undefined) return defaultValue;
                    const num = parseFloat(value);
                    return !isNaN(num) ? num.toFixed(decimals) : defaultValue;
                }

                function safeDisplayPercent(value, defaultValue = '0') {
                    if (value === null || value === undefined) return defaultValue;
                    const num = parseFloat(value);
                    return !isNaN(num) ? Math.round(num) + '%' : defaultValue;
                }

                const getDilColor = (value) => {
                    const percent = parseFloat(value) * 100;
                    if (percent < 16.66) return 'red';
                    if (percent >= 16.66 && percent < 25) return 'yellow';
                    if (percent >= 25 && percent < 50) return 'green';
                    return 'pink';
                };

                filteredData.forEach((item, index) => {
                    const $row = $('<tr>');
                    const slNo = ((currentPage - 1) * rowsPerPage) + index + 1;
                    if (item.is_parent) $row.addClass('parent-row');

                    $row.append($('<td>').text(slNo));
                    $row.append($('<td>').html(`
    <div class="image-wrapper">
        <img 
            src="${item.shopifyb2c_image || 'https://skala.or.id/wp-content/uploads/2024/01/dummy-post-square-1-1.jpg'}" 
            alt="Product Image" 
            class="zoom-image"
        >
    </div>
`));

                    $row.append($('<td>').text(item.Parent || ''));
                    $row.append($('<td>').text(item['SKU'] || ''));



                    const prices = {
                        Amazon: parseFloat(item.amz_price) || Infinity,
                        eBay: parseFloat(item.ebay_price) || Infinity,
                        Shopify: parseFloat(item.shopifyb2c_price) || Infinity,
                        Macy: parseFloat(item.macy_price) || Infinity,
                        // Newegg: parseFloat(item.neweegb2c_price) || Infinity,
                        Reverb: parseFloat(item.reverb_price) || Infinity,
                        doba: parseFloat(item.doba_price) || Infinity,
                        temu: parseFloat(item.temu_price) || Infinity,
                        wayfair: parseFloat(item.wayfair_price) || Infinity,
                        ebay3: parseFloat(item.ebay3_price) || Infinity,
                        ebay2: parseFloat(item.ebay2_price) || Infinity,
                        walmart: parseFloat(item.walmart_price) || Infinity
                    };


                    // Find the channel with the minimum price
                    let minChannel = '';
                    let minPrice = Infinity;

                    for (const [channel, price] of Object.entries(prices)) {
                        if (price < minPrice) {
                            minPrice = price;
                            minChannel = channel;
                        }
                    }

                    // If a valid minimum price was found, display it in MAP column



                    $row.append($('<td>').text(item.INV || 0));


                    // $row.append($('<td>').text(item.L30 || 0));
                    $row.append($('<td>').html(`
                    ${item.L30 || 0}
                    ${
                        !item.is_parent
                            ? `<i class="fa fa-eye text-primary ms-2"  style="cursor: pointer;" data-inv="${item.INV || 0}" 
                                                                                            data-l30='${JSON.stringify({
                                                                                                Amazon: {
                                                                                                    price: item.amz_price,
                                                                                                    l30: item.amz_l30,
                                                                                                    pft: item.amz_pft,
                                                                                                    roi: item.amz_roi
                                                                                                },
                                                                                                eBay: {
                                                                                                    price: item.ebay_price,
                                                                                                    l30: item.ebay_l30,
                                                                                                    pft: item.ebay_pft,
                                                                                                    roi: item.ebay_roi
                                                                                                },
                                                                                                Shopify: {
                                                                                                    price: item.shopifyb2c_price,
                                                                                                    l30: item.shopifyb2c_l30,
                                                                                                    pft: item.shopifyb2c_pft,
                                                                                                    roi: item.shopifyb2c_roi
                                                                                                },
                                                                                                Macy: {
                                                                                                    price: item.macy_price,
                                                                                                    l30: item.macy_l30,
                                                                                                    pft: item.macy_pft,
                                                                                                    roi: item.macy_roi
                                                                                                },
                                                                                                // Newegg: {
                                                                                                //     price: item.neweegb2c_price,
                                                                                                //     l30: item.neweegb2c_l30,
                                                                                                //     pft: item.neweegb2c_pft,
                                                                                                //     roi: item.neweegb2c_roi
                                                                                                // },
                                                                                                Reverb: {
                                                                                                    price: item.reverb_price,
                                                                                                    l30: item.reverb_l30,
                                                                                                    pft: item.reverb_pft,
                                                                                                    roi: item.reverb_roi
                                                                                                }
                                                                                                ,
                                                                                                doba: {
                                                                                                    price: item.doba_price,
                                                                                                    l30: item.doba_l30,
                                                                                                    pft: item.doba_pft,
                                                                                                    roi: item.doba_roi
                                                                                                }
                                                                                                ,
                                                                                                temu: {
                                                                                                    price: item.temu_price,
                                                                                                    l30: item.temu_l30,
                                                                                                    pft: item.temu_pft,
                                                                                                    roi: item.temu_roi
                                                                                                }
                                                                                                ,
                                                                                                wayfair: {
                                                                                                    price: item.wayfair_price,
                                                                                                    l30: item.wayfair_l30,
                                                                                                    pft: item.wayfair_pft,
                                                                                                    roi: item.wayfair_roi
                                                                                                }
                                                                                                ,
                                                                                                ebay3: {
                                                                                                    price: item.ebay3_price,
                                                                                                    l30: item.ebay3_l30,
                                                                                                    pft: item.ebay3_pft,
                                                                                                    roi: item.ebay3_roi
                                                                                                }
                                                                                                ,
                                                                                                ebay2: {
                                                                                                    price: item.ebay2_price,
                                                                                                    l30: item.ebay2_l30,
                                                                                                    pft: item.ebay2_pft,
                                                                                                    roi: item.ebay2_roi
                                                                                                }
                                                                                                ,
                                                                                                walmart: {
                                                                                                    price: item.walmart_price,
                                                                                                    l30: item.walmart_l30,
                                                                                                    pft: item.walmart_pft,
                                                                                                    roi: item.walmart_roi
                                                                                                }

                                                                                            })}'
                                                                                            onclick="showL30Modal(this)">
                                                                                            </i>`
                                                : ''
                                        }
                                    `));




                    $row.append($('<td>').html(`
                                        ${item.is_parent ? '--' : `
                                                                                    <button class="btn btn-outline-success btn-sm" 
                                                                                            style="padding: 2px 6px;" 
                                                                                            data-analysis='${JSON.stringify({
                                                                                                Amazon: {
                                                                                                    price: item.amz_price,
                                                                                                    l30: item.amz_l30,
                                                                                                    pft: item.amz_pft,
                                                                                                    roi: item.amz_roi
                                                                                                },
                                                                                                eBay: {
                                                                                                    price: item.ebay_price,
                                                                                                    l30: item.ebay_l30,
                                                                                                    pft: item.ebay_pft,
                                                                                                    roi: item.ebay_roi
                                                                                                },
                                                                                                Shopify: {
                                                                                                    price: item.shopifyb2c_price,
                                                                                                    l30: item.shopifyb2c_l30,
                                                                                                    pft: item.shopifyb2c_pft,
                                                                                                    roi: item.shopifyb2c_roi
                                                                                                },
                                                                                                Macy: {
                                                                                                    price: item.macy_price,
                                                                                                    l30: item.macy_l30,
                                                                                                    pft: item.macy_pft,
                                                                                                    roi: item.macy_roi
                                                                                                },
                                                                                                // Newegg: {
                                                                                                //     price: item.neweegb2c_price,
                                                                                                //     l30: item.neweegb2c_l30,
                                                                                                //     pft: item.neweegb2c_pft,
                                                                                                //     roi: item.neweegb2c_roi
                                                                                                // },
                                                                                                Reverb: {
                                                                                                    price: item.reverb_price,
                                                                                                    l30: item.reverb_l30,
                                                                                                    pft: item.reverb_pft,
                                                                                                    roi: item.reverb_roi
                                                                                                },
                                                                                                Doba: {
                                                                                                    price: item.doba_price,
                                                                                                    l30: item.doba_l30,
                                                                                                    pft: item.doba_pft,
                                                                                                    roi: item.doba_roi
                                                                                                },
                                                                                                Temu: {
                                                                                                    price: item.temu_price,
                                                                                                    l30: item.temu_l30,
                                                                                                    pft: item.temu_pft,
                                                                                                    roi: item.temu_roi
                                                                                                }
                                                                                                , Wayfair: {
                                                                                                    price: item.wayfair_price,
                                                                                                    l30: item.wayfair_l30,
                                                                                                    pft: item.wayfair_pft,
                                                                                                    roi: item.wayfair_roi
                                                                                                },
                                                                                                ebay3: {
                                                                                                    price: item.ebay3_price,
                                                                                                    l30: item.ebay3_l30,
                                                                                                    pft: item.ebay3_pft,
                                                                                                    roi: item.ebay3_roi
                                                                                                }
                                                                                                , ebay2: {
                                                                                                    price: item.ebay2_price,
                                                                                                    l30: item.ebay2_l30,
                                                                                                    pft: item.ebay2_pft,
                                                                                                    roi: item.ebay2_roi
                                                                                                },
                                                                                                walmart: {
                                                                                                    price: item.walmart_price,
                                                                                                    l30: item.walmart_l30,
                                                                                                    pft: item.walmart_pft,
                                                                                                    roi: item.walmart_roi
                                                                                                }
                                                                                            })}'
                                                                                            onclick="openSiteAnalysisModal(this)">
                                                                                        <i class="fa fa-bar-chart"></i>
                                                                                    </button>
                                                                                `}
                                    `));



                    // Total Dil Percent 
                    const dilValue = item['Dil%'];

                    if (item.is_parent) {
                        $row.append($('<td>').html('---'));
                    } else {
                        $row.append($('<td>').html(
                            dilValue && dilValue > 0 ?
                            `<span class="dil-percent-value ${getDilColor(dilValue)}">${Math.round(dilValue * 100)}%</span>` :
                            '---'
                        ));
                    }



                    // MSRP PRICE
                    $row.append($('<td>').text(
                        item.is_parent ?
                        '---' :
                        (item.MSRP !== null && item.MSRP !== undefined ? parseInt(item.MSRP) : '0')
                    ));

                    // MAP PRICE
                    const mapValue = (minPrice !== Infinity) ? `${minPrice.toFixed(2)}` : '0';
                    $row.append($('<td>').text(item.is_parent ? '---' : mapValue));


                    // Average Price
                    function formatPercentage(value) {
                        const num = parseFloat(value);
                        return value !== null && !isNaN(num) ? Math.round(num * 100) + '%' : '--';
                    }


                    const amzPrice = item.amz_price !== null ? '$' + parseFloat(item.amz_price).toFixed(2) :
                        '0';
                    const amzBuyerLink = item.amz_buy_link || '';




                    const amzL30 = item.amz_l30 || 0;
                    const totalInv = item.INV || 0;
                    const amzDil = totalInv > 0 ? (amzL30 / totalInv).toFixed(2) : '0.00';



                    const amzPriceVal = parseFloat(item.amz_price) || 0;
                    const amzL30Val = parseFloat(item.amz_l30) || 0;
                    const ebayPriceVal = parseFloat(item.ebay_price) || 0;
                    const ebayL30Val = parseFloat(item.ebay_l30) || 0;
                    const shopifyPriceVal = parseFloat(item.shopifyb2c_price) || 0;
                    const shopifyL30Val = parseFloat(item.shopiyb2c_l30) || 0;
                    const macyPriceVal = parseFloat(item.macy_price) || 0;
                    const macyL30Val = parseFloat(item.macy_l30) || 0;
                    const reverbPriceVal = parseFloat(item.reverb_price) || 0;
                    const reverbL30Val = parseFloat(item.reverb_l30) || 0;
                    // const neweggPriceVal = parseFloat(item.neweegb2c_price) || 0;
                    // const neweggL30Val = parseFloat(item.neweegb2c_l30) || 0;
                    const dobaPriceVal = parseFloat(item.doba_price) || 0;
                    const dobaL30Val = parseFloat(item.doba_l30) || 0;
                    const temuPriceVal = parseFloat(item.temu_price) || 0;
                    const temuL30Val = parseFloat(item.temu_l30) || 0;
                    const wayfairPriceVal = parseFloat(item.wayfair_price) || 0;
                    const wayfairL30Val = parseFloat(item.wayfair_l30) || 0;
                    const ebay3PriceVal = parseFloat(item.ebay3_price) || 0;
                    const ebay3L30Val = parseFloat(item.ebay3_l30) || 0;
                    const ebay2PriceVal = parseFloat(item.ebay2_price) || 0;
                    const ebay2L30Val = parseFloat(item.ebay2_l30) || 0;
                    const walmartPriceVal = parseFloat(item.walmart_price) || 0;
                    const walmartL30Val = parseFloat(item.walmart_l30) || 0;

                    const totalWeightedPrice =
                        (amzPriceVal * amzL30Val) +
                        (ebayPriceVal * ebayL30Val) +
                        (shopifyPriceVal * shopifyL30Val) +
                        (macyPriceVal * macyL30Val) +
                        // (neweggPriceVal * neweggL30Val) +
                        (reverbPriceVal * reverbL30Val) +
                        (dobaPriceVal * dobaL30Val) +
                        (temuPriceVal * temuL30Val) +
                        (wayfairPriceVal * wayfairL30Val) +
                        (ebay3PriceVal * ebay3L30Val) +
                        (ebay2PriceVal * ebay2L30Val) + // ✅ Add eBay2
                        (walmartPriceVal * walmartL30Val); // ✅ Add Walmart

                    const totalL30 =
                        amzL30Val + ebayL30Val + shopifyL30Val + macyL30Val  +
                        reverbL30Val + dobaL30Val + temuL30Val + wayfairL30Val + ebay3L30Val +
                        ebay2L30Val + walmartL30Val; // ✅ Add Walmart

                    const LP = parseFloat(item.LP) || 0;
                    const SHIP = parseFloat(item.SHIP) || 0;


                    const avgPrice = totalL30 > 0 ?
                        parseFloat(totalWeightedPrice / totalL30).toFixed(2) : '---';

                    const avgPriceValue = parseFloat(avgPrice);
                    let avgPriceBgColor = '';

                    // Set background color based on value
                    if (avgPriceValue < 10) {
                        avgPriceBgColor = '#dc3545'; // red
                    } else if (avgPriceValue >= 10 && avgPriceValue < 15) {
                        avgPriceBgColor = '#fd7e14'; // orange
                    } else if (avgPriceValue >= 15 && avgPriceValue < 20) {
                        avgPriceBgColor = '#0d6efd'; // blue
                    } else if (avgPriceValue >= 20) {
                        avgPriceBgColor = '#198754'; // green
                    }

                    // Final rendering logic
                    if (item.is_parent || avgPrice === null || avgPrice === '' || isNaN(avgPriceValue)) {
                        $row.append($('<td>').html('--'));
                    } else {
                        $row.append($('<td>').html(`
                            <strong style="background-color:${avgPriceBgColor}; color:white; padding:2px 6px; border-radius:4px;">
                                $${avgPrice}
                            </strong>
                        `));
                    }



                    // Sale Price with edit icon (skip edit for parent)
                    // S-Price
                    $row.append($('<td>').attr('id', `sprice-${item.SKU}`).html(
                        item.is_parent ?
                        `--` :
                        (
                            (item.sprice !== null && !isNaN(item.sprice)) ?
                            `<span class="badge bg-primary">
                    $${Math.round(item.sprice)}
                    </span>
                    <i class="fa fa-edit text-primary ms-2" style="cursor:pointer;" 
                        onclick='openPricingModal(${JSON.stringify({ LP: item.LP, SHIP: item.SHIP, SKU: item.SKU })})'></i>` :
                            `<i class="fa fa-edit text-primary" style="cursor:pointer;" 
                        onclick='openPricingModal(${JSON.stringify({ LP: item.LP, SHIP: item.SHIP, SKU: item.SKU })})'></i>`
                        )
                    ));

                    // S-Profit Percent
                    $row.append($('<td>').attr('id', `spft-${item.SKU}`).html(
                        item.is_parent ?
                        '--' :
                        (!isNaN(item.sprofit_percent) && item.sprofit_percent !== null ?
                            `<span class="badge bg-success">${Math.round(item.sprofit_percent)}%</span>` :
                            `--`)
                    ));

                    // S-ROI Percent
                    $row.append($('<td>').attr('id', `sroi-${item.SKU}`).html(
                        item.is_parent ?
                        '--' :
                        (!isNaN(item.sroi_percent) && item.sroi_percent !== null ?
                            `<span class="badge bg-info">${Math.round(item.sroi_percent)}%</span>` :
                            `--`)
                    ));



                    // Site-wise profit per unit × L30 = total profit per site
                    const amzProfit = ((amzPriceVal * 0.71) - LP - SHIP) * amzL30Val;
                    const ebayProfit = ((ebayPriceVal * 0.77) - LP - SHIP) * ebayL30Val;
                    const shopifyProfit = ((shopifyPriceVal * 0.75) - LP - SHIP) * shopifyL30Val;
                    const macyProfit = ((macyPriceVal * 0.77) - LP - SHIP) * macyL30Val;
                    const reverbProfit = ((reverbPriceVal * 0.84) - LP - SHIP) * reverbL30Val;
                    const dobaProfit = ((dobaPriceVal * 0.95) - LP - SHIP) * dobaL30Val;
                    // const neweggProfit = ((neweggPriceVal * 0.72) - LP - SHIP) * neweggL30Val;
                    const temuProfit = ((temuPriceVal * 0.95) - LP - SHIP) * temuL30Val;
                    const wayfairProfit = ((wayfairPriceVal * 0.90) - LP - SHIP) * wayfairL30Val;
                    const ebay3Profit = ((ebay3PriceVal * 0.76) - LP - SHIP) * ebay3L30Val;
                    const ebay2Profit = ((ebay2PriceVal * 0.88) - LP - SHIP) * ebay2L30Val;
                    const walmartProfit = ((walmartPriceVal * 0.70) - LP - SHIP) * walmartL30Val;

                    // ✅ Now correct totalProfit (includes L30 × per-unit profit)
                    const totalProfit = amzProfit + ebayProfit + shopifyProfit + macyProfit + reverbProfit +
                        dobaProfit  + temuProfit + wayfairProfit + ebay3Profit +
                        ebay2Profit + walmartProfit;


                    // $row.append($('<td>').html(`<strong>${totalProfit.toFixed(2)}</strong>`));

                    const totalRevenueUsedForPft =
                        (amzPriceVal * amzL30Val) +
                        (ebayPriceVal * ebayL30Val) +
                        (shopifyPriceVal * shopifyL30Val) +
                        (macyPriceVal * macyL30Val) +
                        (reverbPriceVal * reverbL30Val) +
                        (dobaPriceVal * dobaL30Val) +
                        // (neweggPriceVal * neweggL30Val) +
                        (temuPriceVal * temuL30Val) +
                        (wayfairPriceVal * wayfairL30Val) +
                        (ebay3PriceVal * ebay3L30Val) + // ✅ Add eBay3
                        (ebay2PriceVal * ebay2L30Val) + // ✅ Add eBay2
                        (walmartPriceVal * walmartL30Val); // ✅ Add Walmart

                    const avgPftPercent = totalRevenueUsedForPft > 0 ?
                        ((totalProfit / totalRevenueUsedForPft) * 100).toFixed(2) : '0.00';

                    // Profit background color logic
                    let pftBgColor = 'pink'; // default color
                    const pftValue = parseFloat(avgPftPercent);

                    if (pftValue < 10) {
                        pftBgColor = 'red';
                    } else if (pftValue >= 10 && pftValue < 15) {
                        pftBgColor = 'orange';
                    } else if (pftValue >= 15 && pftValue < 20) {
                        pftBgColor = 'blue';
                    } else if (pftValue >= 20) {
                        pftBgColor = 'green';
                    }

                    const profitData = {
                        Amazon: {
                            price: item.amz_price,
                            profit: Math.round((item.amz_pft || 0) * 100)
                        },
                        eBay: {
                            price: item.ebay_price,
                            profit: Math.round((item.ebay_pft || 0) * 100)
                        },
                        Shopify: {
                            price: item.shopifyb2c_price,
                            profit: Math.round((item.shopifyb2c_pft || 0) * 100)
                        },
                        Macy: {
                            price: item.macy_price,
                            profit: Math.round((item.macy_pft || 0) * 100)
                        },
                        // Newegg: {
                        //     price: item.neweegb2c_price,
                        //     profit: Math.round((item.neweegb2c_pft || 0) * 100)
                        // },
                        Reverb: {
                            price: item.reverb_price,
                            profit: Math.round((item.reverb_pft || 0) * 100)
                        } // ✅
                        ,
                        Doba: {
                            price: item.doba_price,
                            profit: Math.round((item.doba_pft || 0) * 100)
                        },
                        Temu: {
                            price: item.temu_price,
                            profit: Math.round((item.temu_pft || 0) * 100)
                        },
                        Wayfair: {
                            price: item.wayfair_price,
                            profit: Math.round((item.wayfair_pft || 0) * 100)
                        },

                        ebay3: {
                            price: item.ebay3_price,
                            profit: Math.round((item.ebay3_pft || 0) * 100)
                        },
                        ebay2: {
                            price: item.ebay2_price,
                            profit: Math.round((item.ebay2_pft || 0) * 100)
                        },
                        Walmart: {
                            price: item.walmart_price,
                            profit: Math.round((item.walmart_pft || 0) * 100)
                        }

                    };


                    
                    // Append with span styling
                    $row.append(
                        $('<td>').html(
                            item.is_parent ?
                            `--` :
                            `
                                    <span style="background-color:${pftBgColor}; color:white; padding:2px 6px; border-radius:4px;">
                                        <strong>${avgPftPercent}%</strong>
                                    </span>
                                    <i class="fa fa-eye text-primary ms-2" 
                                    style="cursor: pointer;" 
                                    data-profit='${JSON.stringify(profitData)}'
                                    onclick="showProfitModal(this)">
                                    </i>
                                `
                        )
                    );


                    



                    const totalL30ForROI = totalL30;
                    const avgRoiPercent = (totalL30ForROI > 0 && (LP + SHIP) > 0) ?
                        (((totalProfit / totalL30ForROI) / (LP + SHIP)) * 100).toFixed(2) : '0.00';


                    // ROI background color logic
                    let roiBgColor = 'pink'; // default
                    const roiValue = parseFloat(avgRoiPercent);
                    if (roiValue < 50) {
                        roiBgColor = 'red';
                    } else if (roiValue >= 50 && roiValue < 75) {
                        roiBgColor = 'orange';
                    } else if (roiValue >= 75 && roiValue < 100) {
                        roiBgColor = 'green';
                    }

                    // Append with span having white text and background
                    const roiData = {
                        Amazon: {
                            price: item.amz_price || 0,
                            roi: Math.round((item.amz_roi || 0) * 100)
                        },
                        eBay: {
                            price: item.ebay_price || 0,
                            roi: Math.round((item.ebay_roi || 0) * 100)
                        },
                        Shopify: {
                            price: item.shopifyb2c_price || 0,
                            roi: Math.round((item.shopifyb2c_roi || 0) * 100)
                        },
                        Macy: {
                            price: item.macy_price || 0,
                            roi: Math.round((item.macy_roi || 0) * 100)
                        },
                        // Newegg: {
                        //     price: item.neweegb2c_price || 0,
                        //     roi: Math.round((item.neweegb2c_roi || 0) * 100)
                        // },
                        Reverb: {
                            price: item.reverb_price || 0,
                            roi: Math.round((item.reverb_roi || 0) * 100)
                        } // ✅
                        ,
                        Doba: {
                            price: item.doba_price || 0,
                            roi: Math.round((item.doba_roi || 0) * 100)
                        },
                        Temu: {
                            price: item.temu_price || 0,
                            roi: Math.round((item.temu_roi || 0) * 100)
                        },
                        Wayfair: {
                            price: item.wayfair_price || 0,
                            roi: Math.round((item.wayfair_roi || 0) * 100)
                        },
                        ebay3: {
                            price: item.ebay3_price || 0,
                            roi: Math.round((item.ebay3_roi || 0) * 100)
                        },
                        ebay2: {
                            price: item.ebay2_price || 0,
                            roi: Math.round((item.ebay2_roi || 0) * 100)
                        },
                        Walmart: {
                            price: item.walmart_price || 0,
                            roi: Math.round((item.walmart_roi || 0) * 100)
                        }

                    };

                    $row.append(
                        $('<td>').html(
                            item.is_parent ?
                            `--` :
                            `
                <span style="background-color:${roiBgColor}; color:white; padding:2px 6px; border-radius:4px;">
                    <strong>${avgRoiPercent}%</strong>
                </span>
                <i class="fa fa-eye text-primary ms-2" 
                   style="cursor: pointer;" 
                   data-roi='${JSON.stringify(roiData)}'
                   onclick="showRoiModal(this)">
                </i>
            `
                        )
                    );



                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (amzBuyerLink || amzL30 || amzDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${amzPrice}</span>
                                <div class="sku-tooltip">
                                    ${amzBuyerLink ? `<div class="sku-link"><a href="${amzBuyerLink}" target="_blank" rel="noopener noreferrer">Amazon Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(amzL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(amzDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(amzPrice));
                    }



                    const getPftColor = (value) => {
                        const percent = parseFloat(value) * 100;
                        if (percent < 10) return 'red';
                        if (percent >= 10 && percent < 15) return 'yellow';
                        if (percent >= 15 && percent < 20) return 'blue';
                        if (percent >= 20 && percent <= 40) return 'green';
                        return 'pink';
                    };

                    $row.append($('<td>').html(
                        typeof item.amz_pft === 'number' && !isNaN(item.amz_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getPftColor(item.amz_pft)}">${Math.round(item.amz_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.amz_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));


                    const getRoiColor = (value) => {
                        const percent = parseFloat(value) * 100;
                        if (percent >= 0 && percent < 50) return 'red';
                        if (percent >= 50 && percent < 75) return 'yellow';
                        if (percent >= 75 && percent <= 100) return 'green';
                        return 'pink';
                    };
                    $row.append($('<td>').html(
                        typeof item.amz_roi === 'number' && !isNaN(item.amz_roi) ?
                        `<span class="dil-percent-value ${getRoiColor(item.amz_roi)}">${Math.round(item.amz_roi * 100)}%</span>` :
                        ''
                    ));


                    // eBay Price
                    const ebayPrice = item.ebay_price !== null ? '$' + parseFloat(item.ebay_price).toFixed(
                        2) : '0';
                    const ebayBuyerLink = item.ebay_buy_link || '';


                    const ebayL30 = item.ebay_l30 || 0;
                    const ebayDil = item.INV !== 0 ? (ebayL30 / item.INV).toFixed(2) : '0.00';

                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (ebayBuyerLink || ebayL30 || ebayDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${ebayPrice}</span>
                                <div class="sku-tooltip">
                                    ${ebayBuyerLink ? `<div class="sku-link"><a href="${ebayBuyerLink}" target="_blank" rel="noopener noreferrer">eBay Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(ebayL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(ebayDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(ebayPrice));
                    }

                    // eBay Metrics

                    const getebayRoiColor = (value) => {
                        const percent = parseFloat(value) * 100;
                        if (percent < 50) return 'red';
                        if (percent >= 50 && percent < 75) return 'yellow';
                        if (percent >= 75 && percent <= 125) return 'green';
                        return 'pink';
                    };

                    // eBay Metrics
                    // $row.append($('<td>').text(formatPercentage(item.ebay_pft)));

                    const getebayPftColor = (value) => {
                        const percent = parseFloat(value) * 100;
                        if (percent < 10) return 'red';
                        if (percent >= 10 && percent < 15) return 'yellow';
                        if (percent >= 15 && percent < 20) return 'blue';
                        if (percent >= 20 && percent <= 40) return 'green';
                        return 'pink';
                    };

                    $row.append($('<td>').html(
                        typeof item.ebay_pft === 'number' && !isNaN(item.ebay_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getebayPftColor(item.ebay_pft)}">${Math.round(item.ebay_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.ebay_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));


                    $row.append($('<td>').html(
                        typeof item.ebay_roi === 'number' && !isNaN(item.ebay_roi) ?
                        `<span class="dil-percent-value ${getebayRoiColor(item.ebay_roi)}">${Math.round(item.ebay_roi * 100)}%</span>` :
                        ''
                    ));


                    // Shopify Price
                    const shopifyPrice = item.shopifyb2c_price !== null ? '$' + parseFloat(item
                        .shopifyb2c_price).toFixed(2) : '0';
                    const shopifyBuyerLink = item.shopiyb2c_buy_link || '';


                    const shopifyL30 = item.shopiyb2c_l30 || 0;
                    const shopifyDil = item.INV !== 0 ? (shopifyL30 / item.INV).toFixed(2) : '0.00';

                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (shopifyBuyerLink || shopifyL30 || shopifyDil) {
                        $row.append($('<td>').html(`
                        <div class="sku-tooltip-container">
                            <span class="price-text">${shopifyPrice}</span>
                            <div class="sku-tooltip">
                                ${shopifyBuyerLink ? `<div class="sku-link"><a href="${shopifyBuyerLink}" target="_blank" rel="noopener noreferrer">Shopify Buyer Link</a></div>` : ''}
                                <div class="sku-link"><strong>L30: ${parseFloat(shopifyL30 || 0).toFixed(2)}</strong></div>
                                <div class="sku-link"><strong>DIL: ${parseFloat(shopifyDil || 0).toFixed(2)}%</strong></div>
                            </div>
                        </div>
                    `));
                    } else {
                        $row.append($('<td>').text(shopifyPrice));
                    }

                    // Shopify Metrics

                    const getshopifyPftColor = (value) => {
                        const percent = parseFloat(value) * 100;
                        if (percent < 10) return 'red';
                        if (percent >= 10 && percent < 15) return 'yellow';
                        if (percent >= 15 && percent < 20) return 'blue';
                        if (percent >= 20 && percent <= 40) return 'green';
                        return 'pink';
                    };

                    const getshopifyRoiColor = (value) => {
                        const percent = parseFloat(value) * 100;
                        if (percent >= 0 && percent < 50) return 'red';
                        if (percent >= 50 && percent < 75) return 'yellow';
                        if (percent >= 75 && percent <= 100) return 'green';
                        return 'pink';
                    };
                    // Shopify Metrics

                    $row.append($('<td>').html(
                        typeof item.shopifyb2c_pft === 'number' && !isNaN(item.shopifyb2c_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.shopifyb2c_pft)}">${Math.round(item.shopifyb2c_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.shopifyb2c_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));


                    $row.append($('<td>').html(
                        typeof item.shopifyb2c_roi === 'number' && !isNaN(item.shopifyb2c_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.shopifyb2c_roi)}">${Math.round(item.shopifyb2c_roi * 100)}%</span>` :
                        ''
                    ));


                    // Macy Price
                    const macyPrice = item.macy_price !== null ? '$' + parseFloat(item.macy_price).toFixed(
                        2) : '0';
                    const macyBuyerLink = item.macy_buy_link || '';


                    const macyL30 = item.macy_l30 || 0;
                    const macyDil = item.INV !== 0 ? (macyL30 / item.INV).toFixed(2) : '0.00';

                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (macyBuyerLink || macyL30 || macyDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${macyPrice}</span>
                                <div class="sku-tooltip">
                                    ${macyBuyerLink ? `<div class="sku-link"><a href="${macyBuyerLink}" target="_blank" rel="noopener noreferrer">Macy Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(macyL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(macyDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(macyPrice));
                    }

                    // Macy's Metrics
                    $row.append($('<td>').html(
                        typeof item.macy_pft === 'number' && !isNaN(item.macy_pft) ?
                        `<div>
                        <span class="dil-percent-value ${getshopifyPftColor(item.macy_pft)}">${Math.round(item.macy_pft * 100)}%</span>
                        <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.macy_l30 || 0).toFixed(2)})</small>
                    </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.macy_roi === 'number' && !isNaN(item.macy_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.macy_roi)}">${Math.round(item.macy_roi * 100)}%</span>` :
                        ''
                    ));

                    // Newegg Price
                    // const neweggPrice = item.neweegb2c_price !== null ? '$' + parseFloat(item
                    //     .neweegb2c_price).toFixed(2) : '0';
                    // const neweggBuyerLink = item.neweegb2c_buy_link || '';

                    // const neweggL30 = item.neweegb2c_l30 || 0;
                    // const neweggDil = item.INV !== 0 ? (neweggL30 / item.INV).toFixed(2) : '0.00';

                    // if (item.is_parent) {
                    //     $row.append($('<td>').text('--'));
                    // } else if (neweggBuyerLink || neweggL30 || neweggDil) {
                    //     $row.append($('<td>').html(`
                    //     <div class="sku-tooltip-container">
                    //         <span class="price-text">${neweggPrice}</span>
                    //         <div class="sku-tooltip">
                    //             ${neweggBuyerLink ? `<div class="sku-link"><a href="${neweggBuyerLink}" target="_blank" rel="noopener noreferrer">Newegg Buyer Link</a></div>` : ''}
                    //             <div class="sku-link"><strong>L30: ${parseFloat(neweggL30 || 0).toFixed(2)}</strong></div>
                    //             <div class="sku-link"><strong>DIL: ${parseFloat(neweggDil || 0).toFixed(2)}%</strong></div>
                    //         </div>
                    //     </div>
                    // `));
                    // } else {
                    //     $row.append($('<td>').text(neweggPrice));
                    // }


                    // $row.append($('<td>').html(
                    //     typeof item.neweegb2c_pft === 'number' && !isNaN(item.neweegb2c_pft) ?
                    //     `<div>
                    //             <span class="dil-percent-value ${getshopifyPftColor(item.neweegb2c_pft)}">
                    //                 ${Math.round(item.neweegb2c_pft * 100)}%
                    //             </span>
                    //             <small style="margin-left: 6px; color: #555;">
                    //                 (L30: ${parseFloat(item.neweegb2c_l30 || 0).toFixed(2)})
                    //             </small>
                    //         </div>` : ''
                    // ));


                    // $row.append($('<td>').html(
                    //     typeof item.neweegb2c_roi === 'number' && !isNaN(item.neweegb2c_roi) ?
                    //     `<span class="dil-percent-value ${getshopifyRoiColor(item.neweegb2c_roi)}">
                    //             ${Math.round(item.neweegb2c_roi * 100)}%
                    //         </span>` : ''
                    // ));

                    // Newegg Metrics

                    const reverbPrice = item.reverb_price !== null ? '$' + parseFloat(item.reverb_price)
                        .toFixed(2) : '0';
                    const reverbBuyerLink = item.reverb_buy_link || '';

                    const reverbL30 = item.reverb_l30 || 0;
                    const reverbDil = item.INV !== 0 ? (reverbL30 / item.INV).toFixed(2) : '0.00';

                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (reverbBuyerLink || reverbL30 || reverbDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${reverbPrice}</span>
                                <div class="sku-tooltip">
                                    ${reverbBuyerLink ? `<div class="sku-link"><a href="${reverbBuyerLink}" target="_blank" rel="noopener noreferrer">Reverb Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(reverbL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(reverbDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(reverbPrice));
                    }

                    $row.append($('<td>').html(
                        typeof item.reverb_pft === 'number' && !isNaN(item.reverb_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.reverb_pft)}">${Math.round(item.reverb_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.reverb_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.reverb_roi === 'number' && !isNaN(item.reverb_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.reverb_roi)}">${Math.round(item.reverb_roi * 100)}%</span>` :
                        ''
                    ));


                    // Doba Metrics

                    const dobaPrice = item.doba_price !== null ? '$' + parseFloat(item.doba_price).toFixed(
                        2) : '0';
                    const dobaBuyerLink = item.doba_buy_link || '';

                    const dobaL30 = item.doba_l30 || 0;
                    const dobaDil = item.INV !== 0 ? (dobaL30 / item.INV).toFixed(2) : '0.00';

                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (dobaBuyerLink || dobaL30 || dobaDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${dobaPrice}</span>
                                <div class="sku-tooltip">
                                    ${dobaBuyerLink ? `<div class="sku-link"><a href="${dobaBuyerLink}" target="_blank" rel="noopener noreferrer">Doba Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(dobaL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(dobaDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(dobaPrice));
                    }

                    $row.append($('<td>').html(
                        typeof item.doba_pft === 'number' && !isNaN(item.doba_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.doba_pft)}">${Math.round(item.doba_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.doba_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.doba_roi === 'number' && !isNaN(item.doba_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.doba_roi)}">${Math.round(item.doba_roi * 100)}%</span>` :
                        ''
                    ));






                    // Temu Metrics
                    const temuPrice = item.temu_price !== null ? '$' + parseFloat(item.temu_price).toFixed(
                        2) : '0';
                    const temuBuyerLink = item.temu_buy_link || '';
                    const temuL30 = item.temu_l30 || 0;
                    const temuDil = item.INV !== 0 ? (temuL30 / item.INV).toFixed(2) : '0.00';
                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (temuBuyerLink || temuL30 || temuDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${temuPrice}</span>
                                <div class="sku-tooltip">
                                    ${temuBuyerLink ? `<div class="sku-link"><a href="${temuBuyerLink}" target="_blank" rel="noopener noreferrer">Temu Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(temuL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(temuDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(temuPrice));
                    }

                    $row.append($('<td>').html(
                        typeof item.temu_pft === 'number' && !isNaN(item.temu_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.temu_pft)}">${Math.round(item.temu_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.temu_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));
                    $row.append($('<td>').html(
                        typeof item.temu_roi === 'number' && !isNaN(item.temu_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.temu_roi)}">${Math.round(item.temu_roi * 100)}%</span>` :
                        ''
                    ));


                    // Wayfair Metrics
                    const wayfairPrice = item.wayfair_price !== null ? '$' + parseFloat(item.wayfair_price)
                        .toFixed(2) : '0';
                    const wayfairBuyerLink = item.wayfair_buy_link || '';
                    const wayfairL30 = item.wayfair_l30 || 0;
                    const wayfairDil = item.INV !== 0 ? (wayfairL30 / item.INV).toFixed(2) : '0.00';
                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (wayfairBuyerLink || wayfairL30 || wayfairDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text">${wayfairPrice}</span>
                                <div class="sku-tooltip">
                                    ${wayfairBuyerLink ? `<div class="sku-link"><a href="${wayfairBuyerLink}" target="_blank" rel="noopener noreferrer">Wayfair Buyer Link</a></div >` : ''}        
                                    <div class="sku-link"><strong>L30: ${parseFloat(wayfairL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(wayfairDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(wayfairPrice));
                    }

                    $row.append($('<td>').html(
                        typeof item.wayfair_pft === 'number' && !isNaN(item.wayfair_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.wayfair_pft)}">${Math.round(item.wayfair_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.wayfair_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.wayfair_roi === 'number' && !isNaN(item.wayfair_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.wayfair_roi)}">${Math.round(item.wayfair_roi * 100)}%</span>` :
                        ''
                    ));

                    const ebay3BuyerLink = item.ebay3_buy_link || '';
                    const ebay3L30 = item.ebay3_l30 || 0;
                    const ebay3Dil = item.INV !== 0 ? (ebay3L30 / item.INV).toFixed(2) : '0.00';
                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (ebay3BuyerLink || ebay3L30 || ebay3Dil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text
                                    ">${item.ebay3_price !== null ? '$' + parseFloat(item.ebay3_price).toFixed(2) : '0'}</span>
                                <div class="sku-tooltip">
                                    ${ebay3BuyerLink ? `<div class="sku-link"><a href="${ebay3BuyerLink}" target="_blank" rel="noopener noreferrer">eBay3 Buyer Link</  a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(ebay3L30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(ebay3Dil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(item.ebay3_price !== null ? '$' + parseFloat(item
                            .ebay3_price).toFixed(2) : '0'));
                    }

                    $row.append($('<td>').html(
                        typeof item.ebay3_pft === 'number' && !isNaN(item.ebay3_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.ebay3_pft)}">${Math.round(item.ebay3_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.ebay3_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.ebay3_roi === 'number' && !isNaN(item.ebay3_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.ebay3_roi)}">${Math.round(item.ebay3_roi * 100)}%</span>` :
                        ''
                    ));


                    const ebay2BuyerLink = item.ebay2_buy_link || '';
                    const ebay2L30 = item.ebay2_l30 || 0;
                    const ebay2Dil = item.INV !== 0 ? (ebay2L30 / item.INV).toFixed(2) : '0.00';
                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (ebay2BuyerLink || ebay2L30 || ebay2Dil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text
                                    ">${item.ebay2_price !== null ? '$' + parseFloat(item.ebay2_price).toFixed(2) : '0'}</span>
                                <div class="sku-tooltip">
                                    ${ebay2BuyerLink ? `<div class="sku-link"><a href="${ebay2BuyerLink}" target="_blank" rel="noopener noreferrer">eBay2 Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(ebay2L30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(ebay2Dil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(item.ebay2_price !== null ? '$' + parseFloat(item
                            .ebay2_price).toFixed(2) : '0'));
                    }

                    $row.append($('<td>').html(
                        typeof item.ebay2_pft === 'number' && !isNaN(item.ebay2_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.ebay2_pft)}">${Math.round(item.ebay2_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.ebay2_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.ebay2_roi === 'number' && !isNaN(item.ebay2_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.ebay2_roi)}">${Math.round(item.ebay2_roi * 100)}%</span>` :
                        ''
                    ));


                    const walmartBuyerLink = item.walmart_buy_link || '';
                    const walmartL30 = item.walmart_l30 || 0;
                    const walmartDil = item.INV !== 0 ? (walmartL30 / item.INV).toFixed(2) : '0.00';
                    if (item.is_parent) {
                        $row.append($('<td>').text('--'));
                    } else if (walmartBuyerLink || walmartL30 || walmartDil) {
                        $row.append($('<td>').html(`
                            <div class="sku-tooltip-container">
                                <span class="price-text
                                    ">${item.walmart_price !== null ? '$' + parseFloat(item.walmart_price).toFixed(2) : '0'}</span>
                                <div class="sku-tooltip">
                                    ${walmartBuyerLink ? `<div class="sku-link"><a href="${walmartBuyerLink}" target="_blank" rel="noopener noreferrer">Walmart Buyer Link</a></div>` : ''}
                                    <div class="sku-link"><strong>L30: ${parseFloat(walmartL30 || 0).toFixed(2)}</strong></div>
                                    <div class="sku-link"><strong>DIL: ${parseFloat(walmartDil || 0).toFixed(2)}%</strong></div>
                                </div>
                            </div>
                        `));
                    } else {
                        $row.append($('<td>').text(item.walmart_price !== null ? '$' + parseFloat(item
                            .walmart_price).toFixed(2) : '0'));
                    }

                    $row.append($('<td>').html(
                        typeof item.walmart_pft === 'number' && !isNaN(item.walmart_pft) ?
                        `<div>
                            <span class="dil-percent-value ${getshopifyPftColor(item.walmart_pft)}">${Math.round(item.walmart_pft * 100)}%</span>
                            <small style="margin-left: 6px; color: #555;">(L30: ${parseFloat(item.walmart_l30 || 0).toFixed(2)})</small>
                        </div>` : ''
                    ));

                    $row.append($('<td>').html(
                        typeof item.walmart_roi === 'number' && !isNaN(item.walmart_roi) ?
                        `<span class="dil-percent-value ${getshopifyRoiColor(item.walmart_roi)}">${Math.round(item.walmart_roi * 100)}%</span>` :
                        ''
                    ));


                    $tbody.append($row);

                });

                updatePaginationInfo();
                initTooltips();
                updatePaginationButtons();
                calculateTotals();

                console.log("✅ Table rendered successfully.");
            }


            // Update pagination information
            function updatePaginationInfo() {
                if (paginationInfo) {
                    const start = ((paginationInfo.current_page - 1) * paginationInfo.per_page) + 1;
                    const end = Math.min(
                        paginationInfo.current_page * paginationInfo.per_page,
                        paginationInfo.total
                    );

                    let rowsText;
                    if ($('#rows-per-page').val() === 'all') {
                        rowsText = `Showing all ${paginationInfo.total} rows`;
                    } else {
                        rowsText = `Showing ${start} to ${end} of ${paginationInfo.total} rows`;
                    }

                    $('#page-info').text(`Page ${paginationInfo.current_page} of ${paginationInfo.total_pages}`);
                    $('#visible-rows').text(rowsText);
                }
            }

            function updatePaginationButtons() {
                if (paginationInfo) {
                    const firstBtn = $('#first-page');
                    const prevBtn = $('#prev-page');
                    const nextBtn = $('#next-page');
                    const lastBtn = $('#last-page');

                    // Enable/disable buttons based on current page
                    firstBtn.prop('disabled', currentPage === 1);
                    prevBtn.prop('disabled', currentPage === 1);
                    nextBtn.prop('disabled', currentPage === paginationInfo.total_pages);
                    lastBtn.prop('disabled', currentPage === paginationInfo.total_pages);

                    // Update page info text
                    $('#page-info').text(`Page ${currentPage} of ${paginationInfo.total_pages}`);

                    // Update visible rows text
                    const start = ((currentPage - 1) * paginationInfo.per_page) + 1;
                    const end = Math.min(currentPage * paginationInfo.per_page, paginationInfo.total);
                    const total = paginationInfo.total;

                    if ($('#rows-per-page').val() === 'all') {
                        $('#visible-rows').text(`Showing all ${total} rows`);
                    } else {
                        $('#visible-rows').text(`Showing ${start} to ${end} of ${total} rows`);
                    }
                }
            }

            // Initialize tooltips
            function initTooltips() {
                $('[data-bs-toggle="tooltip"]').tooltip({
                    trigger: 'hover',
                    placement: 'top',
                    boundary: 'window',
                    container: 'body',
                    offset: [0, 5],
                    template: '<div class="tooltip" role="tooltip">' +
                        '<div class="tooltip-arrow"></div>' +
                        '<div class="tooltip-inner"></div></div>'
                });
            }

            // Make columns resizable
            function initResizableColumns() {
                const $table = $('#amazon-table');
                const $headers = $table.find('th');
                let startX, startWidth, columnIndex;

                $headers.each(function() {
                    $(this).append('<div class="resize-handle"></div>');
                });

                $table.on('mousedown', '.resize-handle', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    isResizing = true;
                    $(this).addClass('resizing');

                    const $th = $(this).parent();
                    columnIndex = $th.index();
                    startX = e.pageX;
                    startWidth = $th.outerWidth();

                    $('body').css('user-select', 'none');
                });

                $(document).on('mousemove', function(e) {
                    if (!isResizing) return;

                    const $resizer = $('.resize-handle.resizing');
                    if ($resizer.length) {
                        const $th = $resizer.parent();
                        const newWidth = startWidth + (e.pageX - startX);
                        $th.css('width', newWidth + 'px');
                        $th.css('min-width', newWidth + 'px');
                        $th.css('max-width', newWidth + 'px');
                    }
                });

                $(document).on('mouseup', function(e) {
                    if (!isResizing) return;

                    e.stopPropagation();
                    $('.resize-handle').removeClass('resizing');
                    $('body').css('user-select', '');
                    isResizing = false;
                });
            }

            // Initialize sorting functionality
            function initSorting() {
                $('th[data-field]').addClass('sortable').on('click', function(e) {
                    if (isResizing) {
                        e.stopPropagation();
                        return;
                    }

                    if ($(e.target).is('input') || $(e.target).closest('.position-relative').length) {
                        return;
                    }

                    const th = $(this).closest('th');
                    const thField = th.data('field');
                    const dataField = thField;

                    if (currentSort.field === dataField) {
                        currentSort.direction *= -1;
                    } else {
                        currentSort.field = dataField;
                        currentSort.direction = 1;
                    }

                    $('.sort-arrow').html('↓');
                    $(this).find('.sort-arrow').html(currentSort.direction === 1 ? '↑' : '↓');

                    // We'll do client-side sorting for the current page
                    const freshData = [...tableData];
                    freshData.sort((a, b) => {
                        const valA = a[dataField] || '';
                        const valB = b[dataField] || '';

                        if (dataField === 'sl_no' || dataField === 'INV' || dataField === 'L30') {
                            return (parseFloat(valA) - parseFloat(valB)) * currentSort.direction;
                        }

                        return String(valA).localeCompare(String(valB)) * currentSort.direction;
                    });

                    tableData = freshData;
                    renderTable();

                });
            }

            // Initialize pagination
            function initPagination() {
                // First page
                $('#first-page').off('click').on('click', function() {
                    if (currentPage > 1) {
                        currentPage = 1;
                        loadData().then(renderTable);
                    }
                });

                // Previous page
                $('#prev-page').off('click').on('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        loadData().then(renderTable);
                    }
                });

                // Next page
                $('#next-page').off('click').on('click', function() {
                    if (paginationInfo && currentPage < paginationInfo.total_pages) {
                        currentPage++;
                        loadData().then(renderTable);
                    }
                });

                // Last page
                $('#last-page').off('click').on('click', function() {
                    if (paginationInfo && currentPage < paginationInfo.total_pages) {
                        currentPage = paginationInfo.total_pages;
                        loadData().then(renderTable);
                    }
                });

                // Fix for row data type selector
                $('#row-data-type').off('change').on('change', function() {
                    currentPage = 1;
                    loadData().then(renderTable);
                });

                // Rows per page
                $('#rows-per-page').off('change').on('change', function() {
                    const value = $(this).val();
                    rowsPerPage = (value === 'all') ? 1000000 : parseInt(value);
                    currentPage = 1;
                    loadData().then(renderTable);
                });
            }

            // Initialize search functionality
            function initSearch() {
                // Handle search button click
                $('#search-button').on('click', function() {
                    currentPage = 1;
                    loadData().then(renderTable);
                });

                // Handle Enter key in search input
                $('#search-input').on('keyup', function(e) {
                    if (e.key === 'Enter') {
                        currentPage = 1;
                        loadData().then(renderTable);
                    }
                });

                // Handle clear search button
                $('#clear-search').on('click', function() {
                    $('#search-input').val('');
                    currentPage = 1;
                    loadData().then(renderTable);
                    $(this).hide();
                });
            }

            // Initialize column toggle functionality
            function initColumnToggle() {
                const $table = $('#amazon-table');
                const $headers = $table.find('th[data-field]');
                const $menu = $('#columnToggleMenu');
                const $dropdownBtn = $('#hideColumnsBtn');

                $menu.empty();

                $headers.each(function() {
                    const $th = $(this);
                    const field = $th.data('field');
                    const title = $th.text().trim().replace(' ↓', '');

                    const $item = $(`
                    <div class="column-toggle-item">
                        <input type="checkbox" class="column-toggle-checkbox" 
                               id="toggle-${field}" data-field="${field}" checked>
                        <label for="toggle-${field}">${title}</label>
                    </div>
                `);

                    $menu.append($item);
                });

                $dropdownBtn.on('click', function(e) {
                    e.stopPropagation();
                    $menu.toggleClass('show');
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.custom-dropdown').length) {
                        $menu.removeClass('show');
                    }
                });

                $menu.on('change', '.column-toggle-checkbox', function() {
                    const field = $(this).data('field');
                    const isVisible = $(this).is(':checked');

                    const colIndex = $headers.filter(`[data-field="${field}"]`).index();
                    $table.find('tr').each(function() {
                        $(this).find('td, th').eq(colIndex).toggle(isVisible);
                    });
                });

                $('#showAllColumns').on('click', function() {
                    $menu.find('.column-toggle-checkbox').prop('checked', true).trigger('change');
                    $menu.removeClass('show');
                });
            }

            // Initialize filters
            function initFilters() {
                $('.dropdown-menu').on('click', '.column-filter', function(e) {
                    e.preventDefault();
                    const $this = $(this);
                    const color = $this.data('color');
                    const text = $this.find('span').text().trim();

                    $this.closest('.dropdown')
                        .find('.dropdown-toggle')
                        .html(`<span class="status-circle ${color}"></span> DIL% (${text})`);

                    dilFilter = color;
                    currentPage = 1;
                    loadData().then(renderTable);
                });

                // Clear parent filter button
                $(document).on('click', '.clear-parent-filter', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const $container = $(this).closest('.dropdown-search-container');
                    const $input = $container.find('.parent-search');

                    $input.val('');
                    currentParentFilter = '';
                    $container.find('.dropdown-search-results').hide();
                    $(this).hide();

                    currentPage = 1;
                    loadData().then(renderTable);
                });

                // Clear SKU filter button
                $(document).on('click', '.clear-sku-filter', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const $container = $(this).closest('.dropdown-search-container');
                    const $input = $container.find('.sku-search');

                    $input.val('');
                    currentSkuFilter = '';
                    $container.find('.dropdown-search-results').hide();
                    $(this).hide();

                    currentPage = 1;
                    loadData().then(renderTable);
                });
            }

            // Calculate and display totals
            function calculateTotals() {
                try {
                    let invTotal = 0;
                    let l30Total = 0;

                    // Filter only child SKUs (exclude PARENT SKUs)
                    const childRows = filteredData.filter(item => !(item.SKU && item.SKU.toUpperCase().includes(
                        'PARENT')));

                    if (childRows.length === 0) {
                        $('#inv-total-parent').text('0');
                        $('#l30-total-parent').text('0');
                        return;
                    }

                    childRows.forEach(item => {
                        invTotal += parseFloat(item.INV) || 0;
                        l30Total += parseFloat(item.L30) || 0;
                    });

                    $('#inv-total-parent').text(invTotal.toLocaleString());
                    $('#l30-total-parent').text(l30Total.toLocaleString());
                } catch (error) {
                    console.error('❌ Error calculating parent totals:', error);
                    $('#inv-total-parent').text('0');
                    $('#l30-total-parent').text('0');
                }
            }






            // Initialize enhanced dropdowns
            function initEnhancedDropdowns() {
                // For parent search
                $('#parentSearch').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    const $results = $('#parentSearchResults');
                    $results.empty();
                    const $clearBtn = $(this).next('.clear-parent-filter');

                    // Show/hide clear button based on input
                    $clearBtn.toggle(searchTerm.length > 0);

                    if (!window.distinctParents || window.distinctParents.length === 0) return;
                    if (searchTerm.length === 0) {
                        $results.hide();
                        return;
                    }

                    const filtered = window.distinctParents.filter(parent =>
                        parent.toLowerCase().includes(searchTerm)
                    );

                    if (filtered.length > 0) {
                        filtered.forEach(value => {
                            $results.append(
                                `<div class="dropdown-search-item" tabindex="0" data-value="${value}">${value}</div>`
                            );
                        });
                        $results.show();
                    } else {
                        $results.append(
                            '<div class="dropdown-search-item no-results">No matches found</div>'
                        );
                        $results.show();
                    }
                });

                // For SKU search
                $('#skuSearch').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    const $results = $('#skuSearchResults');
                    const $clearBtn = $(this).next('.clear-sku-filter');

                    // Show/hide clear button based on input
                    $clearBtn.toggle(searchTerm.length > 0);
                    $results.empty();

                    if (!window.distinctSkus || window.distinctSkus.length === 0) return;
                    if (searchTerm.length === 0) {
                        $results.hide();
                        return;
                    }

                    const filtered = window.distinctSkus.filter(sku =>
                        sku.toLowerCase().includes(searchTerm)
                    );

                    if (filtered.length > 0) {
                        filtered.forEach(value => {
                            $results.append(
                                `<div class="dropdown-search-item" tabindex="0" data-value="${value}">${value}</div>`
                            );
                        });
                        $results.show();
                    } else {
                        $results.append(
                            '<div class="dropdown-search-item no-results">No matches found</div>'
                        );
                        $results.show();
                    }
                });

                // Handle selection
                $('.dropdown-search-results').on('click', '.dropdown-search-item:not(.no-results)', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const value = $(this).data('value');
                    const $container = $(this).closest('.dropdown-search-container');
                    const $input = $container.find('input');
                    const $clearBtn = $container.find('.btn-sm');
                    const field = $input.attr('id');

                    $input.val(value);
                    $clearBtn.show();

                    if (field === 'parentSearch') {
                        currentParentFilter = value;
                        currentSkuFilter = '';
                        $('#skuSearch').val('').next('.clear-sku-filter').hide();
                    } else if (field === 'skuSearch') {
                        currentSkuFilter = value;
                        currentParentFilter = '';
                        $('#parentSearch').val('').next('.clear-parent-filter').hide();
                    }

                    currentPage = 1;
                    loadData().then(renderTable);
                    $('.dropdown-search-results').hide();
                });

                // Close dropdowns when clicking elsewhere
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.dropdown-search-container').length) {
                        $('.dropdown-search-results').hide();
                    }
                });
            }



            // Initialize manual dropdowns
            function initManualDropdowns() {
                $(document).on('click', '.dropdown-toggle', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).next('.dropdown-menu').toggleClass('show');
                    $('.dropdown-menu').not($(this).next('.dropdown-menu')).removeClass('show');
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.dropdown').length) {
                        $('.dropdown-menu').removeClass('show');
                    }
                });

                $(document).on('click', '.dropdown-item', function(e) {
                    e.preventDefault();
                    const $dropdown = $(this).closest('.dropdown');

                    const color = $(this).data('color');
                    const text = $(this).text().trim();
                    $dropdown.find('.dropdown-toggle').html(
                        `<span class="status-circle ${color}"></span> ${text.split(' ')[0]}`
                    );

                    $dropdown.find('.dropdown-menu').removeClass('show');

                    const column = $(this).data('column');
                    state.filters[column] = color;
                    applyColumnFilters();
                });


                function showL30Modal(icon) {
                    const data = JSON.parse(icon.getAttribute('data-l30') || '{}');

                    const sites = [];
                    const l30Values = [];
                    const roiValues = [];
                    const pftValues = [];
                    const priceValues = [];

                    let tableHtml = '';

                    for (const [site, values] of Object.entries(data)) {
                        const price = parseFloat(values.price) || 0;
                        const l30 = parseFloat(values.l30) || 0;
                        const roi = parseFloat(values.roi) || 0;
                        const pft = parseFloat(values.pft) || 0;

                        sites.push(site);
                        priceValues.push(price);
                        l30Values.push(l30);
                        roiValues.push(roi);
                        pftValues.push(pft);

                        tableHtml += `
            <tr>
                <td>${site}</td>
                <td>$${price.toFixed(2)}</td>
                <td>${l30}</td>
                <td>${roi.toFixed(2)}%</td>
                <td>${pft.toFixed(2)}%</td>
            </tr>
        `;
                    }

                    // Update table in modal
                    document.getElementById('siteSummaryTableBody').innerHTML = tableHtml;

                    // Destroy previous chart
                    if (window.l30ChartInstance) {
                        window.l30ChartInstance.destroy();
                    }

                    // Build chart
                    const ctx = document.getElementById('l30Chart').getContext('2d');
                    window.l30ChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: sites,
                            datasets: [{
                                    label: 'L30',
                                    data: l30Values,
                                    backgroundColor: '#4bc0c0'
                                },
                                {
                                    label: 'ROI %',
                                    data: roiValues,
                                    backgroundColor: '#ff9f40'
                                },
                                {
                                    label: 'PFT %',
                                    data: pftValues,
                                    backgroundColor: '#9966ff'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Values'
                                    }
                                }
                            }
                        }
                    });

                    // Show modal
                    $('#l30Modal').modal('show');
                }

                $(document).on('keydown', '.dropdown', function(e) {
                    const $menu = $(this).find('.dropdown-menu');
                    const $items = $menu.find('.dropdown-item');
                    const $active = $items.filter(':focus');

                    switch (e.key) {
                        case 'Escape':
                            $menu.removeClass('show');
                            $(this).find('.dropdown-toggle').focus();
                            break;
                        case 'ArrowDown':
                            if ($menu.hasClass('show')) {
                                e.preventDefault();
                                $active.length ? $active.next().focus() : $items.first().focus();
                            }
                            break;
                        case 'ArrowUp':
                            if ($menu.hasClass('show')) {
                                e.preventDefault();
                                $active.length ? $active.prev().focus() : $items.last().focus();
                            }
                            break;
                        case 'Enter':
                            if ($active.length) {
                                e.preventDefault();
                                $active.click();
                            }
                            break;
                    }
                });
            }



            // Show notification
            function showNotification(type, message) {
                // Remove any existing notifications
                $('.custom-notification').remove();

                const notification = $(`
                    <div class="custom-notification position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                `);

                $('body').append(notification);

                // Auto-hide success notifications after 5 seconds
                if (type === 'success') {
                    setTimeout(() => {
                        notification.fadeOut(500, () => notification.remove());
                    }, 5000);
                }
            }

            // Loader functions
            function showLoader() {
                $('#data-loader').fadeIn();
            }

            function hideLoader() {
                $('#data-loader').fadeOut();
            }

            initTable();
        });



        let filteredData = []; // Declare it at the top

        // 2. Update the filteredData array to reflect the change
        const index = filteredData.findIndex(item => item['SL No.'] == itemId);
        if (index !== -1) {
            filteredData[index][title] = cacheUpdateValue;

            // If this is an SPRICE update, calculate and update Spft% in cache using new formula
            if (title === 'SPRICE' && filteredData[index].raw_data) {
                const item = filteredData[index];
                const AMZ = parseFloat(item.AMZ) || 0;
                const SHIP = parseFloat(item.raw_data.SHIP) || 0;
                const LP = parseFloat(item.raw_data.LP) || 0;
                const SPRICE = parseFloat(updatedValue) || 0;

                // Calculate Spft% using new formula: (SPRICE * AMZ - SHIP - LP) / SPRICE
                let Spft = 0;
                if (SPRICE !== 0) {
                    Spft = (SPRICE * 0.72 - SHIP - LP) / SPRICE;

                }

                // Update Spft% in cache and local data
                amazonDataCache.updateField(itemId, 'Spft%', Spft);
                filteredData[index]['Spft%'] = Spft;
                filteredData[index].raw_data['Spft%'] = Spft;
            }

            // If this is an R&A update, ensure the raw_data is also updated
            if (title === 'R&A' && filteredData[index].raw_data) {
                filteredData[index].raw_data[title] = cacheUpdateValue;
            }
        }


        function showL30Modal(icon) {
            const data = JSON.parse(icon.getAttribute('data-l30'));
            const inv = parseFloat(icon.getAttribute('data-inv')) || 0;

            let modalContent = `
    <h5 class="mb-3">Sitewise L30, Price, Profit %, ROI %</h5>
    <table class="table table-bordered" id="l30DynamicTable">
    <thead>
        <tr>
            <th>Site</th>
            <th>L30</th>
            <th>Price</th>
            <th>Pft %</th>
            <th>ROI %</th>
        </tr>
    </thead>
    <tbody>
`;

            let totalL30 = 0;
            let totalWeightedProfit = 0;
            let totalWeightedPrice = 0;
            let totalROIBase = 0;

            const rows = Object.entries(data).map(([site, values]) => {
                const l30 = parseFloat(values.l30) || 0;
                const pft = parseFloat(values.pft) || 0;
                const roi = parseFloat(values.roi) || 0;
                const price = parseFloat(values.price) || 0;

                totalL30 += l30;
                totalWeightedProfit += (pft * price * l30);
                totalWeightedPrice += (price * l30);
                totalROIBase += (inv * l30);

                const pftPercent = (pft * 100).toFixed(2);
                const roiPercent = (roi * 100).toFixed(2);

                let pftBgColor = 'pink';
                const pftValue = parseFloat(pftPercent);
                if (pftValue < 10) pftBgColor = 'red';
                else if (pftValue < 15) pftBgColor = 'orange';
                else if (pftValue < 20) pftBgColor = 'blue';
                else pftBgColor = 'green';

                let roiBgColor = 'pink';
                const roiValue = parseFloat(roiPercent);
                if (roiValue < 50) roiBgColor = 'red';
                else if (roiValue < 75) roiBgColor = 'orange';
                else if (roiValue < 100) roiBgColor = 'green';

                return {
                    site,
                    l30,
                    price: price.toFixed(2),
                    pftPercent,
                    roiPercent,
                    pftBgColor,
                    roiBgColor,
                };
            });

            // Sort by L30 descending
            rows.sort((a, b) => b.l30 - a.l30);

            // Render rows
            rows.forEach((row, index) => {
                modalContent += `
        <tr>
            <td>${row.site}</td>
            <td>${row.l30}</td>
            <td>$${row.price}</td>
            <td style="color:${row.pftBgColor};">${row.pftPercent}%</td>
            <td style="color:${row.roiBgColor};">${row.roiPercent}%</td>
        </tr>`;
            });

            // Calculate avg profit & ROI %
            const avgPftPercent = totalWeightedPrice > 0 ? (totalWeightedProfit / totalWeightedPrice * 100).toFixed(2) :
                '0.00';
            const avgRoiPercent = totalROIBase > 0 ? (totalWeightedProfit / totalROIBase * 100).toFixed(2) : '0.00';

            modalContent += `
    <tr style="font-weight:bold; background-color:#f0f0f0;">
        <td>Total</td>
        <td>${totalL30.toFixed(2)}</td>
        <td>Avg</td>
        <td>${avgPftPercent}%</td>
        <td>${avgRoiPercent}%</td>
    </tr>
`;

            modalContent += `
    </tbody>
    </table>`;




            document.getElementById('l30ModalBody').innerHTML = modalContent;

            // Apply !important styles dynamically via setProperty
            rows.forEach((row, index) => {
                const pftCell = document.getElementById(`pftCell${index}`);
                const roiCell = document.getElementById(`roiCell${index}`);

                if (pftCell) {
                    pftCell.style.setProperty("background-color", row.pftBgColor, "important");
                    pftCell.style.setProperty("color", "white", "important");
                }
                if (roiCell) {
                    roiCell.style.setProperty("background-color", row.roiBgColor, "important");
                    roiCell.style.setProperty("color", "white", "important");
                }
            });

            const modal = new bootstrap.Modal(document.getElementById('l30Modal'));
            modal.show();
        }
        if (!document.getElementById('pricingModal')) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            $('body').append(`
        <div class="modal fade" id="pricingModal" tabindex="-1" aria-labelledby="pricingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content p-3">
                    <div class="modal-header">
                        <h5 class="modal-title">SPRICE Calculator</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="pricingForm">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" id="skuInput" name="sku">

                            <div class="mb-2">
                                <label>SPRICE ($)</label>
                                <input type="number" step="0.01" class="form-control" id="sprPriceInput" name="sprice">
                            </div>
                            <div class="mb-2">
                                <label>SPFT%</label>
                                <input type="text" class="form-control" id="spftPercentInput" name="sprofit_percent" readonly>
                            </div>
                            <div class="mb-2">
                                <label>SROI%</label>
                                <input type="text" class="form-control" id="sroiPercentInput" name="sroi_percent" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    `);
        }


        function showProfitModal(icon) {
            const data = JSON.parse(icon.getAttribute('data-profit'));

            let modalContent = `
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Price</th>
                    <th>Profit (%)</th>
                </tr>
            </thead>
            <tbody>
    `;

            for (const [channel, details] of Object.entries(data)) {
                modalContent += `
            <tr>
                <td>${channel}</td>
                <td>${details.price ?? 0}</td>
                <td>${details.profit ?? 0}%</td>
            </tr>
        `;
            }

            modalContent += `
            </tbody>
        </table>
    `;

            document.getElementById('profitModalBody').innerHTML = modalContent;

            const modal = new bootstrap.Modal(document.getElementById('profitModal'));
            modal.show();
        }





        function showRoiModal(icon) {
            const data = JSON.parse(icon.getAttribute('data-roi'));

            let modalContent = `
        <table class="table table-bordered ">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Price</th>
                    <th>ROI (%)</th>
                </tr>
            </thead>
            <tbody>
    `;

            for (const [channel, details] of Object.entries(data)) {
                modalContent += `
            <tr>
                <td>${channel}</td>
                <td>${details.price}</td>
                <td>${details.roi}%</td>
            </tr>
        `;
            }

            modalContent += `
            </tbody>
        </table>
    `;

            document.getElementById('roiModalBody').innerHTML = modalContent;

            const modal = new bootstrap.Modal(document.getElementById('roiModal'));
            modal.show();
        }
    </script>

    <script>
        $(document).on('submit', '#pricingForm', function(e) {
            e.preventDefault();

            const sku = $('#skuInput').val();
            const sprice = $('#sprPriceInput').val();
            const sprofit_percent = $('#spftPercentInput').val();
            const sroi_percent = $('#sroiPercentInput').val();
            const csrfToken = $('input[name="_token"]').val();

            $.ajax({
                url: '/pricing-master/save',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    sku: sku,
                    sprice: sprice,
                    sprofit_percent: sprofit_percent,
                    sroi_percent: sroi_percent
                },
                success: function(response) {
                    if (response.status === 200) {
                        $('#pricingModal').modal('hide');
                        showNotification('success', response.message); // show toast

                        const {
                            sku,
                            sprice,
                            sprofit_percent,
                            sroi_percent
                        } = response.data;

                        // Update SPRICE cell
                        if (sprice !== null && !isNaN(sprice)) {
                            $(`#sprice-${sku}`).html(`
                        <span class="badge bg-primary">$${parseFloat(sprice).toFixed(2)}</span>
                        <i class="fa fa-edit text-primary ms-2" style="cursor:pointer;" 
                            onclick='openPricingModal(${JSON.stringify({ LP: response.data.lp, SHIP: response.data.ship, SKU: sku })})'></i>
                    `);
                        }

                        // Update SPFT cell
                        if (sprofit_percent !== null && !isNaN(sprofit_percent)) {
                            $(`#spft-${sku}`).html(`
                        <span class="badge bg-success">${parseFloat(sprofit_percent).toFixed(2)}%</span>
                    `);
                        }

                        // Update SROI cell
                        if (sroi_percent !== null && !isNaN(sroi_percent)) {
                            $(`#sroi-${sku}`).html(`
                        <span class="badge bg-info">${parseFloat(sroi_percent).toFixed(2)}%</span>
                    `);
                        }
                    }
                }

            });
        });


        document.addEventListener("DOMContentLoaded", function() {
            fetch('/pricing-analysis-data-view?page=1&per_page=100&dil_filter=all&data_type=all')
                .then(response => response.json())
                .then(data => {
                    console.log("✅ Pricing Analysis Data:", data.data); // 👈 Shows processedData
                    console.log("📦 Distinct Values:", data.distinct_values);
                    console.log("📄 Pagination:", data.pagination);
                })
                .catch(error => {
                    console.error("❌ Failed to fetch pricing data:", error);
                });
        });







        function openSiteAnalysisModal(icon) {
            const data = JSON.parse(icon.getAttribute('data-analysis') || '{}');

            const sites = [];
            const l30Values = [];
            const roiValues = [];
            const pftValues = [];
            const priceValues = [];

            let tableHtml = '';

            for (const [site, values] of Object.entries(data)) {
                const price = parseFloat(values.price) || 0;
                const l30 = parseFloat(values.l30) || 0;
                const roi = (parseFloat(values.roi) || 0) * 100;
                const pft = (parseFloat(values.pft) || 0) * 100;


                sites.push(site);
                priceValues.push(price);
                l30Values.push(l30);
                roiValues.push(roi);
                pftValues.push(pft);

                tableHtml += `
            <tr>
                <td>${site}</td>
                <td>$${price.toFixed(2)}</td>
                <td>${l30}</td>
                <td>${roi.toFixed(2)}%</td>
                <td>${pft.toFixed(2)}%</td>
            </tr>
        `;
            }

            document.getElementById('siteSummaryTableBody').innerHTML = tableHtml;

            // Destroy existing chart
            if (window.siteAnalysisChartInstance) {
                window.siteAnalysisChartInstance.destroy();
            }

            // Draw chart
            const ctx = document.getElementById('siteAnalysisChart').getContext('2d');
            window.siteAnalysisChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: sites,
                    datasets: [{
                            label: 'L30',
                            data: l30Values,
                            backgroundColor: '#e6194b', // Vibrant Red
                            borderColor: '#b2163e',
                            borderWidth: 1
                        },
                        {
                            label: 'ROI %',
                            data: roiValues,
                            backgroundColor: '#3cb44b', // Bright Green
                            borderColor: '#2a8034',
                            borderWidth: 1
                        },
                        {
                            label: 'PFT %',
                            data: pftValues,
                            backgroundColor: '#ffe119', // Vivid Yellow
                            borderColor: '#d4be17',
                            borderWidth: 1
                        },
                        {
                            label: 'Price',
                            data: priceValues,
                            backgroundColor: '#4363d8', // Strong Blue
                            borderColor: '#2e41a6',
                            borderWidth: 1
                        }
                    ]

                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Values'
                            }
                        }
                    }
                }
            });

            // Show modal
            $('#siteAnalysisModal').modal('show');



        }
    </script>
    <script>
        function openPricingModal({
            LP = 0,
            SHIP = 0,
            SKU = ''
        }) {
            $('#skuInput').val(SKU);

            const $sprInput = $('#sprPriceInput');
            const $spftInput = $('#spftPercentInput');
            const $sroiInput = $('#sroiPercentInput');

            // Reset values
            $sprInput.val('');
            $spftInput.val('');
            $sroiInput.val('');

            // Parse numbers
            LP = parseFloat(LP) || 0;
            SHIP = parseFloat(SHIP) || 0;

            // Input calculation
            $sprInput.off('input').on('input', function() {
                const SPRICE = parseFloat(this.value) || 0;

                if (SPRICE > 0) {
                    const SPFT = ((SPRICE * 0.72) - LP - SHIP) / SPRICE;
                    const SROI = ((SPRICE * 0.72) - LP - SHIP) / LP;

                    $spftInput.val((SPFT * 100).toFixed(2)); // ✅ No %
                    $sroiInput.val(isFinite(SROI) ? (SROI * 100).toFixed(2) : '∞'); // ✅ No %
                } else {
                    $spftInput.val('');
                    $sroiInput.val('');
                }
            });

            $('#pricingModal').modal('show');
        }

        function showNotification(type, message) {
            $('#toastMessage').text(message);
            $('#mainToast').removeClass('bg-success bg-danger').addClass(type === 'success' ? 'bg-success' : 'bg-danger');
            const toast = new bootstrap.Toast(document.getElementById('mainToast'));
            toast.show();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection
