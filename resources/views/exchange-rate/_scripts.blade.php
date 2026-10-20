<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Exchange Rate",
        key: "ExchangeRateId",
        keyParam: "exchangeRateId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/ExchangeRate/Search') }}",
            getByKey: "{{ url('/ExchangeRate/GetByKey') }}",
            save: "{{ url('/ExchangeRate/AddEdit') }}",
            remove: "{{ url('/ExchangeRate/DeleteMultiple') }}",
            exportExcel: "{{ url('/ExchangeRate/DownloadExcel') }}"
        },
        columns: [
            { dataField: "Currency", caption: "Currency", width: 100, alignment: "center", lookup: { dataSource: @json($currencies) } },
            { dataField: "Rate", caption: "Rate (IDR)", dataType: "number", format: "#,##0.####", width: 140 },
            HMA.col.date("ValidFrom", "Valid From"),
            HMA.col.date("ValidTo", "Valid To"),
            {
                // Status periode terhadap hari ini. Filter lewat ikon di header kolom.
                dataField: "Status", caption: "Status Today", alignment: "center", width: 120,
                lookup: { dataSource: ["Valid", "Expired", "Not yet valid"] },
                cellTemplate: function (container, options) {
                    var $cell = $("<span>").addClass("sg-status");
                    $("<span>").addClass("sg-status-dot").toggleClass("off", options.value !== "Valid").appendTo($cell);
                    $("<span>").text(options.value || "-").appendTo($cell);
                    $cell.appendTo(container);
                }
            },
            { dataField: "Remark", caption: "Remark" }
        ],
        focusAdd: "#addEditRate",
        focusEdit: "#addEditRate",
        resetForm: function () {
            $("#addEditId, #addEditRate, #addEditValidFrom, #addEditValidTo, #addEditRemark").val("");
            $("#addEditCurrency").val(@json($currencies[0]));
        },
        fillForm: function (data) {
            $("#addEditId").val(data.ExchangeRateId);
            $("#addEditCurrency").val(data.Currency);
            $("#addEditRate").val(data.Rate);
            $("#addEditValidFrom").val(data.ValidFromValue || "");
            $("#addEditValidTo").val(data.ValidToValue || "");
            $("#addEditRemark").val(data.Remark);
        },
        collectForm: function () {
            return {
                ExchangeRateId: $("#addEditId").val(),
                Currency: $("#addEditCurrency").val(),
                Rate: $("#addEditRate").val(),
                ValidFrom: $("#addEditValidFrom").val(),
                ValidTo: $("#addEditValidTo").val(),
                Remark: $("#addEditRemark").val()
            };
        }
    });
</script>
