@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($add)
                    <a class="dp-btn dp-btn-primary" href="{{ url('/Apr/Form') }}"><i class="fa fa-plus"></i> New APR</a>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_open" disabled><i class="fa fa-folder-open-o"></i> Open</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_printApr" disabled><i class="fa fa-print"></i> Print APR</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_printLetter" disabled><i class="fa fa-download"></i> Print Letter</button>
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
@include('apr._status')
<script type="text/javascript">
    $(function () {
        var formUrl = "{{ url('/Apr/Form') }}";

        var grid = HMA.createGrid("#datagrid", {
            key: "AprId",
            url: "{{ url('/Apr/Search') }}",
            gridOptions: {
                selection: { mode: "single" },
                onRowDblClick: function (e) { window.location.href = formUrl + "?id=" + e.data.AprId; }
            },
            columns: [
                HMA.col.rowNumber(),
                {
                    dataField: "DocNo", caption: "Doc No", fixed: true, fixedPosition: "left",
                    cellTemplate: function (container, options) {
                        $("<a>").attr("href", formUrl + "?id=" + options.data.AprId).text(options.value).appendTo(container);
                    }
                },
                HMA.col.date("DocDate", "Doc Date"),
                { dataField: "SupplierName", caption: "Supplier" },
                { dataField: "PartCategory", caption: "Category", visible: false },
                {
                    caption: "Effective",
                    calculateCellValue: function (r) { return AprStatus.period(r.EffectiveFrom, r.EffectiveTo); }
                },
                { dataField: "ItemCount", caption: "Items", dataType: "number", alignment: "center", width: 70 },
                { dataField: "TotalCurrent", caption: "Current Amount (IDR)", dataType: "number", format: "#,##0", visible: false },
                { dataField: "TotalNew", caption: "New Amount (IDR)", dataType: "number", format: "#,##0" },
                {
                    dataField: "TotalDiffPct", caption: "Diff %", dataType: "number", width: 80,
                    cellTemplate: function (container, options) { AprStatus.pct(options.value).appendTo(container); }
                },
                {
                    dataField: "Status", caption: "Status", width: 130, alignment: "center",
                    lookup: { dataSource: AprStatus.lookup(), valueExpr: "value", displayExpr: "text" },
                    cellTemplate: function (container, options) { AprStatus.badge(options.value).appendTo(container); }
                },
                {
                    caption: "Waiting For",
                    calculateCellValue: function (r) { return r.CurrentStep ? r.CurrentStep + " (" + (r.CurrentApprover || "-") + ")" : ""; }
                },
                { dataField: "ExtDocNo", caption: "Letter No", visible: false },
                { dataField: "PreparedBy", caption: "Prepared By" }
            ].concat(HMA.col.audit()),
            onSelectionChanged: function (rows) {
                var row = rows[0];
                $("#btn_open, #btn_printApr").prop("disabled", !row);
                $("#btn_printLetter").prop("disabled", !row || row.Status !== "ISSUED");
            }
        });

        function selected() { return grid.getSelectedRowsData()[0]; }

        $("#btn_open").on("click", function () {
            var row = selected();
            if (row) window.location.href = formUrl + "?id=" + row.AprId;
        });
        $("#btn_printApr").on("click", function () {
            var row = selected();
            if (row) window.open("{{ url('/Apr/PrintApr') }}?id=" + row.AprId);
        });
        $("#btn_printLetter").on("click", function () {
            var row = selected();
            if (row && row.Status === "ISSUED") window.open("{{ url('/Apr/PrintLetter') }}?id=" + row.AprId);
        });

        $("#btn_refresh").on("click", function () { grid.refresh(); });
    });
</script>
@endsection
