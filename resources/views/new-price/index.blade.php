{{--
    Set New Price: lembar kerja harga baru per periode. Current price = harga di price master yang Valid di awal periode.
    New Price & Remark diisi langsung di grid (atau Adjust % / Upload), lalu disimpan sekaligus dengan Save.
--}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
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
                <label>Category</label>
                <select class="form-control" id="filterCategory">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="np-field">
                <label>&nbsp;</label>
                <button type="button" class="dp-btn dp-btn-primary" id="btn_load"><i class="fa fa-search"></i> Load</button>
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
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <span class="np-info" id="loadedInfo"></span>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
            </div>
        </div>
        <div class="box-body">
            <div class="np-legend">
                <span><i class="fa fa-pencil text-red"></i> remark required (increase above {{ $threshold }}%)</span>
                <span><i class="fa fa-exclamation-triangle text-yellow"></i> current price changed since saved / no longer valid</span>
                <span><span class="np-swatch"></span> unsaved change</span>
            </div>
            <div id="datagrid"></div>
        </div>
    </div>
</section>

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
    .np-grow { flex: 1 1 260px; min-width: 200px; }
    .np-range { display: flex; align-items: center; gap: 6px; }
    .np-range span { font-size: 12px; color: #6b7280; }
    .np-info { display: inline-block; margin-right: 10px; font-size: 12px; color: #6b7280; }
    .np-legend { display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; color: #6b7280; margin-bottom: 8px; }
    .np-swatch { display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: #FFF4D6; border: 1px solid #F2D27A; vertical-align: -1px; }
    .np-dirty td { background: #FFF4D6 !important; }
    .np-cell-warn { color: #9A6200; margin-left: 4px; }
    .np-cell-need { color: #B42318; }
    .np-edit-col { background: #F5F8FF; }
</style>
<script type="text/javascript">
    (function () {
        var canEdit = @json($edit);
        var threshold = {{ (float) $threshold }};
        var urls = {
            search: "{{ url('/NewPrice/Search') }}",
            save: "{{ url('/NewPrice/Save') }}",
            upload: "{{ url('/NewPrice/Upload') }}",
            exportExcel: "{{ url('/NewPrice/DownloadExcel') }}",
            supplierLookup: "{{ url('/Lookup/Supplier') }}"
        };

        var rows = [];        // baris lembar kerja (format Search)
        var dirty = {};       // Key -> true untuk baris yang belum disimpan
        var loaded = null;    // filter yang sedang ditampilkan: { dateFrom, dateTo, supplierId, category }
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
                supplierId: $("#filterSupplier").val() || "",
                category: $("#filterCategory").val() || ""
            };
        }

        function refreshState() {
            var n = dirtyCount();
            $("#btn_save").prop("disabled", n === 0).html("<i class='fa fa-save'></i> Save" + (n ? " (" + n + ")" : ""));
            var selected = grid ? grid.getSelectedRowKeys().length : 0;
            $("#btn_adjust, #btn_clear").prop("disabled", selected === 0);
        }

        function render() {
            grid.option("dataSource", rows);
            refreshState();
        }

        function load(force) {
            var f = filters();
            if (!f.dateFrom || !f.dateTo) { showErrorMesgGrowl("Please fill the new price period."); return; }
            if (f.dateFrom > f.dateTo) { showErrorMesgGrowl("Period from must not be later than period to."); return; }

            var go = function () {
                HMA.post(urls.search, f, function (result) {
                    rows = $.map(result.data, calc);
                    dirty = {};
                    loaded = f;
                    grid.clearSelection();
                    render();
                    var withNew = $.grep(rows, function (r) { return r.NewPrice !== null; }).length;
                    $("#loadedInfo").text(moment(f.dateFrom).format("DD-MM-YYYY") + " - " + moment(f.dateTo).format("DD-MM-YYYY")
                        + " · " + rows.length + " item(s), " + withNew + " with new price");
                }, { successMessage: false });
            };
            if (!force && dirtyCount() > 0) {
                showConfirmMesg(dirtyCount() + " unsaved change(s) will be lost. Continue?", go, "Confirmation");
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
                load(true);
            });
        }

        function buildGrid() {
            var columns = [
                { dataField: "ItemCode", caption: "Item Code", allowEditing: false, fixed: true, fixedPosition: "left" },
                { dataField: "ItemName", caption: "Item Name", allowEditing: false },
                { dataField: "Unit", caption: "Unit", allowEditing: false, alignment: "center" },
                { dataField: "Category", caption: "Category", allowEditing: false, visible: false },
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
                noDataText: "Choose the new price period and click Load.",
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
            $(window).on("resize", function () { grid.option("height", HMA.gridHeight("#datagrid")); });
        }

        function selectedRows() {
            var keys = grid.getSelectedRowKeys();
            return $.grep(rows, function (r) { return keys.indexOf(r.Key) >= 0; });
        }

        $(function () {
            buildGrid();
            HMA.select2("#filterSupplier", urls.supplierLookup, "All suppliers");

            // Default: periode 6 bulan mulai bulan depan (sama dengan default APR).
            var start = moment().add(1, "month").startOf("month");
            $("#filterFrom").val(start.format("YYYY-MM-DD"));
            $("#filterTo").val(start.clone().add(5, "month").endOf("month").format("YYYY-MM-DD"));

            $("#btn_load").on("click", function () { load(false); });
            $("#btn_refresh").on("click", function () { if (loaded) load(false); });
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
                        if (result.status === true) { $("#modalUpload").modal("hide"); load(true); }
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
