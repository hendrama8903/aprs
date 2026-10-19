@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($add)
                    <a class="dp-btn dp-btn-primary" href="{{ url('/Apr/Form') }}"><i class="fa fa-plus"></i> New APR</a>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_toggleFilters"><i class="fa fa-filter"></i> Filters</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
        </div>
        <div class="dp-filter-bar" id="filterBar">
            <div class="sg-filter">
                <label for="filterStatus">Status</label>
                <select class="form-control" id="filterStatus">
                    <option value="">All</option>
                    <option value="DRAFT">Draft</option>
                    <option value="IN_APPROVAL">In Approval</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="APPROVED">Approved</option>
                    <option value="ISSUED">Issued</option>
                </select>
            </div>
            <div class="sg-filter" style="min-width: 300px;">
                <label for="filterSupplier">Supplier</label>
                <select class="form-control" id="filterSupplier"></select>
            </div>
            <div class="sg-filter">
                <label for="filterDateFrom">Doc Date From</label>
                <input type="date" class="form-control" id="filterDateFrom" />
            </div>
            <div class="sg-filter">
                <label for="filterDateTo">To</label>
                <input type="date" class="form-control" id="filterDateTo" />
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
        var canDelete = @json($delete);
        var formUrl = "{{ url('/Apr/Form') }}";

        function params() {
            return {
                status: $("#filterStatus").val(),
                supplierId: $("#filterSupplier").val() || "",
                dateFrom: $("#filterDateFrom").val(),
                dateTo: $("#filterDateTo").val()
            };
        }

        HMA.select2("#filterSupplier", "{{ url('/Lookup/Supplier') }}", "All suppliers");

        var grid = HMA.createGrid("#datagrid", {
            key: "AprId",
            url: "{{ url('/Apr/Search') }}",
            params: params,
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
                    caption: "Effective", allowFiltering: false,
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
                    dataField: "Status", caption: "Status", width: 120, alignment: "center",
                    cellTemplate: function (container, options) { AprStatus.badge(options.value).appendTo(container); }
                },
                {
                    caption: "Waiting For", allowFiltering: false,
                    calculateCellValue: function (r) { return r.CurrentStep ? r.CurrentStep + " (" + (r.CurrentApprover || "-") + ")" : ""; }
                },
                { dataField: "ExtDocNo", caption: "Letter No", visible: false },
                { dataField: "PreparedBy", caption: "Prepared By" }
            ].concat(HMA.col.audit()).concat([
                HMA.col.actions(function (row) {
                    var items = [
                        { icon: "fa-folder-open-o", label: "Open", onClick: function () { window.location.href = formUrl + "?id=" + row.AprId; } },
                        { icon: "fa-print", label: "Print APR", onClick: function () { window.open("{{ url('/Apr/PrintApr') }}?id=" + row.AprId); } }
                    ];
                    if (row.Status === "ISSUED") {
                        items.push({ icon: "fa-envelope-o", label: "Print Letter", onClick: function () { window.open("{{ url('/Apr/PrintLetter') }}?id=" + row.AprId); } });
                    }
                    return items;
                })
            ])
        });

        $("#filterStatus, #filterSupplier, #filterDateFrom, #filterDateTo").on("change", function () { grid.refresh(); });
        $("#btn_refresh").on("click", function () { grid.refresh(); });
        $("#btn_toggleFilters").on("click", function () {
            $("#filterBar").toggleClass("show");
            grid.option("height", HMA.gridHeight("#datagrid"));
        });
    });
</script>
@endsection
