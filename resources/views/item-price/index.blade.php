{{-- Price master OGS: read-only, data diganti lewat Import OGS. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_toggleFilters"><i class="fa fa-filter"></i> Filters</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
            </div>
        </div>
        <div class="dp-filter-bar show" id="filterBar">
            <div class="sg-filter" style="min-width: 320px;">
                <label for="filterSupplier">Supplier</label>
                <select class="form-control" id="filterSupplier"></select>
            </div>
            <div class="sg-filter">
                <label for="filterStatus">Active</label>
                <select class="form-control" id="filterStatus">
                    <option value="true">Active only</option>
                    <option value="false">Inactive only</option>
                    <option value="">All</option>
                </select>
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    $(function () {
        function params() {
            return { supplierId: $("#filterSupplier").val() || "", isActive: $("#filterStatus").val() };
        }

        HMA.select2("#filterSupplier", "{{ url('/Lookup/Supplier') }}", "All suppliers");

        var grid = HMA.createGrid("#datagrid", {
            key: "ItemPriceId",
            url: "{{ url('/ItemPrice/Search') }}",
            params: params,
            gridOptions: { selection: { mode: "single" } },
            columns: [
                HMA.col.rowNumber(),
                {
                    dataField: "ItemCode", caption: "Item Code", fixed: true, fixedPosition: "left",
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
                { dataField: "SupplierPlantCode", caption: "Plant", width: 65, alignment: "center" },
                { dataField: "Currency", caption: "Curr", width: 65, alignment: "center" },
                { dataField: "Price", caption: "Price", dataType: "number", format: "#,##0.##", width: 120 },
                { dataField: "Quotation", caption: "Quotation" },
                HMA.col.date("TcFrom", "TC From"),
                HMA.col.date("TcTo", "TC To"),
                { dataField: "OgsInputBy", caption: "OGS Input By", visible: false },
                HMA.col.date("OgsInputDate", "OGS Input Date", true, false),
                { dataField: "OgsUpdateBy", caption: "OGS Update By", visible: false },
                HMA.col.date("OgsUpdateDate", "OGS Update Date", true, false),
                HMA.col.status("IsActive", "Active", "Active", "Inactive")
            ]
        });

        $("#filterSupplier, #filterStatus").on("change", function () { grid.refresh(); });
        $("#btn_refresh").on("click", function () { grid.refresh(); });
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/ItemPrice/DownloadExcel') }}?" + $.param(params());
        });
        $("#btn_toggleFilters").on("click", function () {
            $("#filterBar").toggleClass("show");
            grid.option("height", HMA.gridHeight("#datagrid"));
        });
    });
</script>
@endsection
