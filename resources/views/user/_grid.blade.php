<div class="box-body">
    <div id="datagrid"></div>
</div>

<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gCanDelete = @json($delete);
    var gCanResetPassword = @json($resetPassword);
    var gGrid = null;

    $(document).ready(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "UserId",
            url: "{{ url('/User/Search') }}",
            params: function () {
                return {
                    departmentId: $("#filterDepartment").val(),
                    roleId: $("#filterRole").val(),
                    isActive: $("#filterStatus").val()
                };
            },
            onSelectionChanged: function (rows) {
                $("#btn_edit").prop("disabled", rows.length !== 1);
                $("#btn_delete").prop("disabled", rows.length < 1);
            },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "Username", caption: "Username", fixed: true, fixedPosition: "left" },
                { dataField: "FullName", caption: "Full Name" },
                { dataField: "EmployeeNo", caption: "Employee No" },
                { dataField: "DepartmentName", caption: "Department" },
                { dataField: "RoleName", caption: "Role" },
                { dataField: "SupervisorName", caption: "Supervisor" },
                {
                    dataField: "IsAdUser", caption: "Login Type", alignment: "center", width: 130,
                    calculateCellValue: function (row) { return row.IsAdUser ? "Active Directory" : "Local"; },
                    cellTemplate: function (container, options) {
                        $("<span>").addClass("sg-badge" + (options.data.IsAdUser ? "" : " gray"))
                            .text(options.data.IsAdUser ? "Active Directory" : "Local")
                            .appendTo(container);
                    }
                },
                { dataField: "Email", caption: "Email", visible: false },
                { dataField: "Phone", caption: "Phone", visible: false },
                HMA.col.date("LastLoginDate", "Last Login", true),
                HMA.col.status()
            ].concat(HMA.col.audit()).concat([
                HMA.col.actions(function (row) {
                    var items = [];
                    if (gCanEdit) {
                        items.push({ css: "dp-menu-edit", icon: "fa-pencil-square-o", label: "Edit", onClick: function () { onEditPrepare(row.UserId); } });
                    }
                    if (gCanResetPassword && !row.IsAdUser) {
                        items.push({ css: "dp-menu-history", icon: "fa-key", label: "Reset Password", onClick: function () { onResetPasswordPrepare(row); } });
                    }
                    if (gCanDelete) {
                        if (items.length) items.push("divider");
                        items.push({ css: "dp-menu-delete", icon: "fa-trash-o", label: "Delete", onClick: function () { onDeletePrepare([row.UserId]); } });
                    }
                    return items;
                })
            ])
        });
    });
</script>
