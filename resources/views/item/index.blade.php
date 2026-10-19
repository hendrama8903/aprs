{{-- Item master OGS: read-only, data diperbarui lewat Import OGS. --}}
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
        <div class="dp-filter-bar" id="filterBar">
            <div class="sg-filter">
                <label for="filterCategory">Category</label>
                <select class="form-control" id="filterCategory">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
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
        var grid = HMA.createGrid("#datagrid", {
            key: "ItemId",
            url: "{{ url('/Item/Search') }}",
            params: function () { return { category: $("#filterCategory").val() }; },
            gridOptions: { selection: { mode: "single" } },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "ItemCode", caption: "Item Code", fixed: true, fixedPosition: "left" },
                { dataField: "ItemName", caption: "Item Name" },
                { dataField: "Unit", caption: "Unit", width: 80, alignment: "center" },
                { dataField: "Category", caption: "Category" },
                { dataField: "MinimumOrder", caption: "Min. Order", alignment: "right", visible: false },
                { dataField: "ToolNumber", caption: "Tool Number", visible: false },
                { dataField: "DrawingNumber", caption: "Drawing Number", visible: false },
                HMA.col.date("TcFrom", "TC From"),
                HMA.col.date("TcTo", "TC To"),
                { dataField: "Remark", caption: "Remark", visible: false },
                { dataField: "ActivePriceCount", caption: "Active Price", dataType: "number", alignment: "center", width: 110 }
            ].concat(HMA.col.audit())
        });

        $("#filterCategory").on("change", function () { grid.refresh(); });
        $("#btn_refresh").on("click", function () { grid.refresh(); });
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/Item/DownloadExcel') }}?" + $.param({ category: $("#filterCategory").val() });
        });
        $("#btn_toggleFilters").on("click", function () {
            $("#filterBar").toggleClass("show");
            grid.option("height", HMA.gridHeight("#datagrid"));
        });
    });
</script>
@endsection
