@extends('layouts.vertical', ['title' => 'Transit Container INV'])
@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css" rel="stylesheet">
<style>
  .tabulator .tabulator-header {
    background: linear-gradient(90deg, #e0e7ff 0%, #f4f7fa 100%);
    border-bottom: 2px solid #2563eb;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.10);
  }

  .tabulator .tabulator-header .tabulator-col {
    text-align: center;
    background: transparent;
    border-right: 1px solid #e5e7eb;
    padding: 16px 10px;
    font-weight: 700;
    color: #1e293b;
    font-size: 1.08rem;
    letter-spacing: 0.02em;
    transition: background 0.2s;
  }

  .tabulator .tabulator-header .tabulator-col:hover {
    background: #e0eaff;
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
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    font-size: 1rem;
    color: #000000;
    font-weight: 500;
    vertical-align: middle;
    max-width: 300px;
    transition: background 0.18s, color 0.18s;
  }

  .tabulator .tabulator-cell:focus {
    outline: 2px solid #2563eb;
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
    border-top: 1px solid #e5e7eb;
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
    .nav-tabs {
    overflow-x: auto;
    overflow-y: hidden;
    flex-wrap: nowrap;
    white-space: nowrap;
    scrollbar-width: thin; /* Firefox */
  }

  .nav-tabs .nav-item {
    flex-shrink: 0;
  }

  /* Optional: customize scrollbar */
  .nav-tabs::-webkit-scrollbar {
    height: 6px;
  }

  .nav-tabs::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 5px;
  }

  .nav-tabs::-webkit-scrollbar-track {
    background: transparent;
  }
</style>
@section('content')
@include('layouts.shared.page-title', ['page_title' => 'Transit Container INV', 'sub_title' => 'Transit Container INV'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                    <div class="d-flex gap-4 align-items-center">
                        <div class="fw-semibold text-dark" style="font-size: 1rem;">
                            📦 Total Ctns: <span class="text-success" id="total-cartons-display">0</span>
                        </div>
                        <div class="fw-semibold text-dark" style="font-size: 1rem;">
                            🧮 Total Qty: <span class="text-primary" id="total-qty-display">0</span>
                        </div>
                        <div class="fw-semibold text-dark" style="font-size: 1rem;">
                            💲 Total Amt: <span class="text-primary" id="total-amount-display">0</span>
                        </div>
                    </div>

                    <!-- 🔽 Filter Type Dropdown -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter-type" class="fw-semibold mb-0" style="font-size: 0.95rem;">Filter Type:</label>
                        <select id="filter-type" class="form-select form-select-sm" style="width: 120px;">
                            <option value="">All</option>
                            <option value="new">New</option>
                            <option value="changes">Changes</option>
                        </select>
                    </div>

                    <!-- 🔍 Search Input -->
                    <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search by SKU, Supplier, Parent..." 
                        style="max-width: 200px; border: 2px solid #2185ff; font-size: 0.95rem;">

                    <!-- ➕ Add Container Button -->
                    <button id="add-tab-btn" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add Container
                    </button>
                </div>

                <!-- Tabs Navigation -->
                <div style="overflow-x: auto; overflow-y: hidden; scrollbar-width: none; -ms-overflow-style: none;">
                    <style>
                        div[style*="overflow-x: auto"]::-webkit-scrollbar {
                            display: none;
                        }
                    </style>
                    <ul class="nav nav-tabs flex-nowrap d-flex mb-0" id="tabList" role="tablist" style="min-width: max-content;">
                        @foreach($tabs as $index => $tab)
                            <li class="nav-item" style="flex-shrink: 0;">
                                <button class="nav-link {{ $index == 0 ? 'active' : '' }}" id="tab-{{ $index }}-tab" data-bs-toggle="tab" data-bs-target="#tab-{{ $index }}" type="button" role="tab">
                                    {{ $tab }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Tabs Content -->
                <div class="tab-content mt-3" id="tabContent">
                    @foreach($groupedData as $tabName => $items)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $loop->index }}" role="tabpanel">
                            <div id="tabulator-{{ $loop->index }}" class="tabulator-table"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>
<script>
let tabCounter = {{ count($tabs) }};
const groupedData = @json($groupedData);

Object.entries(groupedData).forEach(([tabName, data], index) => {
    let table = new Tabulator(`#tabulator-${index}`, {
        layout: "fitDataFill",
        data: data,
        pagination: "local",
        paginationSize: 50,
        height: "700px",
        rowHeight: 55,
        columns: [
            {
            title: "Sl No.",
            formatter: function(cell) {
                return cell.getRow().getPosition(true) + 0;
            },
            hozAlign: "center",
            headerSort: false
            },
            { title: "Parent", field: "parent"},
            { title: "Sku", field: "our_sku", editor: "input" },
            { title: "Supplier", field: "supplier_name", editor: "input"},
            {
              title: "Images",
              field: "photos",
              editor: function(cell, onRendered, success, cancel) {
                const container = document.createElement("div");
                container.style.display = "flex";
                container.style.flexDirection = "column";

                const preview = document.createElement("div");
                preview.style.marginBottom = "6px";

                const input = document.createElement("input");
                input.type = "file";
                input.accept = "image/*";
                input.multiple = false;
                input.style.marginBottom = "6px";

                input.addEventListener("change", function (e) {
                  if (e.target.files.length > 0) {
                    handleUpload(e.target.files[0]);
                  }
                });

                container.appendChild(input);
                container.appendChild(preview);

                setTimeout(() => {
                  container.focus();
                }, 200);

                container.setAttribute("contenteditable", true);
                container.addEventListener("paste", function (e) {
                  e.preventDefault();
                  for (let item of e.clipboardData.items) {
                    if (item.type.indexOf("image") !== -1) {
                      const blob = item.getAsFile();
                      handleUpload(blob);
                    }
                  }
                });

                function handleUpload(file) {
                  const formData = new FormData();
                  formData.append("image", file);
                  formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);

                  fetch("/upload-image", {
                    method: "POST",
                    body: formData,
                  })
                  .then(res => res.json())
                  .then(data => {
                    if (data.url) {
                      preview.innerHTML = `<img src="${data.url}" style="height: 50px;"/>`;
                      success(data.url);
                    } else {
                      alert("Upload failed.");
                      cancel();
                    }
                  })
                  .catch(err => {
                    console.error(err);
                    alert("Upload error.");
                    cancel();
                  });
                }

                return container;
              },

              // ✅ Enhanced formatter with fallback to `TransitContainerDetail.photos` or default image
              formatter: function(cell) {
                const row = cell.getRow().getData();
                let url = cell.getValue(); // primary from TransitContainerDetail.photos

                // Fallback 1: shopify image_src
                if (!url && row.image_src) {
                  url = row.image_src;
                }

                // Fallback 2: product_master.Values.image_path
                if (!url && row.Values) {
                  try {
                    const values = typeof row.Values === "string" ? JSON.parse(row.Values) : row.Values;
                    if (values.image_path) {
                      url = "/storage/" + values.image_path.replace(/^storage\//, "");
                    }
                  } catch (err) {
                    console.error("JSON parse error:", err);
                  }
                }

                return url
                  ? `<a href="${url}" target="_blank"><img src="${url}" style="height:40px;border-radius:4px;border:1px solid #ccc"/></a>`
                  : '<span class="text-muted">No Image</span>';
              }
            },
            { title: "Qty / Ctns", field: "no_of_units", editor: "input" },
            { title: "Qty Ctns", field: "total_ctn", editor: "input" },
            { 
              title: "Qty", 
              field: "pcs_qty", 
              editor: false,
              formatter: function(cell) {
                  const data = cell.getRow().getData();
                  const units = parseFloat(data.no_of_units) || 0;
                  const ctn = parseFloat(data.total_ctn) || 0;
                  return units * ctn;
              }
            },
            { title: "Rate ($)", field: "rate", editor: "input" },
            {
              title: "Unit",
              field: "unit",
              headerSort: false,
                hozAlign: "center",
                editor: function (cell, onRendered, success, cancel) {
                const value = cell.getValue();
                const select = document.createElement("select");
                select.className = "form-select form-select-sm";
                select.style.minWidth = "110px";
                select.style.padding = "4px 10px";
                select.style.height = "32px";
                select.style.borderRadius = "6px";
                select.style.border = "1px solid #cbd5e1";
                select.style.background = "#f8fafc";
                select.style.fontWeight = "500";
                select.style.fontSize = "1rem";

                const options = {
                  pieces: "Pieces",
                  pair: "Pair",
                };

                for (let key in options) {
                  const option = document.createElement("option");
                  option.value = key;
                  option.textContent = options[key];
                  select.appendChild(option);
                }

                select.value = value || "pieces";

                select.addEventListener("change", function () {
                  success(this.value);
                });

                select.addEventListener("blur", function () {
                  success(select.value);
                });

                onRendered(() => {
                  select.focus();
                  const event = new MouseEvent('mousedown', { bubbles: true });
                  select.dispatchEvent(event);
                });

                return select;
                },
                formatter: function (cell) {
                const value = cell.getValue();
                if (value === "pieces")
                  return '<span class="badge bg-primary" style="font-size:0.98rem;padding:6px 14px;border-radius:6px;">Pcs</span>';
                if (value === "pair")
                  return '<span class="badge bg-info text-dark" style="font-size:0.98rem;padding:6px 14px;border-radius:6px;">Pair</span>';
                return `<span class="badge bg-secondary" style="font-size:0.98rem;padding:6px 14px;border-radius:6px;">${value || "—"}</span>`;
                },
                cellClick: function (e, cell) {
                cell.edit(true);
                },
                cellDblClick: function (e, cell) {
                cell.edit(true);
                },
              },
            {
              title: "Amt($)", 
              field: "amount", 
              editor: false,
              mutator: false,  // Don't store in data
              formatter: function(cell) {
                const data = cell.getRow().getData();
                const rate = parseFloat(data.rate) || 0;
                const pcs_qty = parseFloat(data.no_of_units || 0) * parseFloat(data.total_ctn || 0);
                return Math.round(rate * pcs_qty);
              }
            },
            { title: "Changes", field: "changes", editor: "input" },
            { 
              title: "Specifications",
              field: "specification", 
              editor: "input",
              formatter: function(cell) {
                const value = cell.getValue();
                return `<div title="${value?.replace(/"/g, '&quot;') ?? ''}" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                          ${value ?? ''}
                        </div>`;
              }
            },
        ],
    });

    window.addEventListener("DOMContentLoaded", () => {
      document.documentElement.setAttribute("data-sidenav-size", "condensed");
        const firstTabIndex = 0;
        const table = window.tabTables[firstTabIndex];
        if (table) {
            setTimeout(() => {
                updateActiveTabSummary(firstTabIndex, table);
            }, 300);
        }
    });

    if (data.length === 0) {
        table.addRow({ tab_name: tabName });
    }

    table.on("cellEdited", function(cell) {
        const row = cell.getRow();
        const data = row.getData();
        data.tab_name = tabName;
        const field = cell.getField();

        if (["no_of_units", "total_ctn"].includes(field)) {
            const units = parseFloat(data.no_of_units) || 0;
            const ctn = parseFloat(data.total_ctn) || 0;
            const pcs_qty = units * ctn;
            row.update({ pcs_qty: pcs_qty });

            const rate = parseFloat(data.rate) || 0;
            const amount = rate * pcs_qty;
            row.update({ amount: amount });
        }

        if (["rate", "pcs_qty"].includes(field)) {
            const rate = parseFloat(data.rate) || 0;
            const qty = parseFloat(data.pcs_qty) || 0;
            const amount = rate * qty;
            row.update({ amount: amount });
        }

        fetch('/transit-container/save-row', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
            if (response.success || response.id) {
                console.log("Row saved successfully:", response);
                if (response.id) {
                    row.update({ id: response.id }); 
                }

                const allRows = table.getRows();
                if (row === allRows[allRows.length - 1]) {
                    const field = cell.getField();
                    const value = cell.getValue();
                    const isNotEmpty = value !== null && value !== "" && value !== 0;

                    if (isNotEmpty) {
                        const newRowData = { tab_name: tabName };
                        table.addRow(newRowData).then((newRow) => {
                            fetch('/transit-container/save-row', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify(newRowData)
                            })
                            .then(res => res.json())
                            .then(newRes => {
                                if (newRes.id) {
                                    newRow.update({ id: newRes.id });
                                    console.log("New row added with ID:", newRes.id);
                                }
                            })
                            .catch(err => {
                                console.error("Error saving new row:", err);
                            });
                        });
                    }
                }

            } else {
                alert(response.message || "Update failed");
            }
        })
        .catch(err => {
            console.error("Save error:", err);
            alert("Something went wrong while saving");
        });

        updateActiveTabSummary(index, table);
    });

    window.tabTables = window.tabTables || {};
    window.tabTables[index] = table;

});

