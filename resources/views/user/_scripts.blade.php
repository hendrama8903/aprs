<script type="text/javascript">
    var gScreenMode = null;

    $(document).ready(function () {
        HMA.select2("#filterDepartment", "{{ url('/Lookup/Department') }}", "All departments");
        HMA.select2("#filterRole", "{{ url('/Lookup/Role') }}", "All roles");
        HMA.select2("#addEditDepartment", "{{ url('/Lookup/Department') }}", "Select department", "#modalAddEdit");
        HMA.select2("#addEditRole", "{{ url('/Lookup/Role') }}", "Select role", "#modalAddEdit");
        HMA.select2("#addEditSupervisor", "{{ url('/Lookup/UserList') }}", "Select supervisor", "#modalAddEdit");

        $("#filterDepartment, #filterRole, #filterStatus").on("change", onSearch);
        $("#btn_toggleFilters").on("click", function () {
            $("#filterBar").toggleClass("show");
            gGrid.option("height", HMA.gridHeight("#datagrid"));
        });

        $("#btn_add").on("click", onAddPrepare);
        $("#btn_edit").on("click", function () {
            var rows = gGrid.getSelectedRowsData();
            if (rows.length === 1) onEditPrepare(rows[0].UserId);
        });
        $("#btn_delete").on("click", function () {
            onDeletePrepare(gGrid.getSelectedRowKeys());
        });
        $("#btn_resetPassword").on("click", function () {
            var rows = gGrid.getSelectedRowsData();
            if (rows.length === 1 && !rows[0].IsAdUser) onResetPasswordPrepare(rows[0]);
        });
        $("#btn_refresh").on("click", onSearch);
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/User/DownloadExcel') }}";
        });
        $("#btn_save").on("click", function () {
            showConfirmMesg("Are you sure you want to save this data?", saveData, "Confirmation");
        });
        $("#btn_resetPasswordSave").on("click", saveResetPassword);

        $("input[name='addEditLoginType']").on("change", applyLoginType);
    });

    function onSearch() {
        gGrid.clearSelection();
        gGrid.refresh();
    }

    function isAdLogin() {
        return $("input[name='addEditLoginType']:checked").val() === "AD";
    }

    // Password hanya untuk akun lokal; saat edit boleh kosong (= tidak diubah).
    function applyLoginType() {
        var local = !isAdLogin();
        $("#rowPassword").toggle(local);
        $("#passwordRequired").toggle(gScreenMode === "ADD");
        $("#passwordHint").text(gScreenMode === "ADD"
            ? "Required for local accounts."
            : "Leave blank to keep the current password.");
    }

    function onAddPrepare() {
        gScreenMode = "ADD";
        $("#modal-title").text("Add User");
        $("#modal-subtitle").text("Register a new user");
        $("#addEditId").val("");
        $("#addEditUsername").val("").prop("disabled", false);
        $("#addEditPassword, #addEditFullName, #addEditEmployeeNo, #addEditEmail, #addEditPhone").val("");
        $("input[name='addEditLoginType'][value='LOCAL']").prop("checked", true);
        HMA.setSelect2Value("#addEditDepartment", null);
        HMA.setSelect2Value("#addEditRole", null);
        HMA.setSelect2Value("#addEditSupervisor", null);
        $("#addEditIsActive").prop("checked", true);
        applyLoginType();

        $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditUsername").focus(); });
    }

    function onEditPrepare(userId) {
        HMA.post("{{ url('/User/GetByKey') }}", { userId: userId }, function (result) {
            var data = result.data;
            gScreenMode = "EDIT";
            $("#modal-title").text("Edit User");
            $("#modal-subtitle").text("Update user " + data.Username);
            $("#addEditId").val(data.UserId);
            $("#addEditUsername").val(data.Username).prop("disabled", true);
            $("#addEditPassword").val("");
            $("#addEditFullName").val(data.FullName);
            $("#addEditEmployeeNo").val(data.EmployeeNo);
            $("#addEditEmail").val(data.Email);
            $("#addEditPhone").val(data.Phone);
            $("input[name='addEditLoginType'][value='" + (data.IsAdUser ? "AD" : "LOCAL") + "']").prop("checked", true);
            HMA.setSelect2Value("#addEditDepartment", data.DepartmentId, data.DepartmentName);
            HMA.setSelect2Value("#addEditRole", data.RoleId, data.RoleName);
            HMA.setSelect2Value("#addEditSupervisor", data.SupervisorUserId, data.SupervisorName);
            $("#addEditIsActive").prop("checked", data.IsActive);
            applyLoginType();

            $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditFullName").focus(); });
        }, { successMessage: false });
    }

    function saveData() {
        HMA.post("{{ url('/User/AddEdit') }}", {
            screenMode: gScreenMode,
            UserId: $("#addEditId").val(),
            Username: $("#addEditUsername").val(),
            password: isAdLogin() ? "" : $("#addEditPassword").val(),
            FullName: $("#addEditFullName").val(),
            EmployeeNo: $("#addEditEmployeeNo").val(),
            Email: $("#addEditEmail").val(),
            Phone: $("#addEditPhone").val(),
            DepartmentId: $("#addEditDepartment").val(),
            RoleId: $("#addEditRole").val(),
            SupervisorUserId: $("#addEditSupervisor").val(),
            IsAdUser: isAdLogin(),
            IsActive: $("#addEditIsActive").is(":checked")
        }, function () {
            $("#modalAddEdit").modal("hide");
            onSearch();
        });
    }

    function onDeletePrepare(ids) {
        if (!ids || ids.length < 1) return;
        showConfirmMesg("Are you sure you want to delete " + ids.length + " selected user(s)?", function () {
            // Refresh juga saat sebagian gagal, karena record lain mungkin sudah terhapus.
            HMA.post("{{ url('/User/DeleteMultiple') }}", { selectedIds: ids }).always(onSearch);
        }, "Confirmation");
    }

    function onResetPasswordPrepare(row) {
        $("#resetPasswordId").val(row.UserId);
        $("#resetPasswordUser").text(row.Username + " - " + row.FullName);
        $("#resetPasswordNew, #resetPasswordConfirm").val("");
        $("#modalResetPassword").modal("show").one("shown.bs.modal", function () { $("#resetPasswordNew").focus(); });
    }

    function saveResetPassword() {
        if ($("#resetPasswordNew").val() !== $("#resetPasswordConfirm").val()) {
            showErrorMesgGrowl("New password and confirmation do not match.");
            return;
        }
        HMA.post("{{ url('/User/ResetPassword') }}", {
            userId: $("#resetPasswordId").val(),
            newPassword: $("#resetPasswordNew").val()
        }, function () {
            $("#modalResetPassword").modal("hide");
        });
    }
</script>
