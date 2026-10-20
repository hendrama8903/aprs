<style>
    .item-pair { display: flex; gap: 8px; }
    .item-pair > .form-control { flex: 1 1 0; min-width: 0; }
</style>
<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js);
    // Upload (file export OGS) ditangani partials/ogs-upload.
    var gPage = HMA.crudPage({
        entity: "Item",
        key: "ItemId",
        keyParam: "itemId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Item/Search') }}",
            getByKey: "{{ url('/Item/GetByKey') }}",
            save: "{{ url('/Item/AddEdit') }}",
            remove: "{{ url('/Item/DeleteMultiple') }}",
            exportExcel: "{{ url('/Item/DownloadExcel') }}"
        },
        columns: [
            { dataField: "ItemCode", caption: "Item Code", fixed: true, fixedPosition: "left" },
            { dataField: "ItemName", caption: "Item Name" },
            { dataField: "Unit", caption: "Unit", width: 80, alignment: "center" },
            { dataField: "Category", caption: "Category", lookup: { dataSource: @json($categories) } },
            { dataField: "ToolNumber", caption: "Tool Number", visible: false },
            { dataField: "DrawingNumber", caption: "Drawing Number", visible: false },
            HMA.col.date("TcFrom", "TC From"),
            HMA.col.date("TcTo", "TC To"),
            { dataField: "Remark", caption: "Remark", visible: false },
            { dataField: "ValidPriceCount", caption: "Valid Price", dataType: "number", alignment: "center", width: 110 }
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditCode").val("").prop("disabled", false);
            $("#addEditName, #addEditUnit, #addEditCategory, #addEditTool, #addEditDrawing, #addEditTcFrom, #addEditTcTo, #addEditRemark").val("");
        },
        fillForm: function (data) {
            $("#addEditId").val(data.ItemId);
            $("#addEditCode").val(data.ItemCode).prop("disabled", true);
            $("#addEditName").val(data.ItemName);
            $("#addEditUnit").val(data.Unit);
            $("#addEditCategory").val(data.Category);
            $("#addEditTool").val(data.ToolNumber);
            $("#addEditDrawing").val(data.DrawingNumber);
            $("#addEditTcFrom").val(data.TcFromValue || "");
            $("#addEditTcTo").val(data.TcToValue || "");
            $("#addEditRemark").val(data.Remark);
        },
        collectForm: function () {
            return {
                ItemId: $("#addEditId").val(),
                ItemCode: $("#addEditCode").val(),
                ItemName: $("#addEditName").val(),
                Unit: $("#addEditUnit").val(),
                Category: $("#addEditCategory").val(),
                ToolNumber: $("#addEditTool").val(),
                DrawingNumber: $("#addEditDrawing").val(),
                TcFrom: $("#addEditTcFrom").val(),
                TcTo: $("#addEditTcTo").val(),
                Remark: $("#addEditRemark").val()
            };
        }
    });

    window.onOgsUploaded = function () { gPage.search(); };
</script>
