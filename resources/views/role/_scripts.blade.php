<script type="text/javascript">
    var gScreenMode = null;

    $(document).ready(function () {
        $("#btn_add").on("click", onAddPrepare);
        $("#btn_edit").on("click", function () {
            var rows = gGrid.getSelectedRowsData();
            if (rows.length === 1) onEditPrepare(rows[0].RoleId);
        });
        $("#btn_access").on("click", function () {
            var rows = gGrid.getSelectedRowsData();
            if (rows.length === 1) onAccessPrepare(rows[0]);
        });
        $("#btn_delete").on("click", function () {
            onDeletePrepare(gGrid.getSelectedRowKeys());
        });
        $("#btn_refresh").on("click", onSearch);
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/Role/DownloadExcel') }}";
        });
        $("#btn_save").on("click", function () {
            showConfirmMesg("Are you sure you want to save this data?", saveData, "Confirmation");
        });
        $("#btn_accessSave").on("click", function () {
            showConfirmMesg("Save access for this role?", saveAccess, "Confirmation");
        });

        // Centang menu -> semua sub-menu ikut; uncentang menu -> permission-nya ikut lepas.
        $("#accessTree").on("change", ".acc-menu-check", function () {
            var checked = $(this).is(":checked");
            var $item = $(this).closest(".acc-item");
            $item.find(".acc-menu-check").prop("checked", checked);
            if (!checked) $item.find(".acc-perm-check").prop("checked", false);
            syncParents();
        });
        // Centang permission -> menunya otomatis tercentang.
        $("#accessTree").on("change", ".acc-perm-check", function () {
            if ($(this).is(":checked")) {
                $(this).closest(".acc-item").children(".acc-menu").find(".acc-menu-check").prop("checked", true);
                syncParents();
            }
        });
    });

    function onSearch() {
        gGrid.clearSelection();
        gGrid.refresh();
    }

    function onAddPrepare() {
        gScreenMode = "ADD";
        $("#modal-title").text("Add Role");
        $("#modal-subtitle").text("Create a new role");
        $("#addEditId").val("");
        $("#addEditCode").val("").prop("disabled", false);
        $("#addEditName, #addEditDescription").val("");
        $("#addEditIsActive").prop("checked", true).prop("disabled", false);

        $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditCode").focus(); });
    }

    function onEditPrepare(roleId) {
        HMA.post("{{ url('/Role/GetByKey') }}", { roleId: roleId }, function (result) {
            var data = result.data;
            gScreenMode = "EDIT";
            $("#modal-title").text("Edit Role");
            $("#modal-subtitle").text("Update role " + data.RoleCode);
            $("#addEditId").val(data.RoleId);
            $("#addEditCode").val(data.RoleCode).prop("disabled", true);
            $("#addEditName").val(data.RoleName);
            $("#addEditDescription").val(data.Description);
            // Role sistem (ADMIN) tidak boleh dinonaktifkan.
            $("#addEditIsActive").prop("checked", data.IsActive).prop("disabled", data.IsSystem);

            $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditName").focus(); });
        }, { successMessage: false });
    }

    function saveData() {
        HMA.post("{{ url('/Role/AddEdit') }}", {
            screenMode: gScreenMode,
            RoleId: $("#addEditId").val(),
            RoleCode: $("#addEditCode").val(),
            RoleName: $("#addEditName").val(),
            Description: $("#addEditDescription").val(),
            IsActive: $("#addEditIsActive").is(":checked")
        }, function () {
            $("#modalAddEdit").modal("hide");
            onSearch();
        });
    }

    function onDeletePrepare(ids) {
        if (!ids || ids.length < 1) return;
        showConfirmMesg("Are you sure you want to delete " + ids.length + " selected role(s)?", function () {
            // Refresh juga saat sebagian gagal, karena record lain mungkin sudah terhapus.
            HMA.post("{{ url('/Role/DeleteMultiple') }}", { selectedIds: ids }).always(onSearch);
        }, "Confirmation");
    }

    // ---------- Set Access ----------
    function onAccessPrepare(row) {
        HMA.post("{{ url('/Role/GetAccess') }}", { roleId: row.RoleId }, function (result) {
            $("#accessRoleId").val(row.RoleId);
            $("#accessRoleName").text(row.RoleCode + " - " + row.RoleName);
            renderAccessTree(result.menus, result.permissions);
            $("#modalAccess").modal("show");
        }, { successMessage: false });
    }

    function renderAccessTree(menus, permissions) {
        function build(parentId) {
            var children = menus.filter(function (m) { return (m.ParentMenuId || null) === parentId; });
            if (children.length === 0) return null;

            var $ul = $("<ul>").addClass("acc-list");
            $.each(children, function (_, menu) {
                var $li = $("<li>").addClass("acc-item");
                var $label = $("<label>").addClass("acc-menu")
                    .append($("<input type='checkbox'>").addClass("acc-menu-check").val(menu.MenuId).prop("checked", menu.IsGranted))
                    .append($("<i>").addClass("fa " + (menu.MenuIcon || "fa-circle-o")))
                    .append($("<span>").text(menu.MenuName));
                $li.append($label);

                var perms = permissions.filter(function (p) { return p.MenuId === menu.MenuId; });
                if (perms.length > 0) {
                    var $perms = $("<div>").addClass("acc-perms");
                    $.each(perms, function (_, p) {
                        $perms.append($("<label>")
                            .append($("<input type='checkbox'>").addClass("acc-perm-check").val(p.PermissionId).prop("checked", p.IsGranted))
                            .append(document.createTextNode(p.PermissionName)));
                    });
                    $li.append($perms);
                }

                var $sub = build(menu.MenuId);
                if ($sub) $li.append($sub);
                $ul.append($li);
            });
            return $ul;
        }

        var $tree = build(null);
        $("#accessTree").replaceWith($tree ? $tree.attr("id", "accessTree") : $("<ul id='accessTree' class='acc-list'>"));
        syncParents();
    }

    // Grup (menu dengan sub-menu) tercentang kalau salah satu sub-menunya tercentang.
    function syncParents() {
        $($("#accessTree .acc-item").get().reverse()).each(function () {
            var $subChecks = $(this).children(".acc-list").find(".acc-menu-check");
            if ($subChecks.length > 0) {
                $(this).children(".acc-menu").find(".acc-menu-check").prop("checked", $subChecks.filter(":checked").length > 0);
            }
        });
    }

    function saveAccess() {
        var menuIds = $("#accessTree .acc-menu-check:checked").map(function () { return this.value; }).get();
        var permissionIds = $("#accessTree .acc-perm-check:checked").map(function () { return this.value; }).get();

        HMA.post("{{ url('/Role/SaveAccess') }}", {
            roleId: $("#accessRoleId").val(),
            menuIds: menuIds,
            permissionIds: permissionIds
        }, function () {
            $("#modalAccess").modal("hide");
        });
    }
</script>
