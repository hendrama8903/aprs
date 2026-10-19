<script type="text/javascript">
    // Halaman master standar: toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    // Untuk master baru cukup ganti entity/key/urls/columns dan 3 fungsi form di bawah.
    var gPage = HMA.crudPage({
        entity: "Department",
        key: "DepartmentId",
        keyParam: "departmentId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Department/Search') }}",
            getByKey: "{{ url('/Department/GetByKey') }}",
            save: "{{ url('/Department/AddEdit') }}",
            remove: "{{ url('/Department/DeleteMultiple') }}",
            exportExcel: "{{ url('/Department/DownloadExcel') }}"
        },
        columns: [
            { dataField: "DepartmentCode", caption: "Department Code", fixed: true, fixedPosition: "left" },
            { dataField: "DepartmentName", caption: "Department Name" },
            { dataField: "UserCount", caption: "Total User", dataType: "number", alignment: "center", width: 110 },
            HMA.col.status()
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditCode").val("").prop("disabled", false);
            $("#addEditName").val("");
            $("#addEditIsActive").prop("checked", true);
        },
        fillForm: function (data) {
            $("#addEditId").val(data.DepartmentId);
            $("#addEditCode").val(data.DepartmentCode).prop("disabled", true);
            $("#addEditName").val(data.DepartmentName);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                DepartmentId: $("#addEditId").val(),
                DepartmentCode: $("#addEditCode").val(),
                DepartmentName: $("#addEditName").val(),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });

</script>
