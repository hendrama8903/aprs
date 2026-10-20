<style>
    .sup-addr-head { margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--line, #e5e7eb); }
    .sup-addr-form { margin-top: 8px; padding: 12px; border: 1.5px solid var(--accent, #1455d9); border-radius: 9px; }
    .sup-addr-add { display: block; width: 100%; margin-top: 8px; padding: 10px; border: 1.5px dashed #c7cfdd; border-radius: 9px; background: none; color: #1455d9; font-size: 13px; font-weight: 600; cursor: pointer; }
    .sup-addr-add:hover { border-color: #1455d9; background: #f5f8ff; }
    .sup-addr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .sup-addr-grid label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
    .sup-span2 { grid-column: 1 / -1; }
    .sup-addr-form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; }
    .sup-addr-list { margin-top: 12px; display: flex; flex-direction: column; gap: 8px; }
    .sup-addr-empty { font-size: 12px; color: #6b7280; padding: 10px 0; }
    .sup-addr-card { display: flex; gap: 12px; justify-content: space-between; padding: 10px 12px; border: 1px solid var(--line, #e5e7eb); border-radius: 9px; }
    .sup-addr-card.inactive { opacity: .6; }
    .sup-addr-title { font-weight: 700; }
    .sup-addr-badge { display: inline-block; margin-left: 6px; padding: 1px 7px; border-radius: 9px; font-size: 11px; font-weight: 600; background: #e8efff; color: #1455d9; }
    .sup-addr-badge.off { background: #f3f4f6; color: #6b7280; }
    .sup-addr-text { font-size: 12px; color: #374151; white-space: pre-line; margin-top: 2px; }
    .sup-addr-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .sup-addr-actions { display: flex; align-items: flex-start; gap: 4px; flex-shrink: 0; }
    .sup-addr-actions button { border: 0; background: none; color: #1455d9; font-size: 12px; font-weight: 600; padding: 2px 6px; cursor: pointer; }
    .sup-addr-actions button.danger { color: #dc2626; }
    @media (max-width: 600px) {
        .sup-addr-grid { grid-template-columns: 1fr; }
        .sup-addr-card { flex-direction: column; }
    }
</style>
<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    // Daftar alamat dikelola di client (gAddresses) lalu dikirim bersama supplier saat Save.
    var gAddresses = [];

    function renderAddresses() {
        // Kembalikan form ke tempat asalnya dulu supaya tidak ikut terhapus saat daftar dikosongkan.
        $("#addressForm").appendTo("#addressFormHome");
        var $list = $("#addressList").empty();
        if (!gAddresses.length) {
            $list.append($("<div class='sup-addr-empty'>").text("No address yet. Add at least one address before creating an APR for this supplier."));
            return;
        }
        $.each(gAddresses, function (i, a) {
            var $title = $("<div class='sup-addr-title'>").text(a.Label);
            if (a.PlantCode) $title.append($("<span class='sup-addr-badge off'>").text("Plant " + a.PlantCode));
            if (a.IsDefault) $title.append($("<span class='sup-addr-badge'>").text("Default"));
            if (!a.IsActive) $title.append($("<span class='sup-addr-badge off'>").text("Inactive"));

            var meta = [];
            if (a.AttentionName) meta.push("Attn. " + a.AttentionName + (a.AttentionTitle ? " (" + a.AttentionTitle + ")" : ""));
            if (a.Email) meta.push(a.Email);

            var $actions = $("<div class='sup-addr-actions'>");
            if (!a.IsDefault && a.IsActive) {
                $("<button type='button'>").text("Set Default").on("click", function () { setDefault(i); }).appendTo($actions);
            }
            $("<button type='button'>").text("Edit").on("click", function () { openAddress(i); }).appendTo($actions);
            $("<button type='button' class='danger'>").text("Delete").on("click", function () { removeAddress(i); }).appendTo($actions);

            $("<div class='sup-addr-card'>").attr("data-index", i).toggleClass("inactive", !a.IsActive)
                .append($("<div>")
                    .append($title)
                    .append($("<div class='sup-addr-text'>").text(a.Address))
                    .append(meta.length ? $("<div class='sup-addr-meta'>").text(meta.join(" · ")) : null))
                .append($actions)
                .appendTo($list);
        });
    }

    // Alamat aktif pertama jadi default kalau belum ada default (sama dengan aturan di SupplierRepo).
    function ensureDefault() {
        var hasDefault = $.grep(gAddresses, function (a) { return a.IsDefault; }).length > 0;
        if (hasDefault) return;
        $.each(gAddresses, function (_, a) {
            if (a.IsActive) { a.IsDefault = true; return false; }
        });
    }

    function setDefault(index) {
        $.each(gAddresses, function (i, a) { a.IsDefault = i === index; });
        renderAddresses();
    }

    function removeAddress(index) {
        gAddresses.splice(index, 1);
        ensureDefault();
        closeAddress();
        renderAddresses();
    }

    function openAddress(index) {
        var a = index === null ? { Label: "", PlantCode: "", Address: "", AttentionName: "", AttentionTitle: "", Email: "", IsActive: true } : gAddresses[index];
        $("#addrIndex").val(index === null ? "" : index);
        $("#addrLabel").val(a.Label);
        $("#addrPlantCode").val(a.PlantCode);
        $("#addrAddress").val(a.Address);
        $("#addrAttentionName").val(a.AttentionName);
        $("#addrAttentionTitle").val(a.AttentionTitle);
        $("#addrEmail").val(a.Email);
        $("#addrIsActive").prop("checked", a.IsActive);

        // Add: form di bawah daftar. Edit: form menggantikan kartu yang diedit.
        closeAddress();
        if (index === null) {
            $("#addressForm").appendTo("#addressFormHome");
        } else {
            var $card = $("#addressList .sup-addr-card[data-index=" + index + "]");
            $("#addressForm").insertAfter($card);
            $card.hide();
        }
        $("#btn_addAddress").hide();
        $("#addressForm").show();
        $("#addrLabel").focus();
    }

    function closeAddress() {
        $("#addressForm").hide();
        $("#addressList .sup-addr-card").show();
        $("#btn_addAddress").show();
    }

    function confirmAddress() {
        var label = $.trim($("#addrLabel").val()), address = $.trim($("#addrAddress").val());
        if (!label || !address) { showErrorMesgGrowl("Label and address are required."); return; }

        var index = $("#addrIndex").val() === "" ? null : parseInt($("#addrIndex").val(), 10);
        var duplicate = $.grep(gAddresses, function (a, i) { return i !== index && a.Label.toUpperCase() === label.toUpperCase(); }).length > 0;
        if (duplicate) { showErrorMesgGrowl("Address label " + label + " is already used."); return; }
        var plant = $.trim($("#addrPlantCode").val()).toUpperCase();
        var plantUsed = plant && $.grep(gAddresses, function (a, i) { return i !== index && (a.PlantCode || "").toUpperCase() === plant; }).length > 0;
        if (plantUsed) { showErrorMesgGrowl("Plant code " + plant + " is already used by another address."); return; }

        var a = index === null ? { AddressId: null, IsDefault: false } : gAddresses[index];
        a.Label = label;
        a.PlantCode = plant;
        a.Address = address;
        a.AttentionName = $.trim($("#addrAttentionName").val());
        a.AttentionTitle = $.trim($("#addrAttentionTitle").val());
        a.Email = $.trim($("#addrEmail").val());
        a.IsActive = $("#addrIsActive").is(":checked");
        if (!a.IsActive) a.IsDefault = false;
        if (index === null) gAddresses.push(a);

        ensureDefault();
        closeAddress();
        renderAddresses();
    }

    var gPage = HMA.crudPage({
        entity: "Supplier",
        key: "SupplierId",
        keyParam: "supplierId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Supplier/Search') }}",
            getByKey: "{{ url('/Supplier/GetByKey') }}",
            save: "{{ url('/Supplier/AddEdit') }}",
            remove: "{{ url('/Supplier/DeleteMultiple') }}",
            exportExcel: "{{ url('/Supplier/DownloadExcel') }}"
        },
        columns: [
            { dataField: "SupplierCode", caption: "Supplier Code", fixed: true, fixedPosition: "left", width: 120 },
            { dataField: "SupplierName", caption: "Supplier Name" },
            {
                // Alamat default saja; baris alamat digabung jadi satu baris di grid.
                dataField: "DefaultAddress", caption: "Address",
                calculateCellValue: function (row) { return (row.DefaultAddress || "").replace(/\s*\r?\n\s*/g, ", "); }
            },
            { dataField: "AttentionName", caption: "Attention" },
            { dataField: "AttentionTitle", caption: "Title", visible: false },
            { dataField: "Email", caption: "Email", visible: false },
            HMA.col.status()
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditCode").val("").prop("disabled", false);
            $("#addEditName").val("");
            $("#addEditIsActive").prop("checked", true);
            gAddresses = [];
            closeAddress();
            renderAddresses();
        },
        fillForm: function (data) {
            $("#addEditId").val(data.SupplierId);
            $("#addEditCode").val(data.SupplierCode).prop("disabled", true);
            $("#addEditName").val(data.SupplierName);
            $("#addEditIsActive").prop("checked", data.IsActive);
            gAddresses = $.map(data.Addresses || [], function (a) { return $.extend({}, a); });
            closeAddress();
            renderAddresses();
        },
        collectForm: function () {
            return {
                SupplierId: $("#addEditId").val(),
                SupplierCode: $("#addEditCode").val(),
                SupplierName: $("#addEditName").val(),
                IsActive: $("#addEditIsActive").is(":checked"),
                Addresses: JSON.stringify(gAddresses)
            };
        }
    });

    $(function () {
        $("#btn_addAddress").on("click", function () { openAddress(null); });
        $("#btn_addrOk").on("click", confirmAddress);
        $("#btn_addrCancel").on("click", closeAddress);
    });
</script>
