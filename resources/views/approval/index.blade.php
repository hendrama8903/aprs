@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                <ul class="nav nav-pills appr-tabs">
                    <li class="active"><a href="#" data-tab="inbox"><i class="fa fa-inbox"></i> Waiting for Me <span class="badge" id="inboxCount">0</span></a></li>
                    <li><a href="#" data-tab="history"><i class="fa fa-history"></i> My History</a></li>
                </ul>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-primary" id="btn_open" disabled><i class="fa fa-check-square-o"></i> Open / Review</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
        </div>
        <div class="box-body">
            <div id="inboxGrid"></div>
            <div id="historyGrid" style="display:none"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
@include('apr._status')
<style>
    .appr-tabs > li > a { padding: 6px 12px; }
    .appr-tabs .badge { background: #B42318; }
</style>
<script type="text/javascript">
    $(function () {
        var formUrl = "{{ url('/Apr/Form') }}";
        function open(id) { window.location.href = formUrl + "?id=" + id; }
        function docLink(container, options) {
            $("<a>").attr("href", formUrl + "?id=" + options.data.AprId).text(options.value).appendTo(container);
        }

        var inbox = HMA.createGrid("#inboxGrid", {
            key: "AprId",
            url: "{{ url('/Approval/Search') }}",
            onLoaded: function (data) { $("#inboxCount").text(data.length); },
            gridOptions: {
                selection: { mode: "single" },
                noDataText: "Nothing is waiting for your approval.",
                onRowDblClick: function (e) { open(e.data.AprId); }
            },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "DocNo", caption: "Doc No", cellTemplate: docLink },
                HMA.col.date("DocDate", "Doc Date"),
                { dataField: "SupplierName", caption: "Supplier" },
                { caption: "Effective", calculateCellValue: function (r) { return AprStatus.period(r.EffectiveFrom, r.EffectiveTo); } },
                { dataField: "ItemCount", caption: "Items", dataType: "number", alignment: "center", width: 70 },
                { dataField: "TotalNew", caption: "New Amount (IDR)", dataType: "number", format: "#,##0" },
                { dataField: "TotalDiffPct", caption: "Diff %", dataType: "number", width: 80, cellTemplate: function (c, o) { AprStatus.pct(o.value).appendTo(c); } },
                { dataField: "CurrentStep", caption: "Your Step" },
                { dataField: "PreparedBy", caption: "Prepared By" }
            ],
            onSelectionChanged: function (rows) { $("#btn_open").prop("disabled", !rows.length); }
        });

        function activeGrid() { return $("#historyGrid").is(":visible") ? history : inbox; }

        var history = null;
        function showHistory() {
            if (history) { history.refresh(); return; }
            history = HMA.createGrid("#historyGrid", {
                key: "AprApprovalId",
                url: "{{ url('/Approval/History') }}",
                onSelectionChanged: function (rows) { $("#btn_open").prop("disabled", !rows.length); },
                gridOptions: { selection: { mode: "single" }, onRowDblClick: function (e) { open(e.data.AprId); } },
                columns: [
                    HMA.col.rowNumber(),
                    HMA.col.date("ActionDate", "Date", true),
                    { dataField: "DocNo", caption: "Doc No", cellTemplate: docLink },
                    { dataField: "SupplierName", caption: "Supplier" },
                    { dataField: "StepName", caption: "Step" },
                    { dataField: "Action", caption: "Action", width: 90, alignment: "center" },
                    { dataField: "Note", caption: "Note" },
                    { dataField: "Status", caption: "Doc Status", width: 120, alignment: "center", cellTemplate: function (c, o) { AprStatus.badge(o.value).appendTo(c); } }
                ]
            });
        }

        $(".appr-tabs a").on("click", function (e) {
            e.preventDefault();
            var tab = $(this).data("tab");
            $(".appr-tabs li").removeClass("active");
            $(this).parent().addClass("active");
            $("#inboxGrid").toggle(tab === "inbox");
            $("#historyGrid").toggle(tab === "history");
            if (tab === "history") showHistory(); else inbox.refresh();
            activeGrid().clearSelection();
            $("#btn_open").prop("disabled", true);
        });
        $("#btn_open").on("click", function () {
            var row = activeGrid().getSelectedRowsData()[0];
            if (row) open(row.AprId);
        });
        $("#btn_refresh").on("click", function () {
            inbox.refresh();
            if (history) history.refresh();
        });
    });
</script>
@endsection
