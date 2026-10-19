<style>
    .mn-name { display: inline-flex; align-items: center; gap: 8px; }
    .mn-name i { width: 16px; text-align: center; color: #2450D8; }
    .mn-name.BUTTON i { color: #98A2B3; }
    .mn-name.inactive { color: #98A2B3; }
    .mn-code { font-family: "IBM Plex Mono", monospace; font-size: 12px; color: #475467; }
    .mn-note { font-size: 11.5px; color: #667085; padding: 10px 16px 14px; line-height: 1.5; }
    .mn-note i { color: #2450D8; }
</style>

<div class="box-body">
    <div id="treelist"></div>
</div>

<script type="text/javascript">
    var gTypeInfo = {
        MENU:   { label: "Menu",   badge: "amber", icon: "fa-folder-o" },
        SCREEN: { label: "Screen", badge: "green", icon: "fa-window-maximize" },
        BUTTON: { label: "Button", badge: "gray",  icon: "fa-hand-pointer-o" }
    };

    var gColumns = [
        {
            dataField: "NodeName", caption: "Name", minWidth: 260,
            cellTemplate: function (container, options) {
                var row = options.data;
                var icon = row.NodeType === "BUTTON" ? gTypeInfo.BUTTON.icon : (row.MenuIcon || gTypeInfo[row.NodeType].icon);
                $("<span>").addClass("mn-name " + row.NodeType).toggleClass("inactive", !row.IsActive)
                    .append($("<i>").addClass("fa " + icon))
                    .append($("<span>").text(row.NodeName))
                    .appendTo(container);
            }
        },
        {
            dataField: "NodeType", caption: "Type", alignment: "center", width: 100,
            calculateCellValue: function (row) { return gTypeInfo[row.NodeType].label; },
            cellTemplate: function (container, options) {
                var info = gTypeInfo[options.data.NodeType];
                $("<span>").addClass("sg-badge " + info.badge).text(info.label).appendTo(container);
            }
        },
        {
            caption: "URL / Button Code", minWidth: 200,
            calculateCellValue: function (row) { return row.NodeType === "BUTTON" ? row.PermissionCode : row.MenuUrl; },
            cellTemplate: function (container, options) {
                if (options.value) $("<span>").addClass("mn-code").text(options.value).appendTo(container);
            }
        },
        { dataField: "SortOrder", caption: "Sort", dataType: "number", alignment: "center", width: 70 },
        { dataField: "RoleCount", caption: "Used By Roles", dataType: "number", alignment: "center", width: 120 },
        HMA.col.status()
    ];
</script>
