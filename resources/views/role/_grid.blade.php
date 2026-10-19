<div class="box-body">
    <div id="datagrid"></div>
</div>

<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gGrid = null;

    $(document).ready(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "RoleId",
            url: "{{ url('/Role/Search') }}",
            gridOptions: {
                onRowDblClick: function (e) { if (gCanEdit) onEditPrepare(e.data.RoleId); }
            },
            onSelectionChanged: function (rows) {
                $("#btn_edit, #btn_access").prop("disabled", rows.length !== 1);
                // Role sistem (Administrator) tidak boleh dihapus.
                $("#btn_delete").prop("disabled", rows.length < 1 || rows.some(function (r) { return r.IsSystem; }));
            },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "RoleCode", caption: "Role Code", fixed: true, fixedPosition: "left" },
                {
                    dataField: "RoleName", caption: "Role Name",
                    cellTemplate: function (container, options) {
                        container.text(options.data.RoleName);
                        if (options.data.IsSystem) {
                            container.append(" ").append($("<span>").addClass("sg-badge amber").text("System"));
                        }
                    }
                },
                { dataField: "Description", caption: "Description" },
                { dataField: "UserCount", caption: "Total User", dataType: "number", alignment: "center", width: 110 },
                HMA.col.status()
            ].concat(HMA.col.audit())
        });
    });
</script>
