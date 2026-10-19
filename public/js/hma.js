// =====================================================================
// HMA - helper bersama untuk halaman modul (grid DevExtreme, AJAX, menu aksi baris)
// Dipakai bersama site.js (growl, confirm, blockUI) dari tema DMS.
// =====================================================================
var HMA = (function ($) {

    // ---------- AJAX ----------
    // POST + blockUI + growl standar. onSuccess dipanggil hanya kalau result.status == true.
    function post(url, data, onSuccess, opts) {
        opts = opts || {};
        if (opts.block !== false) popUpProgressShow();

        return $.ajax({
            type: "POST",
            url: url,
            dataType: "json",
            traditional: true,
            data: data
        }).done(function (result) {
            if (opts.block !== false) popUpProgressHide();
            if (result.status === true) {
                if (opts.successMessage !== false && result.message) showSuccessMesgGrowl(formatMessage(result.message));
                if (onSuccess) onSuccess(result);
            } else if (result.message) {
                showErrorMesgGrowl(formatMessage(result.message));
            }
        }).fail(function () {
            if (opts.block !== false) popUpProgressHide();
        });
    }

    // ---------- Grid ----------
    function gridHeight(selector, minHeight) {
        var $grid = $(selector);
        if ($grid.length === 0) return 400;
        return Math.max(minHeight || 300, $(window).height() - $grid.offset().top - 40);
    }

    // Standar filter semua grid/treelist: baris filter di bawah header setiap kolom.
    // Tidak ada search panel, column chooser, maupun bar filter terpisah di atas grid.
    function filterDefaults() {
        return {
            filterRow: { visible: true, applyFilter: "auto", showOperationChooser: true, resetOperationText: "Reset" },
            headerFilter: { visible: false },
            searchPanel: { visible: false },
            columnChooser: { enabled: false }
        };
    }

    // Grid standar: data diambil sekali dari url (POST), filter/paging di client.
    //   cfg.key      : nama field primary key
    //   cfg.url      : endpoint Search
    //   cfg.params() : parameter tambahan (filter)
    //   cfg.columns  : kolom DevExtreme
    //   cfg.onLoaded(data), cfg.onSelectionChanged(rows)
    function createGrid(selector, cfg) {
        var store = new DevExpress.data.CustomStore({
            key: cfg.key,
            loadMode: "raw",
            load: function () {
                var d = $.Deferred();
                $.ajax({
                    type: "POST",
                    url: cfg.url,
                    traditional: true,
                    data: cfg.params ? cfg.params() : {}
                }).done(function (result) {
                    if (result.status === false) {
                        showErrorMesgGrowl(formatMessage(result.message));
                        d.resolve([]);
                        return;
                    }
                    var data = result.data || [];
                    if (cfg.onLoaded) cfg.onLoaded(data);
                    d.resolve(data);
                }).fail(function () {
                    d.reject("Data loading error");
                });
                return d.promise();
            }
        });

        var options = $.extend(true, {
            dataSource: store,
            showBorders: true,
            columnAutoWidth: true,
            allowColumnResizing: true,
            columnResizingMode: "widget",
            noDataText: "Data not found.",
            height: gridHeight(selector),
            selection: { mode: "multiple", showCheckBoxesMode: "always", allowSelectAll: true },
            paging: { pageSize: 25 },
            pager: {
                visible: true,
                displayMode: "compact",
                showPageSizeSelector: true,
                allowedPageSizes: [10, 25, 50, 100],
                showInfo: true,
                showNavigationButtons: true
            },
            columnFixing: { enabled: true },
            onSelectionChanged: function (e) {
                if (cfg.onSelectionChanged) cfg.onSelectionChanged(e.selectedRowsData);
            }
        }, filterDefaults(), cfg.gridOptions || {});
        options.columns = cfg.columns;
        // Kolom checkbox bawaan DevExtreme lebarnya 70px - dipersempit supaya kolom data muat.
        if (options.selection && options.selection.mode === "multiple") {
            options.columns = [{ type: "selection", width: 40 }].concat(options.columns);
        }

        $(selector).addClass("sg-grid");
        var grid = $(selector).dxDataGrid(options).dxDataGrid("instance");

        $(window).on("resize", function () {
            grid.option("height", gridHeight(selector));
        });

        return grid;
    }

    // ---------- Kolom siap pakai ----------
    var col = {
        rowNumber: function () {
            return {
                caption: "No", width: 50, alignment: "center",
                allowHiding: false, allowFiltering: false, allowSorting: false, allowHeaderFiltering: false,
                fixed: true, fixedPosition: "left",
                cellTemplate: function (container, options) {
                    var grid = options.component;
                    container.text(grid.pageIndex() * grid.pageSize() + options.row.rowIndex + 1);
                }
            };
        },
        status: function (field, caption, onText, offText) {
            field = field || "IsActive";
            onText = onText || "Active";
            offText = offText || "Inactive";
            return {
                dataField: field, caption: caption || "Status", alignment: "center", width: 120,
                calculateCellValue: function (row) { return row[field] ? onText : offText; },
                // Filter berupa pilihan, bukan teks ("Active" juga cocok dengan "Inactive").
                lookup: { dataSource: [onText, offText] },
                selectedFilterOperation: "=",
                cellTemplate: function (container, options) {
                    var on = !!options.data[field];
                    var $cell = $("<span>").addClass("sg-status");
                    $("<span>").addClass("sg-status-dot").toggleClass("off", !on).appendTo($cell);
                    $("<span>").text(on ? onText : offText).appendTo($cell);
                    $cell.appendTo(container);
                }
            };
        },
        date: function (field, caption, withTime, visible) {
            return {
                // "date" untuk kolom tanpa jam supaya filter "= tanggal" tidak gagal karena komponen jam.
                dataField: field, caption: caption, dataType: withTime ? "datetime" : "date", alignment: "right",
                visible: visible !== false,
                format: withTime ? "dd-MM-yyyy HH:mm" : "dd-MM-yyyy"
            };
        },
        audit: function () {
            return [
                { dataField: "CreatedBy", caption: "Created By", visible: false },
                col.date("CreatedDate", "Created Date", true, false),
                { dataField: "ModifiedBy", caption: "Modified By", visible: false },
                col.date("ModifiedDate", "Modified Date", true, false)
            ];
        }
    };

    // ---------- Halaman CRUD standar ----------
    // Menghubungkan toolbar (#btn_add/#btn_edit/#btn_delete/#btn_refresh/#btn_export),
    // grid (#datagrid) dan popup (#modalAddEdit, #modal-title, #modal-subtitle, #btn_save).
    //   cfg.entity      : label, mis. "Department"
    //   cfg.key         : field primary key, mis. "DepartmentId"
    //   cfg.keyParam    : nama parameter GetByKey, mis. "departmentId"
    //   cfg.urls        : { search, getByKey, save, remove, exportExcel }
    //   cfg.canEdit     : hak edit (double-click baris membuka Edit)
    //   cfg.columns     : kolom data (kolom No & audit ditambahkan otomatis)
    //   cfg.params()    : filter tambahan untuk Search
    //   cfg.onSelectionChanged(rows) : untuk mengaktifkan tombol toolbar tambahan
    //   cfg.resetForm(), cfg.fillForm(data), cfg.collectForm() -> object, cfg.focusAdd, cfg.focusEdit
    function crudPage(cfg) {
        var page = { mode: null, grid: null };

        page.search = function () {
            if (!page.grid) return; // grid belum dibuat (mis. filter di-set saat halaman dimuat)
            page.grid.clearSelection();
            page.grid.refresh();
        };

        function showModal(focusSelector) {
            $("#modalAddEdit").modal("show").one("shown.bs.modal", function () {
                if (focusSelector) $(focusSelector).focus();
            });
        }

        page.add = function () {
            page.mode = "ADD";
            $("#modal-title").text("Add " + cfg.entity);
            $("#modal-subtitle").text("Register a new " + cfg.entity.toLowerCase());
            cfg.resetForm();
            showModal(cfg.focusAdd);
        };

        page.edit = function (id) {
            var data = {};
            data[cfg.keyParam] = id;
            post(cfg.urls.getByKey, data, function (result) {
                page.mode = "EDIT";
                $("#modal-title").text("Edit " + cfg.entity);
                $("#modal-subtitle").text("Update " + cfg.entity.toLowerCase() + " data");
                cfg.fillForm(result.data);
                showModal(cfg.focusEdit);
            }, { successMessage: false });
        };

        page.remove = function (ids) {
            if (!ids || ids.length < 1) return;
            showConfirmMesg("Are you sure you want to delete " + ids.length + " selected record(s)?", function () {
                // Refresh juga saat sebagian gagal, karena record lain mungkin sudah terhapus.
                post(cfg.urls.remove, { selectedIds: ids }).always(page.search);
            }, "Confirmation");
        };

        page.save = function () {
            showConfirmMesg("Are you sure you want to save this data?", function () {
                post(cfg.urls.save, $.extend({ screenMode: page.mode }, cfg.collectForm()), function () {
                    $("#modalAddEdit").modal("hide");
                    page.search();
                });
            }, "Confirmation");
        };

        // Aksi (Add/Edit/Delete/Export) ada di toolbar di luar grid, bukan kolom Action per baris.
        // Double-click baris = Edit.
        var columns = [col.rowNumber()].concat(cfg.columns).concat(col.audit());

        $(function () {
            page.grid = createGrid("#datagrid", {
                key: cfg.key,
                url: cfg.urls.search,
                params: cfg.params,
                columns: columns,
                gridOptions: {
                    onRowDblClick: function (e) { if (cfg.canEdit) page.edit(e.data[cfg.key]); }
                },
                onSelectionChanged: function (rows) {
                    $("#btn_edit").prop("disabled", rows.length !== 1);
                    $("#btn_delete").prop("disabled", rows.length < 1);
                    if (cfg.onSelectionChanged) cfg.onSelectionChanged(rows);
                }
            });

            $("#btn_add").on("click", page.add);
            $("#btn_edit").on("click", function () {
                var rows = page.grid.getSelectedRowsData();
                if (rows.length === 1) page.edit(rows[0][cfg.key]);
            });
            $("#btn_delete").on("click", function () { page.remove(page.grid.getSelectedRowKeys()); });
            $("#btn_refresh").on("click", page.search);
            $("#btn_export").on("click", function () { window.location.href = cfg.urls.exportExcel; });
            $("#btn_save").on("click", page.save);
        });

        return page;
    }

    // ---------- Select2 (AJAX, format response Lookup: { items: [{id, text}] }) ----------
    // parent: modal tempat select berada (wajib untuk select di dalam modal agar kolom search bisa diketik).
    // extra : parameter tambahan ke server, mis. { param: "ASSET" }.
    function select2(selector, url, placeholder, parent, extra) {
        var pageLimit = 20;
        $(selector).select2({
            ajax: {
                url: url,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return $.extend({ q: params.term, pageLimit: pageLimit, page: params.page || 1 }, extra || {});
                },
                processResults: function (data) {
                    var items = data.items || [];
                    return { results: items, pagination: { more: items.length >= pageLimit } };
                }
            },
            allowClear: true,
            placeholder: placeholder,
            width: "100%",
            dropdownParent: parent ? $(parent) : $(document.body)
        });
    }

    // Set nilai select2 AJAX (option belum ada di DOM) - id kosong = kosongkan.
    function setSelect2Value(selector, id, text) {
        var $el = $(selector);
        $el.find("option").remove();
        if (id !== null && id !== undefined && id !== "") {
            $el.append(new Option(text, id, true, true));
        }
        $el.trigger("change");
    }

    // ---------- Util ----------
    function escapeHtml(text) {
        return $("<div>").text(text == null ? "" : text).html();
    }

    // Pesan dari server bisa memuat input user: escape dulu, baris baru jadi <br>.
    function formatMessage(text) {
        return escapeHtml(text).replace(/\n/g, "<br>");
    }

    return {
        post: post,
        gridHeight: gridHeight,
        filterDefaults: filterDefaults,
        createGrid: createGrid,
        col: col,
        crudPage: crudPage,
        select2: select2,
        setSelect2Value: setSelect2Value,
        escapeHtml: escapeHtml,
        formatMessage: formatMessage
    };
})(jQuery);
