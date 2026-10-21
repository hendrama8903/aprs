{{-- Audit trail (read-only): perubahan data & aktivitas login. Data diambil per rentang tanggal, filter lain di grid. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="at-filter">
            <div class="at-field">
                <label>Date <span class="text-red">*</span></label>
                <div class="at-range">
                    <input type="date" class="form-control" id="filterFrom" />
                    <span>to</span>
                    <input type="date" class="form-control" id="filterTo" />
                </div>
            </div>
            <div class="at-field">
                <label>Module</label>
                <select class="form-control" id="filterModule">
                    <option value="">All modules</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}">{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="at-field">
                <label>Action</label>
                <select class="form-control" id="filterAction">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="at-field">
                <label>&nbsp;</label>
                <button type="button" class="dp-btn dp-btn-primary" id="btn_load"><i class="fa fa-search"></i> Load</button>
            </div>
        </div>

        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_detail" disabled><i class="fa fa-eye"></i> Detail</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-history"></i></div>
                <div>
                    <div class="dpf-header-title" id="detailTitle">Audit Detail</div>
                    <div class="dpf-header-sub" id="detailSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="at-info" id="detailInfo"></div>
                <table class="at-changes" id="detailChanges">
                    <thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>
                    <tbody></tbody>
                </table>
                <div class="at-empty" id="detailEmpty">No field changes recorded for this activity.</div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .at-filter { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; padding: 14px 16px 4px; }
    .at-field label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
    .at-range { display: flex; align-items: center; gap: 6px; }
    .at-range span { font-size: 12px; color: #6b7280; }
    .at-info { display: grid; grid-template-columns: 110px 1fr; gap: 4px 12px; font-size: 13px; margin-bottom: 14px; }
    .at-info .k { color: #6b7280; }
    .at-changes { width: 100%; border-collapse: collapse; font-size: 13px; }
    .at-changes th, .at-changes td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; white-space: pre-wrap; word-break: break-word; }
    .at-changes th { background: #f9fafb; font-weight: 600; text-align: left; }
    .at-changes td:first-child { width: 22%; font-weight: 600; }
    .at-changes td.old { background: #FEF3F2; }
    .at-changes td.new { background: #F3FAF5; }
    .at-empty { font-size: 13px; color: #6b7280; }
    .at-action { font-weight: 600; font-size: 12px; }
    .at-action.CREATE, .at-action.LOGIN { color: #17824F; }
    .at-action.UPDATE, .at-action.UPLOAD { color: #1D4ED8; }
    .at-action.DELETE, .at-action.LOGIN-FAILED { color: #B42318; }
</style>
<script type="text/javascript">
    $(function () {
        var urls = {
            search: "{{ url('/AuditTrail/Search') }}",
            getByKey: "{{ url('/AuditTrail/GetByKey') }}",
            exportExcel: "{{ url('/AuditTrail/DownloadExcel') }}"
        };

        function filters() {
            return {
                dateFrom: $("#filterFrom").val(),
                dateTo: $("#filterTo").val(),
                module: $("#filterModule").val(),
                action: $("#filterAction").val()
            };
        }

        function showDetail(id) {
            HMA.post(urls.getByKey, { auditId: id }, function (result) {
                var d = result.data;
                $("#detailTitle").text(d.Action + " - " + d.Module);
                $("#detailSub").text(d.RecordLabel || "");
                var $info = $("#detailInfo").empty();
                $.each([
                    ["Date", moment(d.LogDate).format("DD-MM-YYYY HH:mm:ss")],
                    ["User", d.Username || "-"],
                    ["Record", (d.RecordLabel || "-") + (d.RecordKey ? " (ID " + d.RecordKey + ")" : "")],
                    ["Description", d.Description || "-"],
                    ["IP Address", d.IpAddress || "-"]
                ], function (_, r) {
                    $("<div class='k'>").text(r[0]).appendTo($info);
                    $("<div>").text(r[1]).appendTo($info);
                });
                var $body = $("#detailChanges tbody").empty();
                $.each(d.Changes, function (_, c) {
                    $("<tr>")
                        .append($("<td>").text(c.Field))
                        .append($("<td class='old'>").text(c.Old === null ? "-" : c.Old))
                        .append($("<td class='new'>").text(c.New === null ? "-" : c.New))
                        .appendTo($body);
                });
                $("#detailChanges").toggle(d.Changes.length > 0);
                $("#detailEmpty").toggle(d.Changes.length === 0);
                $("#modalDetail").modal("show");
            }, { successMessage: false });
        }

        // Default: 7 hari terakhir.
        $("#filterTo").val(moment().format("YYYY-MM-DD"));
        $("#filterFrom").val(moment().subtract(6, "day").format("YYYY-MM-DD"));

        var grid = HMA.createGrid("#datagrid", {
            key: "AuditId",
            url: urls.search,
            params: filters,
            gridOptions: {
                selection: { mode: "single" },
                onRowDblClick: function (e) { showDetail(e.data.AuditId); }
            },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "LogDate", caption: "Date", dataType: "datetime", format: "dd-MM-yyyy HH:mm:ss", alignment: "right", width: 150, sortOrder: "desc" },
                { dataField: "Username", caption: "User", width: 120 },
                {
                    dataField: "Action", caption: "Action", width: 115, alignment: "center",
                    cellTemplate: function (container, options) {
                        $("<span class='at-action'>").addClass(String(options.value).replace(" ", "-")).text(options.value).appendTo(container);
                    }
                },
                { dataField: "Module", caption: "Module", width: 150 },
                { dataField: "RecordLabel", caption: "Record", width: 200 },
                { dataField: "Description", caption: "Description" },
                { dataField: "ChangeCount", caption: "Fields", dataType: "number", alignment: "center", width: 70 },
                { dataField: "IpAddress", caption: "IP Address", width: 120, visible: false }
            ],
            onSelectionChanged: function (rows) { $("#btn_detail").prop("disabled", rows.length !== 1); }
        });

        function load() {
            var f = filters();
            if (!f.dateFrom || !f.dateTo) { showErrorMesgGrowl("Please fill the date range."); return; }
            if (f.dateFrom > f.dateTo) { showErrorMesgGrowl("Date from must not be later than date to."); return; }
            grid.clearSelection();
            grid.refresh();
        }

        $("#btn_load, #btn_refresh").on("click", load);
        $("#btn_detail").on("click", function () {
            var rows = grid.getSelectedRowsData();
            if (rows.length === 1) showDetail(rows[0].AuditId);
        });
        $("#btn_export").on("click", function () { window.location.href = urls.exportExcel + "?" + $.param(filters()); });
    });
</script>
@endsection
