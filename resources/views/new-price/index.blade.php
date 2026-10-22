{{--
    Set New Price: lembar kerja harga baru per periode. Current price = harga di price master yang Valid di awal periode.
    New Price & Remark diisi langsung di grid (atau Adjust % / Upload), lalu disimpan sekaligus dengan Save.
--}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="np-tabs" role="tablist">
        <button type="button" class="np-tab active" data-pane="paneSaved"><i class="fa fa-list-alt"></i> Saved New Prices <span class="np-tab-count" id="savedCount"></span></button>
        <button type="button" class="np-tab" data-pane="paneWork"><i class="fa fa-pencil-square-o"></i> {{ $edit ? 'Set New Price' : 'Worksheet' }}</button>
    </div>

    {{-- Tab 1: daftar new price yang sudah disimpan (semua periode), dikelompokkan per periode lalu supplier. --}}
    <div class="dp-card np-pane" id="paneSaved">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_savedAdd"><i class="fa fa-plus"></i> Set New Price</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_savedExport"><i class="fa fa-download"></i> Export Excel</button>
            </div>
        </div>
        <div class="box-body">
            <div id="savedGrid"></div>
        </div>
    </div>

    {{-- Tab 2: lembar kerja per periode (semua item ber-harga Valid), untuk mengisi / mengubah new price. --}}
    <div class="dp-card np-pane" id="paneWork" style="display: none;">
        <div class="np-filter">
            <div class="np-field">
                <label>New Price Period <span class="text-red">*</span></label>
                <div class="np-range">
                    <input type="date" class="form-control" id="filterFrom" />
                    <span>to</span>
                    <input type="date" class="form-control" id="filterTo" />
                </div>
            </div>
            <div class="np-field np-grow">
                <label>Supplier</label>
                <select class="form-control" id="filterSupplier"></select>
            </div>
            <div class="np-field">
                <label>&nbsp;</label>
                <label class="np-check"><input type="checkbox" id="filterOnlyNew" /> Only items with new price</label>
            </div>
        </div>

        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_save" disabled><i class="fa fa-save"></i> Save</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_adjust" disabled><i class="fa fa-percent"></i> Adjust %</button>
                    <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_clear" disabled><i class="fa fa-eraser"></i> Clear New Price</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_upload"><i class="fa fa-upload"></i> Upload</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_workRefresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalDetail" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width: 92%; max-width: 1280px;">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-tags"></i></div>
                <div>
                    <div class="dpf-header-title" id="detailTitle"></div>
                    <div class="dpf-header-sub" id="detailSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div id="detailGrid"></div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_detailEdit"><i class="fa fa-pencil"></i> {{ $edit ? 'Edit in Set New Price' : 'Open in Worksheet' }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalAdjust" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-percent"></i></div>
                <div>
                    <div class="dpf-header-title">Adjust %</div>
                    <div class="dpf-header-sub" id="adjustSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <label class="np-label">Change from current price (%)</label>
                <input class="form-control" id="adjustPct" type="number" step="0.01" placeholder="e.g. 5 or -3" />
                <div class="dpf-hint">New Price = Current Price × (1 + %). IDR is rounded to whole numbers, other currencies to 2 decimals.</div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_adjustOk"><i class="fa fa-check"></i> Apply</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalUpload" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-upload"></i></div>
                <div>
                    <div class="dpf-header-title">Upload New Price</div>
                    <div class="dpf-header-sub" id="uploadSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">File <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="uploadFile" type="file" accept=".xlsx,.xls" />
                        <div class="dpf-hint">Use the file from Export Excel: fill the New Price (and Remark) columns. Rows are matched by Item Code + Supplier Code; rows with an empty New Price are ignored.</div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doUpload"><i class="fa fa-upload"></i> Upload</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .np-filter { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; padding: 14px 16px 4px; }
    .np-field label, .np-label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
    .np-grow { flex: 0 1 360px; min-width: 220px; }
    .np-range { display: flex; align-items: center; gap: 6px; }
    .np-range span { font-size: 12px; color: #6b7280; }
    .np-dirty td { background: #FFF4D6 !important; }
    .np-cell-warn { color: #9A6200; margin-left: 4px; }
    .np-cell-need { color: #B42318; }
    .np-edit-col { background: #F5F8FF; }
    .np-tabs { display: flex; gap: 4px; margin-bottom: -1px; position: relative; z-index: 1; }
    .np-tab { border: 1px solid #E3E8EE; border-bottom-color: transparent; background: #F3F5F8; color: #66788A; font-size: 13px; font-weight: 600;
        padding: 8px 16px; border-radius: 8px 8px 0 0; cursor: pointer; }
    .np-tab:hover { color: #2450D8; }
    .np-tab.active { background: #fff; color: #1D2939; border-color: #E3E8EE; border-bottom-color: #fff; }
    .np-tab i { margin-right: 4px; }
    .np-tab-count { display: inline-block; min-width: 20px; padding: 0 6px; margin-left: 4px; border-radius: 10px; background: #E7F1FB; color: #2450D8; font-size: 11px; line-height: 18px; }
    .np-tab-count:empty { display: none; }
    .np-pane.dp-card { border-top-left-radius: 0; }
    .np-check { display: inline-flex !important; align-items: center; gap: 6px; font-weight: 500 !important; height: 34px; margin: 0; cursor: pointer; }
    #savedGrid .dx-data-row { cursor: pointer; }
    .np-kebab { border: 0; background: transparent; width: 26px; height: 26px; border-radius: 6px; color: #66788A; cursor: pointer; }
    .np-kebab:hover, .np-kebab.open { background: #E7F1FB; color: #2450D8; }
    .np-menu { position: absolute; z-index: 1600; min-width: 170px; background: #fff; border: 1px solid #E3E8EE; border-radius: 8px;
        box-shadow: 0 6px 20px rgba(16, 24, 40, .12); padding: 4px; display: none; }
    .np-menu button { display: flex; align-items: center; gap: 8px; width: 100%; border: 0; background: transparent; text-align: left;
        font-size: 13px; color: #344054; padding: 7px 10px; border-radius: 6px; cursor: pointer; }
    .np-menu button i { width: 14px; text-align: center; color: #66788A; }
    .np-menu button:hover:not(:disabled) { background: #F3F5F8; }
    .np-menu button.danger, .np-menu button.danger i { color: #C62828; }
    .np-menu button:disabled { opacity: .45; cursor: not-allowed; }
    .np-menu hr { margin: 4px 0; border-color: #EEF0F3; }
    .np-group b { color: #1D2939; }
    .np-group span { color: #66788A; margin-left: 8px; font-weight: 400; }
</style>
<script type="text/javascript">
    (function () {
        var canEdit = @json($edit);
        var threshold = {{ (float) $threshold }};
        var urls = {
            search: "{{ url('/NewPrice/Search') }}",
            saved: "{{ url('/NewPrice/Saved') }}",
            savedExcel: "{{ url('/NewPrice/DownloadSavedExcel') }}",
            save: "{{ url('/NewPrice/Save') }}",
            remove: "{{ url('/NewPrice/Delete') }}",
            upload: "{{ url('/NewPrice/Upload') }}",
            exportExcel: "{{ url('/NewPrice/DownloadExcel') }}",
            supplierLookup: "{{ url('/Lookup/Supplier') }}"
        };

        var rows = [];        // baris lembar kerja (format Search)
        var dirty = {};       // Key -> true untuk baris yang belum disimpan
        var loaded = null;    // filter yang sedang ditampilkan: { dateFrom, dateTo, supplierId }
        var savedRows = [];   // new price tersimpan semua periode (format Saved)
        var savedGrid, detailGrid;
        var savedHeaders = []; // satu baris per periode + supplier
        var detailHeader = null; // header yang sedang dibuka di popup
        var workReady = false; // tab Set New Price sudah pernah dibuka / di-load
        var supplierStats = {}; // label group supplier -> { total, withNew } untuk baris group grid
        var grid;

        function num(v) { var n = parseFloat(v); return isNaN(n) ? null : n; }
        function fmt(v) { return v === null || v === undefined ? "" : Number(v).toLocaleString("en-US", { maximumFractionDigits: 4 }); }
        function dirtyCount() { return Object.keys(dirty).length; }

        // Kolom turunan satu baris (sama dengan aturan NewPriceRepo::apply).
        function calc(r) {
            r.NewPrice = num(r.NewPrice);
            r.Movement = r.NewPrice !== null && r.CurrentPrice !== null ? r.NewPrice - r.CurrentPrice : null;
            r.MovementPct = r.Movement !== null && r.CurrentPrice > 0 ? Math.round(r.Movement / r.CurrentPrice * 10000) / 100 : null;
            r.NeedRemark = r.MovementPct !== null && r.MovementPct > threshold && !$.trim(r.Remark || "");
            return r;
        }

        function filters() {
            return {
                dateFrom: $("#filterFrom").val(),
                dateTo: $("#filterTo").val(),
                supplierId: $("#filterSupplier").val() || ""
            };
        }

        function dmy(d) { return moment(d).format("DD-MM-YYYY"); }
        function periodKey(from, to) { return from + "|" + to; }
        function supplierLabel(r) { return (r.SupplierCode || "") + " - " + (r.SupplierName || ""); }

        // ---------- Tab Saved New Prices ----------
        function avg(list) { return list.length ? Math.round(list.reduce(function (a, b) { return a + b; }, 0) / list.length * 100) / 100 : null; }

        // Detail tersimpan -> header per periode + supplier.
        function buildHeaders(list) {
            var map = {}, out = [];
            $.each(list, function (_, r) {
                var k = periodKey(r.PeriodFrom, r.PeriodTo) + "|" + r.SupplierId;
                var h = map[k];
                if (!h) {
                    h = map[k] = { Key: k, PeriodFrom: r.PeriodFrom, PeriodTo: r.PeriodTo, SupplierId: r.SupplierId, SupplierCode: r.SupplierCode,
                        SupplierName: r.SupplierName, Items: [], Currencies: [], Up: 0, Down: 0, NeedRemark: 0, Status: r.Status, ModifiedBy: null, ModifiedDate: null };
                    out.push(h);
                }
                h.Items.push(r);
                if (h.Currencies.indexOf(r.Currency) < 0) h.Currencies.push(r.Currency);
                if (r.MovementPct > 0) h.Up++; else if (r.MovementPct < 0) h.Down++;
                if (r.NeedRemark) h.NeedRemark++;
                if (!h.ModifiedDate || moment(r.ModifiedDate).isAfter(h.ModifiedDate)) { h.ModifiedDate = r.ModifiedDate; h.ModifiedBy = r.ModifiedBy; }
            });
            $.each(out, function (_, h) {
                var pcts = $.map(h.Items, function (r) { return r.MovementPct; });
                h.ItemCount = h.Items.length;
                h.Currency = h.Currencies.sort().join(", ");
                h.AvgPct = avg(pcts);
                h.MaxPct = pcts.length ? Math.max.apply(null, pcts) : null;
                h.MinPct = pcts.length ? Math.min.apply(null, pcts) : null;
            });
            return out;
        }

        function loadSaved() {
            HMA.post(urls.saved, {}, function (result) {
                savedRows = result.data || [];
                savedHeaders = buildHeaders(savedRows);
                savedGrid.option("dataSource", savedHeaders);
                $("#savedCount").text(savedHeaders.length || "");
                if (detailHeader && $("#modalDetail").is(":visible")) {
                    var h = $.grep(savedHeaders, function (x) { return x.Key === detailHeader.Key; })[0];
                    if (h) showDetail(h); else $("#modalDetail").modal("hide");
                }
            }, { successMessage: false });
        }

        function buildSavedGrid() {
            var columns = [
                HMA.col.rowNumber(),
                {
                    name: "actions", caption: "Action", width: 70, alignment: "center", allowFiltering: false, allowSorting: false, allowResizing: false,
                    fixed: true, fixedPosition: "left",
                    cellTemplate: function (c, o) {
                        $("<button type='button' class='np-kebab' title='{{ $edit ? 'View, edit or delete' : 'View detail' }}'><i class='fa fa-ellipsis-v'></i></button>")
                            .on("click", function (ev) { ev.stopPropagation(); openRowMenu(this, o.data); })
                            .appendTo(c);
                    }
                },
                { dataField: "PeriodFrom", caption: "Period From", dataType: "date", format: "dd-MM-yyyy", sortOrder: "desc", sortIndex: 0 },
                { dataField: "PeriodTo", caption: "Period To", dataType: "date", format: "dd-MM-yyyy" },
                { dataField: "SupplierCode", caption: "Supp. Code", sortOrder: "asc", sortIndex: 1 },
                { dataField: "SupplierName", caption: "Supplier Name" },
                { dataField: "Currency", caption: "Curr", alignment: "center" },
                { dataField: "ItemCount", caption: "Items", dataType: "number", alignment: "center" },
                {
                    caption: "Up / Down", alignment: "center", allowFiltering: false, allowSorting: false,
                    cellTemplate: function (c, o) {
                        c.append($("<span class='apr-up'>").html("<i class='fa fa-arrow-up'></i> " + o.data.Up), " &nbsp; ",
                            $("<span class='apr-down'>").html("<i class='fa fa-arrow-down'></i> " + o.data.Down));
                    }
                },
                { dataField: "AvgPct", caption: "Avg %", dataType: "number", cellTemplate: function (c, o) { c.append(AprStatus.pct(o.value)); } },
                { dataField: "MaxPct", caption: "Max %", dataType: "number", cellTemplate: function (c, o) { c.append(AprStatus.pct(o.value)); } },
                {
                    dataField: "NeedRemark", caption: "Need Remark", dataType: "number", alignment: "center",
                    cellTemplate: function (c, o) {
                        if (o.value) c.append($("<span class='np-cell-need'>").html("<i class='fa fa-pencil'></i> " + o.value));
                        else c.text("-");
                    }
                },
                { dataField: "Status", caption: "Status", alignment: "center", cellTemplate: function (c, o) { c.append(AprStatus.badge(o.value)); } },
                { dataField: "ModifiedBy", caption: "Modified By" },
                { dataField: "ModifiedDate", caption: "Modified Date", dataType: "datetime", format: "dd-MM-yyyy HH:mm" }
            ];

            savedGrid = $("#savedGrid").addClass("sg-grid").dxDataGrid($.extend(HMA.filterDefaults(), {
                dataSource: savedHeaders,
                keyExpr: "Key",
                showBorders: true,
                showRowLines: true,
                showColumnLines: true,
                columnAutoWidth: true,
                allowColumnResizing: true,
                hoverStateEnabled: true,
                height: HMA.gridHeight("#savedGrid"),
                noDataText: canEdit ? "No new price saved yet. Click Set New Price to create one." : "No new price saved yet.",
                paging: { pageSize: 50 },
                pager: { visible: true, displayMode: "compact", showPageSizeSelector: true, allowedPageSizes: [25, 50, 100], showInfo: true, showNavigationButtons: true },
                columns: $.map(columns, HMA.relaxWidth),
                onRowClick: function (e) { if (e.rowType === "data" && !(e.column && e.column.name === "actions")) showDetail(e.data); }
            })).dxDataGrid("instance");
            HMA.attachFilterToggle(savedGrid, "#savedGrid");
        }

        // ---------- Menu aksi baris (⋮) ----------
        var $menu = $("<div class='np-menu' role='menu'>").appendTo(document.body);
        function closeRowMenu() { $menu.hide(); $(".np-kebab.open").removeClass("open"); }

        function openRowMenu(btn, h) {
            var wasOpen = $(btn).hasClass("open");
            closeRowMenu();
            if (wasOpen) return;
            var draft = h.Status === "DRAFT";
            var item = function (icon, text, fn, cls, disabled, title) {
                return $("<button type='button'>").addClass(cls || "").prop("disabled", !!disabled).attr("title", title || "")
                    .append($("<i class='fa'>").addClass(icon), document.createTextNode(text))
                    .on("click", function () { closeRowMenu(); fn(); });
            };
            $menu.empty().append(item("fa-eye", "View detail", function () { showDetail(h); }));
            if (canEdit) {
                $menu.append(
                    item("fa-pencil", "Edit", function () { openInWorksheet(h); }),
                    $("<hr>"),
                    item("fa-trash", "Delete", function () { deleteSet(h); }, "danger", !draft, draft ? "" : "Only Draft new prices can be deleted"));
            } else {
                $menu.append(item("fa-table", "Open in worksheet", function () { openInWorksheet(h); }));
            }
            $(btn).addClass("open");
            var r = btn.getBoundingClientRect();
            var top = r.bottom + window.scrollY + 4;
            if (r.bottom + $menu.outerHeight() + 8 > window.innerHeight) top = r.top + window.scrollY - $menu.outerHeight() - 4;
            $menu.css({ top: top, left: r.left + window.scrollX }).show();
        }
        $(document).on("click", function (e) { if (!$(e.target).closest(".np-menu, .np-kebab").length) closeRowMenu(); })
            .on("keydown", function (e) { if (e.key === "Escape") closeRowMenu(); });
        $(window).on("resize", closeRowMenu);

        function deleteSet(h) {
            showConfirmMesg("Delete " + h.ItemCount + " new price(s) of " + h.SupplierCode + " - " + h.SupplierName
                + " for period " + dmy(h.PeriodFrom) + " - " + dmy(h.PeriodTo) + "?", function () {
                HMA.post(urls.remove, { dateFrom: h.PeriodFrom, dateTo: h.PeriodTo, supplierId: h.SupplierId }, function () {
                    if (detailHeader && detailHeader.Key === h.Key) $("#modalDetail").modal("hide");
                    loadSaved();
                    // Lembar kerja periode yang sama ikut di-refresh (kalau tidak ada perubahan yang belum disimpan).
                    if (loaded && loaded.dateFrom === h.PeriodFrom && loaded.dateTo === h.PeriodTo && dirtyCount() === 0) load(true);
                });
            }, "Delete New Price", "Delete", "Cancel");
        }

        // ---------- Popup detail ----------
        function showDetail(h) {
            detailHeader = h;
            $("#detailTitle").text(h.SupplierCode + " - " + h.SupplierName);
            $("#detailSub").text("New price period " + dmy(h.PeriodFrom) + " - " + dmy(h.PeriodTo));

            if (!detailGrid) {
                detailGrid = $("#detailGrid").addClass("sg-grid").dxDataGrid($.extend(HMA.filterDefaults(), {
                    keyExpr: "Key",
                    showBorders: true,
                    showRowLines: true,
                    showColumnLines: true,
                    columnAutoWidth: true,
                    allowColumnResizing: true,
                    height: 420,
                    paging: { enabled: false },
                    scrolling: { mode: "virtual" },
                    columns: $.map([
                        { dataField: "ItemCode", caption: "Item Code", sortOrder: "asc" },
                        { dataField: "ItemName", caption: "Item Name" },
                        { dataField: "Unit", caption: "Unit", alignment: "center" },
                        { dataField: "Currency", caption: "Curr", alignment: "center" },
                        { dataField: "CurrentPrice", caption: "Current Price", dataType: "number", format: "#,##0.##" },
                        { dataField: "CurrentTcFrom", caption: "Current TC From", dataType: "date", format: "dd-MM-yyyy", alignment: "right" },
                        { dataField: "CurrentTcTo", caption: "Current TC To", dataType: "date", format: "dd-MM-yyyy", alignment: "right" },
                        {
                            dataField: "NewPrice", caption: "New Price", dataType: "number", format: "#,##0.##",
                            cellTemplate: function (c, o) { c.append($("<b>").text(fmt(o.value))); }
                        },
                        { dataField: "Movement", caption: "Movement", dataType: "number", format: "#,##0.##" },
                        { dataField: "MovementPct", caption: "%", dataType: "number", cellTemplate: function (c, o) { c.append(AprStatus.pct(o.value)); } },
                        {
                            dataField: "Remark", caption: "Remark", width: 200,
                            cellTemplate: function (c, o) {
                                if (o.data.NeedRemark) c.append($("<span class='np-cell-need'><i class='fa fa-pencil'></i> required</span>"));
                                else c.text(o.value || "");
                            }
                        },
                        { dataField: "Quotation", caption: "Quotation", visible: false },
                        { dataField: "ModifiedBy", caption: "Modified By" },
                        { dataField: "ModifiedDate", caption: "Modified Date", dataType: "datetime", format: "dd-MM-yyyy HH:mm" }
                    ], HMA.relaxWidth),
                    summary: {
                        totalItems: [
                            { column: "ItemCode", summaryType: "count", displayFormat: "{0} item(s)" },
                            { column: "MovementPct", summaryType: "avg", valueFormat: "#,##0.00", displayFormat: "avg {0}%" }
                        ]
                    }
                })).dxDataGrid("instance");
                $("#modalDetail").on("shown.bs.modal", function () { detailGrid.updateDimensions(); });
            }
            detailGrid.clearFilter();
            detailGrid.option("dataSource", h.Items);
            if (!$("#modalDetail").is(":visible")) $("#modalDetail").modal("show");
        }

        // Buka periode + supplier baris tersimpan di tab Set New Price.
        function openInWorksheet(r) {
            showPane("paneWork", true);
            setFilters(r.PeriodFrom, r.PeriodTo, { id: r.SupplierId, text: supplierLabel(r) });
        }

        // ---------- Tab ----------
        function showPane(id, skipInit) {
            $(".np-tab").removeClass("active").filter("[data-pane='" + id + "']").addClass("active");
            $(".np-pane").hide();
            $("#" + id).show();
            var g = id === "paneWork" ? grid : savedGrid;
            g.option("height", HMA.gridHeight(id === "paneWork" ? "#datagrid" : "#savedGrid"));
            g.updateDimensions();
            if (id === "paneWork" && !workReady) {
                workReady = true;
                if (skipInit) return;
                // Default: periode tersimpan terbaru; kalau belum ada, 6 bulan mulai bulan depan (sama dengan default APR).
                if (savedRows.length) {
                    setFilters(savedRows[0].PeriodFrom, savedRows[0].PeriodTo, null);
                } else {
                    var start = moment().add(1, "month").startOf("month");
                    setFilters(start.format("YYYY-MM-DD"), start.clone().add(5, "month").endOf("month").format("YYYY-MM-DD"), null);
                }
            }
        }

        // ---------- Tab Set New Price ----------
        // Ubah filter lalu langsung load (supplier null = semua supplier).
        var settingFilters = false;
        function setFilters(from, to, supplier) {
            settingFilters = true;
            $("#filterFrom").val(from);
            $("#filterTo").val(to);
            HMA.setSelect2Value("#filterSupplier", supplier ? supplier.id : null, supplier ? supplier.text : "");
            settingFilters = false;
            load(false);
        }

        function refreshState() {
            var n = dirtyCount();
            $("#btn_save").prop("disabled", n === 0).html("<i class='fa fa-save'></i> Save" + (n ? " (" + n + ")" : ""));
            var selected = grid ? grid.getSelectedRowKeys().length : 0;
            $("#btn_adjust, #btn_clear").prop("disabled", selected === 0);
        }

        function render() {
            supplierStats = {};
            $.each(rows, function (_, r) {
                var s = supplierStats[supplierLabel(r)] = supplierStats[supplierLabel(r)] || { total: 0, withNew: 0, dirty: 0 };
                s.total++;
                if (r.NewPrice !== null) s.withNew++;
                if (dirty[r.Key]) s.dirty++;
            });
            // Semua supplier: dikelompokkan per supplier supaya progres tiap supplier terlihat. Satu supplier: daftar biasa.
            var groupIndex = loaded && !loaded.supplierId ? 0 : undefined;
            if (grid.columnOption("SupplierGroup", "groupIndex") !== groupIndex) grid.columnOption("SupplierGroup", "groupIndex", groupIndex);
            var onlyNew = $("#filterOnlyNew").is(":checked");
            grid.option("dataSource", onlyNew ? $.grep(rows, function (r) { return r.NewPrice !== null || dirty[r.Key]; }) : rows);
            refreshState();
        }

        function load(force) {
            var f = filters();
            if (!f.dateFrom || !f.dateTo) return;
            if (f.dateFrom > f.dateTo) { showErrorMesgGrowl("Period from must not be later than period to."); return; }

            var go = function () {
                HMA.post(urls.search, f, function (result) {
                    rows = $.map(result.data, calc);
                    dirty = {};
                    loaded = f;
                    grid.clearSelection();
                    render();
                }, { successMessage: false });
            };
            if (!force && dirtyCount() > 0) {
                showConfirmMesg(dirtyCount() + " unsaved change(s) will be lost. Continue?", go, "Confirmation", "Yes", "No", function () {
                    // Batal: kembalikan filter ke data yang sedang tampil.
                    settingFilters = true;
                    $("#filterFrom").val(loaded.dateFrom);
                    $("#filterTo").val(loaded.dateTo);
                    if (String(filters().supplierId) !== String(loaded.supplierId)) {
                        var r = $.grep(rows, function (x) { return String(x.SupplierId) === String(loaded.supplierId); })[0];
                        HMA.setSelect2Value("#filterSupplier", loaded.supplierId || null, r ? supplierLabel(r) : "");
                    }
                    settingFilters = false;
                });
            } else {
                go();
            }
        }

        function markDirty(r) {
            calc(r);
            dirty[r.Key] = true;
            r.Status = "Unsaved";
        }

        function save() {
            if (!loaded || dirtyCount() === 0) return;
            grid.saveEditData();
            var payload = $.map($.grep(rows, function (r) { return dirty[r.Key]; }), function (r) {
                return { ItemCode: r.ItemCode, SupplierId: r.SupplierId, NewPrice: r.NewPrice === null ? "" : r.NewPrice, Remark: r.Remark || "" };
            });
            HMA.post(urls.save, { dateFrom: loaded.dateFrom, dateTo: loaded.dateTo, rows: JSON.stringify(payload) }, function () {
                dirty = {};
                loadSaved();
                load(true);
            });
        }

        function buildGrid() {
            var columns = [
                {
                    dataField: "SupplierGroup", caption: "Supplier", allowEditing: false, visible: false, showWhenGrouped: false,
                    calculateCellValue: supplierLabel,
                    groupCellTemplate: function (c, o) {
                        var s = supplierStats[o.value] || { total: 0, withNew: 0, dirty: 0 };
                        c.addClass("np-group").append($("<b>").text(o.value),
                            $("<span>").text(s.withNew + " / " + s.total + " with new price" + (s.dirty ? " · " + s.dirty + " unsaved" : "")));
                    }
                },
                { dataField: "ItemCode", caption: "Item Code", allowEditing: false, fixed: true, fixedPosition: "left" },
                { dataField: "ItemName", caption: "Item Name", allowEditing: false },
                { dataField: "Unit", caption: "Unit", allowEditing: false, alignment: "center" },
                { dataField: "SupplierCode", caption: "Supp. Code", allowEditing: false },
                { dataField: "SupplierName", caption: "Supplier Name", allowEditing: false },
                { dataField: "PlantCode", caption: "Plant", allowEditing: false, alignment: "center", visible: false },
                { dataField: "Currency", caption: "Curr", allowEditing: false, alignment: "center" },
                {
                    dataField: "CurrentPrice", caption: "Current Price", dataType: "number", format: "#,##0.##", allowEditing: false,
                    cellTemplate: function (c, o) {
                        c.text(fmt(o.value));
                        if (o.data.NoValidPrice) {
                            c.append($("<i class='fa fa-exclamation-triangle np-cell-warn'>").attr("title", "No valid price at the start of the period anymore; saved with current price " + fmt(o.data.SavedCurrentPrice) + ". Clear or keep this new price."));
                        } else if (o.data.CurrentChanged) {
                            c.append($("<i class='fa fa-exclamation-triangle np-cell-warn'>").attr("title", "Saved with current price " + fmt(o.data.SavedCurrentPrice) + "; price master is now " + fmt(o.value) + ". Save again to update."));
                        }
                    }
                },
                { dataField: "CurrentTcFrom", caption: "TC From", dataType: "date", format: "dd-MM-yyyy", allowEditing: false, alignment: "right" },
                { dataField: "CurrentTcTo", caption: "TC To", dataType: "date", format: "dd-MM-yyyy", allowEditing: false, alignment: "right" },
                { dataField: "Quotation", caption: "Quotation", allowEditing: false, visible: false },
                {
                    dataField: "NewPrice", caption: "New Price", dataType: "number", format: "#,##0.##", cssClass: canEdit ? "np-edit-col" : "",
                    editorOptions: { min: 0 },
                    cellTemplate: function (c, o) { c.append($("<b>").text(fmt(o.value))); }
                },
                { dataField: "Movement", caption: "Movement", dataType: "number", format: "#,##0.##", allowEditing: false },
                {
                    dataField: "MovementPct", caption: "%", dataType: "number", allowEditing: false,
                    cellTemplate: function (c, o) { c.append(AprStatus.pct(o.value)); }
                },
                {
                    dataField: "Remark", caption: "Remark", width: 200, cssClass: canEdit ? "np-edit-col" : "",
                    editorOptions: { maxLength: 255 },
                    cellTemplate: function (c, o) {
                        if (o.data.NeedRemark) c.append($("<span class='np-cell-need'><i class='fa fa-pencil'></i> required</span>"));
                        else c.text(o.value || "");
                    }
                },
                {
                    dataField: "Status", caption: "Status", allowEditing: false, alignment: "center",
                    calculateCellValue: function (r) { return r.Status || "Not set"; },
                    lookup: { dataSource: ["Not set", "Draft", "Unsaved"] }
                },
                { dataField: "ModifiedBy", caption: "Modified By", allowEditing: false, visible: false },
                { dataField: "ModifiedDate", caption: "Modified Date", dataType: "datetime", format: "dd-MM-yyyy HH:mm", allowEditing: false, visible: false }
            ];

            grid = $("#datagrid").addClass("sg-grid").dxDataGrid($.extend(HMA.filterDefaults(), {
                dataSource: rows,
                keyExpr: "Key",
                showBorders: true,
                showRowLines: true,
                showColumnLines: true,
                columnAutoWidth: true,
                allowColumnResizing: true,
                columnFixing: { enabled: true },
                height: HMA.gridHeight("#datagrid"),
                noDataText: "No item with a valid price at the start of this period.",
                grouping: { autoExpandAll: true, allowCollapsing: true },
                selection: canEdit ? { mode: "multiple", showCheckBoxesMode: "always", allowSelectAll: true } : { mode: "none" },
                editing: { mode: "cell", allowUpdating: canEdit },
                paging: { pageSize: 50 },
                pager: { visible: true, displayMode: "compact", showPageSizeSelector: true, allowedPageSizes: [25, 50, 100, 500], showInfo: true, showNavigationButtons: true },
                columns: $.map(columns, HMA.relaxWidth),
                onEditorPreparing: function (e) {
                    // Item tanpa harga Valid di awal periode tidak bisa diberi new price baru (hanya bisa dikosongkan).
                    if (e.parentType === "dataRow" && e.dataField === "NewPrice" && e.row.data.NoValidPrice) e.cancel = true;
                },
                onRowUpdated: function (e) { markDirty(e.data); render(); },
                onRowPrepared: function (e) { if (e.rowType === "data" && dirty[e.key]) e.rowElement.addClass("np-dirty"); },
                onSelectionChanged: refreshState
            })).dxDataGrid("instance");
            HMA.attachFilterToggle(grid, "#datagrid");
            $(window).on("resize", function () {
                if ($("#paneWork").is(":visible")) grid.option("height", HMA.gridHeight("#datagrid"));
                if (savedGrid && $("#paneSaved").is(":visible")) savedGrid.option("height", HMA.gridHeight("#savedGrid"));
            });
        }

        function selectedRows() {
            var keys = grid.getSelectedRowKeys();
            return $.grep(rows, function (r) { return keys.indexOf(r.Key) >= 0; });
        }

        $(function () {
            buildGrid();
            HMA.select2("#filterSupplier", urls.supplierLookup, "All suppliers");

            buildSavedGrid();
            loadSaved();

            $(".np-tab").on("click", function () { showPane($(this).data("pane")); });
            $("#btn_savedAdd").on("click", function () { showPane("paneWork"); });
            $("#btn_refresh").on("click", loadSaved);
            $("#btn_detailEdit").on("click", function () {
                var h = detailHeader;
                $("#modalDetail").modal("hide");
                if (h) openInWorksheet(h);
            });
            $("#btn_savedExport").on("click", function () { window.location.href = urls.savedExcel; });

            // Filter berubah = langsung load (tanpa tombol Load).
            $("#filterFrom, #filterTo").on("change", function () { if (!settingFilters) load(false); });
            $("#filterSupplier").on("change", function () { if (!settingFilters) load(false); });
            $("#filterOnlyNew").on("change", function () { if (loaded) render(); });
            $("#btn_workRefresh").on("click", function () { if (loaded) load(false); });
            $("#btn_save").on("click", function () {
                showConfirmMesg("Save " + dirtyCount() + " change(s)?", save, "Confirmation");
            });

            $("#btn_adjust").on("click", function () {
                $("#adjustSub").text(selectedRows().length + " selected item(s)");
                $("#adjustPct").val("");
                $("#modalAdjust").modal("show").one("shown.bs.modal", function () { $("#adjustPct").focus(); });
            });
            $("#btn_adjustOk").on("click", function () {
                var pct = num($("#adjustPct").val());
                if (pct === null) { showErrorMesgGrowl("Please fill the percentage."); return; }
                var skipped = 0;
                $.each(selectedRows(), function (_, r) {
                    if (r.NoValidPrice || !(r.CurrentPrice > 0)) { skipped++; return; }
                    var dec = r.Currency === "IDR" ? 0 : 2, f = Math.pow(10, dec);
                    r.NewPrice = Math.round(r.CurrentPrice * (1 + pct / 100) * f) / f;
                    markDirty(r);
                });
                $("#modalAdjust").modal("hide");
                render();
                if (skipped) showErrorMesgGrowl(skipped + " item(s) skipped: no valid current price.");
            });

            $("#btn_clear").on("click", function () {
                var list = $.grep(selectedRows(), function (r) { return r.NewPrice !== null; });
                if (!list.length) { showErrorMesgGrowl("The selected items have no new price."); return; }
                showConfirmMesg("Clear the new price of " + list.length + " item(s)? It is removed when you click Save.", function () {
                    $.each(list, function (_, r) { r.NewPrice = null; markDirty(r); });
                    render();
                }, "Confirmation");
            });

            $("#btn_export").on("click", function () {
                if (!loaded) { showErrorMesgGrowl("Please load the worksheet first."); return; }
                window.location.href = urls.exportExcel + "?" + $.param(loaded);
            });

            $("#btn_upload").on("click", function () {
                if (!loaded) { showErrorMesgGrowl("Please load the worksheet first."); return; }
                if (dirtyCount() > 0) { showErrorMesgGrowl("Please save or reload your unsaved changes first."); return; }
                $("#uploadSub").text("Period " + moment(loaded.dateFrom).format("DD-MM-YYYY") + " - " + moment(loaded.dateTo).format("DD-MM-YYYY"));
                $("#uploadFile").val("");
                $("#modalUpload").modal("show");
            });
            $("#btn_doUpload").on("click", function () {
                var file = $("#uploadFile")[0].files[0];
                if (!file) { showErrorMesgGrowl("Please choose a file to upload."); return; }
                var form = new FormData();
                form.append("file", file);
                form.append("dateFrom", loaded.dateFrom);
                form.append("dateTo", loaded.dateTo);
                popUpProgressShow();
                $.ajax({ type: "POST", url: urls.upload, data: form, processData: false, contentType: false, dataType: "json" })
                    .done(function (result) {
                        popUpProgressHide();
                        (result.status === true ? showSuccessMesgGrowl : showErrorMesgGrowl)(HMA.formatMessage(result.message));
                        if (result.status === true) { $("#modalUpload").modal("hide"); loadSaved(); load(true); }
                    })
                    .fail(function () { popUpProgressHide(); showErrorMesgGrowl("Upload failed. Please try again."); });
            });

            $(window).on("beforeunload", function () {
                if (dirtyCount() > 0) return "You have unsaved changes.";
            });
        });
    })();
</script>
@include('apr._status')
@endsection
