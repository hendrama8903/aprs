<div class="box-body">
    <div id="datagrid"></div>
</div>

<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gCanDelete = @json($delete);
    var gCanAccess = @json($access);
    var gGrid = null;

    $(document).ready(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "RoleId",
            url: "{{ url('/Role/Search') }}",
            onSelectionChanged: function (rows) {
                $("#btn_edit, #btn_access").prop("disabled", rows.length !== 1);
                $("#btn_delete").prop("disabled", rows.length < 1);
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
            ].concat(HMA.col.audit()).concat([
                HMA.col.actions(function (row) {
                    var items = [];
                    if (gCanEdit) {
                        items.push({ css: "dp-menu-edit", icon: "fa-pencil-square-o", label: "Edit", onClick: function () { onEditPrepare(row.RoleId); } });
                    }
                    if (gCanAccess) {
                        items.push({ css: "dp-menu-position", icon: "fa-key", label: "Set Access", onClick: function () { onAccessPrepare(row); } });
                    }
                    if (gCanDelete && !row.IsSystem) {
                        if (items.length) items.push("divider");
                        items.push({ css: "dp-menu-delete", icon: "fa-trash-o", label: "Delete", onClick: function () { onDeletePrepare([row.RoleId]); } });
                    }
                    return items;
                })
            ])
        });
    });
</script>
