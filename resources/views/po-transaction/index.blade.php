{{-- Transaksi PO OGS: read-only, diperbarui lewat Upload (upsert per PO NO + PO LINE). --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($upload)
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_upload"><i class="fa fa-upload"></i> Upload</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                @include('partials.ogs-last-upload')
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('partials.ogs-upload', ['title' => 'PO Transaction', 'baseUrl' => url('/PoTransaction'), 'hint' => 'PO lines are matched by PO No + PO Line: existing lines are updated, new lines are added. Lines not in the file are kept.'])
@endsection

@section('scripts')
<script type="text/javascript">
    $(function () {
        var grid = HMA.createGrid("#datagrid", {
            key: "PoTransactionId",
            url: "{{ url('/PoTransaction/Search') }}",
            gridOptions: { selection: { mode: "single" } },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "PoNo", caption: "PO No", fixed: true, fixedPosition: "left" },
                { dataField: "PoLine", caption: "Line", dataType: "number", alignment: "center", width: 60 },
                HMA.col.date("PoDate", "PO Date"),
                {
                    dataField: "ItemCode", caption: "Item Code",
                    cellTemplate: function (container, options) {
                        container.text(options.value);
                        if (!options.data.InItemMaster) {
                            $("<i class='fa fa-exclamation-triangle text-yellow' title='Not found in item master'>").css("margin-left", "6px").appendTo(container);
                        }
                    }
                },
                { dataField: "ItemName", caption: "Item Name" },
                { dataField: "Unit", caption: "Unit", width: 70, alignment: "center" },
                { dataField: "SupplierCode", caption: "Supp. Code", width: 95 },
                { dataField: "SupplierName", caption: "Supplier Name" },
                { dataField: "Currency", caption: "Curr", width: 65, alignment: "center" },
                { dataField: "Qty", caption: "Qty", dataType: "number", format: "#,##0.##" },
                { dataField: "UnitPrice", caption: "Unit Price", dataType: "number", format: "#,##0.##" },
                { dataField: "Amount", caption: "Amount", dataType: "number", format: "#,##0.##" }
            ]
        });

        window.onOgsUploaded = function () { grid.refresh(); };
        $("#btn_refresh").on("click", function () { grid.refresh(); });
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/PoTransaction/DownloadExcel') }}";
        });
    });
</script>
@stack('ogs-upload-scripts')
@endsection
