<div class="box-body">
    <div id="datagrid"></div>
</div>

<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gGrid = null;

    $(document).ready(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "UserId",
            url: "{{ url('/User/Search') }}",
            gridOptions: {
                onRowDblClick: function (e) { if (gCanEdit) onEditPrepare(e.data.UserId); }
            },
            onSelectionChanged: function (rows) {
                $("#btn_edit").prop("disabled", rows.length !== 1);
                $("#btn_delete").prop("disabled", rows.length < 1);
                // User Active Directory mengganti password lewat Windows.
                $("#btn_resetPassword").prop("disabled", rows.length !== 1 || rows[0].IsAdUser);
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
            ].concat(HMA.col.audit())
        });
    });
</script>
