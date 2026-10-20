<style>
    .ip-note { font-size: 12px; color: #9A6200; background: #FFF8E6; border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; }
    .ip-pair { display: flex; gap: 8px; }
    .ip-pair > .form-control { flex: 1 1 0; min-width: 0; }
    .ip-pair > .ip-currency { flex: 0 0 90px; }
</style>
<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    // Item & supplier dipilih lewat Select2 saat Add, dikunci (teks) saat Edit.
    var gPage = HMA.crudPage({
        entity: "Price",
        key: "ItemPriceId",
        keyParam: "itemPriceId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/ItemPrice/Search') }}",
            getByKey: "{{ url('/ItemPrice/GetByKey') }}",
            save: "{{ url('/ItemPrice/AddEdit') }}",
            remove: "{{ url('/ItemPrice/DeleteMultiple') }}",
            exportExcel: "{{ url('/ItemPrice/DownloadExcel') }}"
        },
        columns: [
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
            {
                // Status Time Control terhadap hari ini. Semua status tampil; filter lewat ikon di header kolom.
                dataField: "Status", caption: "Status Today", alignment: "center", width: 120,
                lookup: { dataSource: ["Valid", "Expired", "Not yet valid"] },
                cellTemplate: function (container, options) {
                    var $cell = $("<span>").addClass("sg-status");
                    $("<span>").addClass("sg-status-dot").toggleClass("off", options.value !== "Valid").appendTo($cell);
                    $("<span>").text(options.value || "-").appendTo($cell);
                    $cell.appendTo(container);
                }
            },
            { dataField: "Source", caption: "Source", alignment: "center", width: 90, lookup: { dataSource: ["OGS", "Manual"] } },
            { dataField: "OgsInputBy", caption: "OGS Input By", visible: false },
            HMA.col.date("OgsInputDate", "OGS Input Date", true, false),
            { dataField: "OgsUpdateBy", caption: "OGS Update By", visible: false },
            HMA.col.date("OgsUpdateDate", "OGS Update Date", true, false)
        ],
        focusEdit: "#addEditPrice",
        resetForm: function () {
            $("#addEditId").val("");
            HMA.setSelect2Value("#addEditItem", null);
            HMA.setSelect2Value("#addEditSupplier", null);
            $("#addEditItem, #addEditSupplier").next(".select2").show();
            $("#addEditItemText, #addEditSupplierText").hide().val("");
            $("#itemSupplierHint").show();
            $("#addEditPlant, #addEditPrice, #addEditQuotation, #addEditTcFrom, #addEditTcTo").val("");
            $("#addEditCurrency").val("IDR");
        },
        fillForm: function (data) {
            $("#addEditId").val(data.ItemPriceId);
            // Item & supplier tidak bisa diubah saat edit: tampil sebagai teks.
            $("#addEditItem, #addEditSupplier").next(".select2").hide();
            $("#addEditItemText").show().val(data.ItemCode + " - " + data.ItemName);
            $("#addEditSupplierText").show().val(data.SupplierCode + " - " + data.SupplierName);
            $("#itemSupplierHint").hide();
            $("#addEditPlant").val(data.SupplierPlantCode);
            $("#addEditCurrency").val(data.Currency);
            $("#addEditPrice").val(data.Price);
            $("#addEditQuotation").val(data.Quotation);
            $("#addEditTcFrom").val(data.TcFromValue || "");
            $("#addEditTcTo").val(data.TcToValue || "");
        },
        collectForm: function () {
            return {
                ItemPriceId: $("#addEditId").val(),
                ItemId: $("#addEditItem").val() || 0,
                SupplierId: $("#addEditSupplier").val() || 0,
                SupplierPlantCode: $("#addEditPlant").val(),
                Currency: $("#addEditCurrency").val(),
                Price: $("#addEditPrice").val(),
                Quotation: $("#addEditQuotation").val(),
                TcFrom: $("#addEditTcFrom").val(),
                TcTo: $("#addEditTcTo").val()
            };
        }
    });

    window.onOgsUploaded = function () { gPage.search(); };

    $(function () {
        HMA.select2("#addEditItem", "{{ url('/Lookup/Item') }}", "Choose item", "#modalAddEdit");
        HMA.select2("#addEditSupplier", "{{ url('/Lookup/Supplier') }}", "Choose supplier", "#modalAddEdit");
    });
</script>