// Tab Add Button
document.getElementById('add-tab-btn').addEventListener('click', async function () {
    const tabName = prompt("Enter new container name:");
    if (!tabName || tabName.trim() === "") {
        alert("Tab name is required.");
        return;
    }

    const response = await fetch('/transit-container/add-tab', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ tab_name: tabName.trim() })
    });

    const result = await response.json();
    if (!result.success) {
        alert(result.message || 'Failed to create tab.');
        return;
    }

    location.reload();
});

function updateActiveTabSummary(index, table) {
  const data = table.getData();
  let totalCtn = 0;
  let totalQty = 0;
  let totalAmount = 0;

  data.forEach(row => {
    const ctn = parseFloat(row.total_ctn) || 0;
    const units = parseFloat(row.no_of_units) || 0;
    const rate = parseFloat(row.rate) || 0;
    totalCtn += ctn;
    totalQty += ctn * units;
    totalAmount += ctn * units * rate;
  });

  document.getElementById("total-cartons-display").textContent = totalCtn;
  document.getElementById("total-qty-display").textContent = totalQty;
  document.getElementById("total-amount-display").textContent = Math.round(totalAmount);

}

document.querySelectorAll('[data-bs-toggle="tab"]').forEach((btn, index) => {
    btn.addEventListener("shown.bs.tab", () => {
        if (window.tabTables && window.tabTables[index]) {
            updateActiveTabSummary(index, window.tabTables[index]);
        }
    });
});

