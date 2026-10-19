<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gTree = null;
    var gNodes = [];
    var gScreenMode = null;
    var gEditRow = null;
    var gCodePrefix = "";

    var gTypeText = {
        MENU: { title: "Menu", desc: "Group in the sidebar, no URL.", icon: "fa-folder-o", name: "Menu Name", namePh: "e.g. Report", parent: "Parent Menu", parentHelp: "(Top level) = directly in the sidebar." },
        SCREEN: { title: "Screen", desc: "Page opened from the sidebar.", icon: "fa-window-maximize", name: "Screen Name", namePh: "e.g. Stock Report", parent: "Parent Menu", parentHelp: "Menu group that contains this screen." },
        BUTTON: { title: "Button", desc: "Action permission inside a screen.", icon: "fa-hand-pointer-o", name: "Button Name", namePh: "e.g. Approve", parent: "Screen", parentHelp: "Screen where this button appears." }
    };

    var gIcons = ["fa-folder-o", "fa-dashboard", "fa-database", "fa-archive", "fa-cubes", "fa-cube", "fa-tags", "fa-building",
        "fa-map-marker", "fa-truck", "fa-download", "fa-upload", "fa-exchange", "fa-list-alt", "fa-shopping-cart", "fa-check-square-o",
        "fa-calendar", "fa-clipboard", "fa-file-text-o", "fa-bar-chart", "fa-pie-chart", "fa-print", "fa-wrench", "fa-users",
        "fa-user", "fa-key", "fa-cogs", "fa-sitemap", "fa-bell-o", "fa-history"];

    var gPresets = [
        { action: "ADD", name: "Add", icon: "fa-plus" },
        { action: "EDIT", name: "Edit", icon: "fa-pencil" },
        { action: "DELETE", name: "Delete", icon: "fa-trash-o" },
        { action: "APPROVE", name: "Approve", icon: "fa-check" },
        { action: "REJECT", name: "Reject", icon: "fa-ban" },
        { action: "POST", name: "Post", icon: "fa-paper-plane-o" },
        { action: "CANCEL", name: "Cancel", icon: "fa-times" },
        { action: "EXPORT", name: "Export", icon: "fa-download" },
        { action: "PRINT", name: "Print", icon: "fa-print" }
    ];

    $(document).ready(function () {
        gTree = createTree();
        buildIconPalette();
        buildPresetOptions();

        $("#btn_add").on("click", function () {
            var d = defaultsFromSelection();
            onAddPrepare(d.type, d.parentId);
        });
        $("#btn_edit").on("click", function () {
            var row = selectedRow();
            if (row) onEditPrepare(row);
        });
        $("#btn_delete").on("click", function () {
            var row = selectedRow();
            if (row) onDeletePrepare(row);
        });
        $("#btn_refresh").on("click", onSearch);
        $("#btn_expand").on("click", function () { gTree.option("expandedRowKeys", parentKeys()); });
        $("#btn_collapse").on("click", function () { gTree.option("expandedRowKeys", []); });
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/Menu/DownloadExcel') }}";
        });

        $("#btn_save").on("click", onSaveClick);
        $("#menuForm").on("submit", function (e) {
            e.preventDefault();
            onSaveClick();
        });

        $("#addEditType").on("change", function () { applyType(this.value, null); });
        $("#addEditParent").on("change", function () { suggestDefaults(); updateLocation(); });
        $("#addEditName").on("input", function () { syncPreset(); updateLocation(); });
        $("#addEditCode").on("input", function () {
            this.value = this.value.toUpperCase().replace(/\s/g, "");
            syncPreset();
            updateLocation();
        });
        $("#addEditUrl").on("input", function () {
            this.value = this.value.replace(/\s/g, "");
            updateLocation();
        });
        $("#addEditIcon").on("input", function () {
            this.value = this.value.replace(/\s/g, "");
            syncIconPalette();
            updateLocation();
        });
        // Hapus tanda error begitu field diubah.
        $("#menuForm").on("input change", ".form-control", function () {
            $(this).closest(".mnf-field").removeClass("invalid");
        });
    });

    // ---------- Tree ----------
    function createTree() {
        var store = new DevExpress.data.CustomStore({
            key: "NodeId",
            loadMode: "raw",
            load: function () {
                var d = $.Deferred();
                $.ajax({ type: "POST", url: "{{ url('/Menu/Search') }}" }).done(function (result) {
                    if (result.status === false) {
                        showErrorMesgGrowl(HMA.formatMessage(result.message));
                        d.resolve([]);
                        return;
                    }
                    gNodes = result.data || [];
                    d.resolve(gNodes);
                }).fail(function () {
                    d.reject("Data loading error");
                });
                return d.promise();
            }
        });

        // Aksi lewat toolbar di atas tree; tombol Add menebak tipe & parent dari baris yang dipilih.
        var columns = gColumns.concat(HMA.col.audit());
        $.each(columns, function (_, c) {
            if (c.dataField === "SortOrder") { c.sortOrder = "asc"; c.sortIndex = 0; }
            if (c.dataField === "NodeName") { c.sortOrder = "asc"; c.sortIndex = 1; }
        });

        $("#treelist").addClass("sg-grid");
        var tree = $("#treelist").dxTreeList({
            dataSource: store,
            keyExpr: "NodeId",
            parentIdExpr: "ParentNodeId",
            rootValue: null,
            dataStructure: "plain",
            autoExpandAll: false,
            expandedRowKeys: [],
            showBorders: true,
            showRowLines: true,
            columnAutoWidth: true,
            allowColumnResizing: true,
            columnResizingMode: "widget",
            noDataText: "Data not found.",
            height: HMA.gridHeight("#treelist"),
            searchPanel: { visible: true, width: 240, placeholder: "Search..." },
            headerFilter: { visible: true },
            selection: { mode: "single" },
            sorting: { mode: "multiple" },
            scrolling: { mode: "standard" },
            columnChooser: { enabled: true, mode: "select" },
            columnFixing: { enabled: true },
            columns: columns,
            onContentReady: function (e) {
                // Pertama kali dimuat: buka level menu supaya screen terlihat.
                if (!e.component._sgExpanded && gNodes.length > 0) {
                    e.component._sgExpanded = true;
                    e.component.option("expandedRowKeys", gNodes.filter(function (n) { return n.NodeType === "MENU"; }).map(function (n) { return n.NodeId; }));
                }
            },
            onSelectionChanged: function (e) {
                var row = e.selectedRowsData[0];
                $("#btn_edit, #btn_delete").prop("disabled", !row);
                // Label Add mengikuti apa yang akan ditambahkan di bawah baris terpilih.
                var d = defaultsFromSelection();
                $("#btnAddLabel").text("Add " + gTypeText[d.type].title);
            },
            onRowDblClick: function (e) { if (gCanEdit) onEditPrepare(e.data); }
        }).dxTreeList("instance");

        $(window).on("resize", function () {
            tree.option("height", HMA.gridHeight("#treelist"));
        });
        return tree;
    }

    function onSearch() {
        gTree.clearSelection();
        gTree.refresh();
    }

    function selectedRow() {
        return gTree.getSelectedRowsData()[0] || null;
    }

    function findNode(nodeId) {
        return gNodes.filter(function (n) { return n.NodeId === nodeId; })[0] || null;
    }

    function parentKeys() {
        var keys = {};
        $.each(gNodes, function (_, n) { if (n.ParentNodeId) keys[n.ParentNodeId] = true; });
        return Object.keys(keys);
    }

    // Tombol Add menebak tipe & parent dari baris yang dipilih:
    // tidak ada -> Menu top level, Menu -> Screen di dalamnya, Screen/Button -> Button di screen tsb.
    function defaultsFromSelection() {
        var row = selectedRow();
        if (!row) return { type: "MENU", parentId: null };
        if (row.NodeType === "MENU") return { type: "SCREEN", parentId: row.RecordId };
        if (row.NodeType === "SCREEN") return { type: "BUTTON", parentId: row.RecordId };
        return { type: "BUTTON", parentId: row.ParentMenuId };
    }

    // ---------- Form ----------
    function onAddPrepare(type, parentMenuId) {
        gScreenMode = "ADD";
        gEditRow = null;
        gCodePrefix = "";
        resetValidation();
        $("#addEditType").prop("disabled", false);
        $("#addEditId, #addEditName, #addEditUrl, #addEditCode, #addEditIcon, #addEditSort").val("");
        $("#addEditCode, #addEditParent").prop("disabled", false);
        $("#addEditIsActive").prop("checked", true);
        $("#addEditType").val(type);
        applyType(type, parentMenuId);

        $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditName").focus(); });
    }

    function onEditPrepare(row) {
        gScreenMode = "EDIT";
        gEditRow = row;
        resetValidation();
        $("#addEditType").val(row.NodeType);
        $("#addEditType").prop("disabled", true);
        applyType(row.NodeType, row.ParentMenuId);

        $("#addEditId").val(row.RecordId);
        $("#addEditName").val(row.NodeName);
        $("#addEditUrl").val(row.MenuUrl || "");
        $("#addEditCode").val(row.PermissionCode || "").prop("disabled", true);
        $("#addEditIcon").val(row.MenuIcon || "");
        $("#addEditSort").val(row.SortOrder);
        $("#addEditIsActive").prop("checked", row.IsActive);
        // Button tidak bisa dipindah ke screen lain.
        $("#addEditParent").prop("disabled", row.NodeType === "BUTTON");
        syncIconPalette();
        syncPreset();
        updateLocation();

        $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditName").focus(); });
    }

    function currentType() {
        return $("#addEditType").val();
    }

    function applyType(type, parentMenuId) {
        var text = gTypeText[type];
        $("#modal-title").text((gScreenMode === "ADD" ? "Add " : "Edit ") + text.title);
        $("#modal-icon").attr("class", "fa " + text.icon);
        $("#typeGlyph").attr("class", "fa " + text.icon);
        $("#typeHelp").text(gScreenMode === "EDIT" ? "Cannot be changed after saving." : text.desc);
        $("#nameLabel").text(text.name);
        $("#addEditName").attr("placeholder", text.namePh);
        $("#parentLabel").text(text.parent);
        $("#parentOpt").html(type === "BUTTON" ? '<span class="req">*</span>' : "(optional)").toggleClass("opt", type !== "BUTTON");
        $("#parentHelp").text(text.parentHelp);
        $(".mn-only-screen").toggle(type === "SCREEN");
        $(".mn-only-button").toggle(type === "BUTTON");
        $(".mn-only-menu").toggle(type !== "BUTTON");
        $(".presetable").toggle(type === "BUTTON" && gScreenMode === "ADD");
        resetValidation();

        // Ganti tipe di form: pertahankan parent kalau masih cocok.
        if (parentMenuId === null && gScreenMode === "ADD") {
            var current = parseInt($("#addEditParent").val(), 10);
            var node = isNaN(current) ? null : findNode("M" + current);
            if (node && ((type === "BUTTON") === (node.NodeType === "SCREEN"))) parentMenuId = current;
            else if (node && type === "BUTTON" && node.NodeType === "MENU") parentMenuId = null;
        }
        fillParentOptions(type, parentMenuId);
        suggestDefaults();
        syncIconPalette();
        syncPreset();
        updateLocation();
    }

    // Opsi parent: MENU/SCREEN -> daftar menu (berjenjang), BUTTON -> daftar screen dikelompokkan per menu.
    function fillParentOptions(type, selectedId) {
        var $sel = $("#addEditParent").empty();
        var excluded = {};
        if (gEditRow && gEditRow.NodeType === "MENU") {
            (function exclude(nodeId) {
                excluded[nodeId] = true;
                $.each(gNodes, function (_, n) { if (n.ParentNodeId === nodeId) exclude(n.NodeId); });
            })(gEditRow.NodeId);
        }

        $sel.append($("<option>").val("").text(type === "BUTTON" ? "-- Select screen --" : "(Top level)"));

        (function walk(parentNodeId, depth, path, $target) {
            var children = gNodes.filter(function (n) { return n.ParentNodeId === parentNodeId && n.NodeType !== "BUTTON"; });
            children.sort(function (a, b) { return a.SortOrder - b.SortOrder || a.NodeName.localeCompare(b.NodeName); });
            $.each(children, function (_, n) {
                if (excluded[n.NodeId]) return;
                if (type === "BUTTON") {
                    if (n.NodeType === "SCREEN") {
                        $target.append($("<option>").val(n.RecordId).text(n.NodeName + "  —  " + n.MenuUrl));
                    } else {
                        var $group = $("<optgroup>").attr("label", path + n.NodeName);
                        $sel.append($group);
                        walk(n.NodeId, depth + 1, path + n.NodeName + " › ", $group);
                        if ($group.children().length === 0) $group.remove();
                    }
                } else if (n.NodeType === "MENU") {
                    $target.append($("<option>").val(n.RecordId).text(new Array(depth + 1).join("    ") + (depth ? "└ " : "") + n.NodeName));
                    walk(n.NodeId, depth + 1, path, $target);
                }
            });
        })(null, 0, "", $sel);

        $sel.val(selectedId == null ? "" : String(selectedId));
        if ($sel.val() === null) $sel.val("");
    }

    // Saat tambah: usulkan urutan berikutnya dan prefix kode button dari URL screen.
    function suggestDefaults() {
        if (gScreenMode !== "ADD") return;
        var type = currentType();
        var parentId = $("#addEditParent").val();
        var parentNodeId = parentId ? "M" + parentId : null;

        if (type !== "BUTTON") {
            var maxSort = 0;
            $.each(gNodes, function (_, n) {
                if (n.ParentNodeId === parentNodeId && n.NodeType !== "BUTTON") maxSort = Math.max(maxSort, n.SortOrder);
            });
            $("#addEditSort").val(maxSort + 10);
            return;
        }

        var prefix = screenPrefix(parentNodeId);
        var $code = $("#addEditCode");
        var code = $code.val();
        // Ganti prefix lama dengan prefix screen yang baru, aksi di belakangnya dipertahankan.
        if (code === "" || code === gCodePrefix) $code.val(prefix);
        else if (gCodePrefix && code.indexOf(gCodePrefix) === 0) $code.val(prefix + code.substring(gCodePrefix.length));
        else if (!gCodePrefix && code.indexOf("-") < 0) $code.val(prefix + code);
        gCodePrefix = prefix;
        $("#codeHelp").text(prefix ? "Prefix " + prefix + " · cannot be changed later." : "Checked by the program. Cannot be changed later.");
        syncPreset();
    }

    function screenPrefix(parentNodeId) {
        var screen = parentNodeId ? findNode(parentNodeId) : null;
        if (!screen || !screen.MenuUrl) return "";
        var segment = screen.MenuUrl.split("/").filter(function (s) { return s; })[0] || "";
        return segment.toUpperCase() + "-";
    }

    // ---------- Icon picker ----------
    function buildIconPalette() {
        var $p = $("#iconPalette");
        $.each(gIcons, function (_, icon) {
            $("<button>").attr({ type: "button", title: icon, "aria-label": icon }).addClass("mnf-icon-opt").data("icon", icon)
                .append($("<i>").addClass("fa " + icon))
                .on("click", function () {
                    $("#addEditIcon").val(icon);
                    $(".mnf-icon-wrap").removeClass("open");
                    syncIconPalette();
                    updateLocation();
                })
                .appendTo($p);
        });

        $("#btn_iconToggle").on("click", function (e) {
            e.stopPropagation();
            $(".mnf-icon-wrap").toggleClass("open");
        });
        $("#iconPop").on("click", function (e) { e.stopPropagation(); });
        $(document).on("click", function () { $(".mnf-icon-wrap").removeClass("open"); });
        $("#addEditIcon").on("keydown", function (e) { if (e.key === "Escape") $(".mnf-icon-wrap").removeClass("open"); });
        $("#modalAddEdit").on("hidden.bs.modal", function () { $(".mnf-icon-wrap").removeClass("open"); });
    }

    function syncIconPalette() {
        var icon = $.trim($("#addEditIcon").val());
        $("#iconPalette .mnf-icon-opt").each(function () { $(this).toggleClass("active", $(this).data("icon") === icon); });
        $("#iconInputGlyph").attr("class", "fa " + (icon || "fa-circle-o"));
    }

    // ---------- Preset button (combo box) ----------
    function buildPresetOptions() {
        var $sel = $("#addEditPreset").append($("<option>").val("").text("Custom"));
        $.each(gPresets, function (_, p) {
            $sel.append($("<option>").val(p.action).text(p.name + "  —  " + p.action));
        });
        $sel.on("change", function () {
            var p = presetByAction(this.value);
            $("#presetGlyph").attr("class", "fa " + (p ? p.icon : "fa-bolt"));
            if (!p) return;
            var prefix = screenPrefix($("#addEditParent").val() ? "M" + $("#addEditParent").val() : null);
            $("#addEditName").val(p.name);
            $("#addEditCode").val(prefix + p.action);
            $("#fieldName, #fieldCode").removeClass("invalid");
            updateLocation();
        });
    }

    function presetByAction(action) {
        return gPresets.filter(function (p) { return p.action === action; })[0] || null;
    }

    // Combo ikut menunjukkan preset yang cocok dengan nama & kode; selain itu "Custom".
    function syncPreset() {
        var action = ($("#addEditCode").val() || "").split("-").pop();
        var p = presetByAction(action);
        var match = p && $("#addEditName").val() === p.name ? p : null;
        $("#addEditPreset").val(match ? match.action : "");
        $("#presetGlyph").attr("class", "fa " + (match ? match.icon : "fa-bolt"));
    }

    // ---------- Lokasi (breadcrumb header) ----------
    function ancestors(menuId) {
        var list = [];
        var node = menuId ? findNode("M" + menuId) : null;
        while (node) {
            list.unshift(node);
            node = node.ParentNodeId ? findNode(node.ParentNodeId) : null;
        }
        return list;
    }

    function updateLocation() {
        var type = currentType();
        if (!type) return;
        var name = $.trim($("#addEditName").val()) || gTypeText[type].namePh.replace("e.g. ", "");
        var chain = ancestors(parseInt($("#addEditParent").val(), 10) || null);

        // Breadcrumb lokasi di header.
        var $path = $("#modal-path").empty().append($("<span>").text("Location:"));
        $.each(chain.length ? chain : [{ NodeName: "Sidebar" }], function (_, n) {
            $path.append($("<span>").text(n.NodeName)).append($("<i>").addClass("fa fa-angle-right"));
        });
        $path.append($("<b>").text(name));
    }

    // ---------- Validasi & simpan ----------
    function resetValidation() {
        $("#menuForm .mnf-field").removeClass("invalid");
    }

    function validateForm() {
        resetValidation();
        var type = currentType();
        var bad = [];
        if (!$.trim($("#addEditName").val())) bad.push("#fieldName");
        if (type === "BUTTON" && !$("#addEditParent").val()) bad.push("#fieldParent");
        if (type === "SCREEN" && !/^\/\S+$/.test($.trim($("#addEditUrl").val()))) bad.push("#fieldUrl");
        if (type === "BUTTON" && gScreenMode === "ADD" && !/^[A-Z0-9_-]+$/.test($("#addEditCode").val()) ) bad.push("#fieldCode");

        $.each(bad, function (_, sel) { $(sel).addClass("invalid"); });
        if (bad.length) $(bad[0]).find(".form-control").first().focus();
        return bad.length === 0;
    }

    function onSaveClick() {
        if (!validateForm()) return;
        showConfirmMesg("Are you sure you want to save this data?", saveData, "Confirmation");
    }

    function saveData() {
        var type = currentType();
        var parentId = $("#addEditParent").val();
        HMA.post("{{ url('/Menu/AddEdit') }}", {
            screenMode: gScreenMode,
            NodeType: type,
            RecordId: $("#addEditId").val(),
            ParentMenuId: parentId,
            NodeName: $.trim($("#addEditName").val()),
            MenuUrl: type === "SCREEN" ? $.trim($("#addEditUrl").val()) : "",
            MenuIcon: type === "BUTTON" ? "" : $.trim($("#addEditIcon").val()),
            PermissionCode: type === "BUTTON" ? $("#addEditCode").val().toUpperCase() : "",
            SortOrder: $("#addEditSort").val() || 0,
            IsActive: $("#addEditIsActive").is(":checked")
        }, function () {
            $("#modalAddEdit").modal("hide");
            // Buka parent supaya item baru langsung terlihat.
            if (parentId) {
                var keys = gTree.option("expandedRowKeys").slice();
                $.each(ancestors(parseInt(parentId, 10)), function (_, n) {
                    if (keys.indexOf(n.NodeId) < 0) keys.push(n.NodeId);
                });
                gTree.option("expandedRowKeys", keys);
            }
            onSearch();
        });
    }

    function onDeletePrepare(row) {
        var label = gTypeText[row.NodeType].title.toLowerCase();
        showConfirmMesg("Are you sure you want to delete " + label + " \"" + HMA.escapeHtml(row.NodeName) + "\"?"
            + (row.RoleCount > 0 ? " It is currently used by " + row.RoleCount + " role(s)." : ""), function () {
            HMA.post("{{ url('/Menu/Delete') }}", { nodeType: row.NodeType, recordId: row.RecordId }).always(onSearch);
        }, "Confirmation");
    }
</script>