document.getElementById('search-input').addEventListener('input', function () {
    const value = this.value.toLowerCase();

    // Get currently active tab index
    const activeTab = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
    if (!activeTab) return;

    const activeIndex = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]')).indexOf(activeTab);
    const activeTable = window.tabTables[activeIndex];

    if (activeTable) {
        activeTable.setFilter([
            [
                { field: "our_sku", type: "like", value: value },
                { field: "supplier_name", type: "like", value: value },
                { field: "parent", type: "like", value: value }
            ]
        ]);
    }
});

  document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("filter-type").addEventListener("change", function () {
        const selected = this.value;

        const activeTab = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
        if (!activeTab) return;

        const activeIndex = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]')).indexOf(activeTab);
        const activeTable = window.tabTables[activeIndex];

        if (!activeTable) {
            console.warn("No Tabulator instance found for index:", activeIndex);
            return;
        }

        if (selected === "new") {
            activeTable.setFilter((data) => {
                const parent = (data.parent || "").toUpperCase().trim();
                return parent === "SOURCING";
            });
        } else if (selected === "changes") {
            activeTable.setFilter((data) => {
                const parent = (data.parent || "").toUpperCase().trim();
                return parent !== "SOURCING";
            });
        } else {
            activeTable.clearFilter();
        }

        activeTable.redraw();
        console.log("Filtered data count:", activeTable.getDataCount("active"));
    });
  });





document.body.style.zoom = "98%"; 

</script>
@endsection
