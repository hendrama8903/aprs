@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($upload)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_upload"><i class="fa fa-upload"></i> Upload OGS File</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                @foreach (\App\Models\OgsImport::TypeLabels as $type => $label)
                    @php($last = $lastImports[$type])
                    <span class="ogs-last" title="Last {{ strtolower($label) }} import">
                        <i class="fa fa-clock-o"></i> {{ $label }}:
                        @if ($last)
                            <b>{{ $last->CreatedDate->format('d-m-Y H:i') }}</b> by {{ $last->CreatedBy }}
                        @else
                            <b class="text-red">never imported</b>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalUpload" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-upload"></i></div>
                <div>
                    <div class="dpf-header-title">Upload OGS File</div>
                    <div class="dpf-header-sub">Excel export from OGS (.xlsx / .xls)</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-list"></i></div>
                        <div>
                            <div class="dpf-label">Import Type <span class="text-red">*</span></div>
                            <div class="dpf-sub">Which OGS export is this file</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="uploadType">
                            <option value="ITEM">Item Master</option>
                            <option value="PRICE">Price Master</option>
                            <option value="PO">PO Transaction</option>
                        </select>
                        <div class="dpf-hint" id="uploadTypeHint"></div>
                        <a href="#" class="ogs-template" id="btn_template"><i class="fa fa-download"></i> <span id="templateLabel">Download template</span></a>
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-file-excel-o"></i></div>
                        <div>
                            <div class="dpf-label">File <span class="text-red">*</span></div>
                            <div class="dpf-sub">Max 20 MB</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="uploadFile" type="file" accept=".xlsx,.xls" />
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doUpload"><i class="fa fa-upload"></i> Upload</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalDetail" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <div>
                    <div class="dpf-header-title" id="detailTitle">Import Detail</div>
                    <div class="dpf-header-sub" id="detailSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <ul id="detailWarnings" class="ogs-warnings"></ul>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .ogs-last { display: inline-block; margin-left: 14px; font-size: 12px; color: #6b7280; }
    .ogs-template { display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 600; }
    .ogs-warnings { max-height: 420px; overflow-y: auto; padding-left: 18px; margin: 0; }
    .ogs-warnings li { margin-bottom: 6px; word-break: break-word; }
</style>
<script type="text/javascript">
    var gGrid;
    var gTypeHints = {
        ITEM: "Items are inserted or updated by item code. Items missing from the file are kept.",
        PRICE: "Replaces ALL price data with this file. Suppliers are created or renamed automatically.",
        PO: "PO lines are inserted or updated by PO No + PO Line, so overlapping periods are safe. Columns: PO NO, PO LINE, PO DATE, ITEM CODE, SUPPLIER CODE, SUPPLIER NAME, CURRENCY, QTY, UNIT PRICE."
    };
    var gTypeNames = { ITEM: "Item Master", PRICE: "Price Master", PO: "PO Transaction" };

    function showDetail(importId) {
        HMA.post("{{ url('/OgsImport/GetByKey') }}", { importId: importId }, function (result) {
            var d = result.data;
            $("#detailTitle").text(gTypeNames[d.ImportType] + " Import #" + d.ImportId);
            $("#detailSub").text(d.FileName + " - " + d.TotalRows + " rows, " + d.WarningCount + " warning(s)");
            var $list = $("#detailWarnings").empty();
            if (!d.Warnings.length) {
                $list.append($("<li>").text("No warnings."));
            }
            $.each(d.Warnings, function (_, w) { $list.append($("<li>").text(w)); });
            if (d.WarningCount > d.Warnings.length) {
                $list.append($("<li>").text("... and " + (d.WarningCount - d.Warnings.length) + " more."));
            }
            $("#modalDetail").modal("show");
        }, { successMessage: false });
    }

    $(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "ImportId",
            url: "{{ url('/OgsImport/Search') }}",
            gridOptions: { selection: { mode: "single" } },
            columns: [
                HMA.col.rowNumber(),
                HMA.col.date("CreatedDate", "Import Date", true),
                { dataField: "ImportType", caption: "Type", width: 90, alignment: "center" },
                { dataField: "FileName", caption: "File Name" },
                { dataField: "TotalRows", caption: "Rows", dataType: "number", width: 80 },
                { dataField: "InsertedRows", caption: "Inserted", dataType: "number", width: 90 },
                { dataField: "UpdatedRows", caption: "Updated", dataType: "number", width: 90 },
                { dataField: "SkippedRows", caption: "Skipped", dataType: "number", width: 90 },
                {
                    dataField: "WarningCount", caption: "Warnings", dataType: "number", width: 100,
                    cellTemplate: function (container, options) {
                        if (!options.value) { container.text("0"); return; }
                        $("<a href='#'>").text(options.value + " - view")
                            .on("click", function (e) { e.preventDefault(); showDetail(options.data.ImportId); })
                            .appendTo(container);
                    }
                },
                { dataField: "CreatedBy", caption: "Imported By", width: 120 }
            ]
        });

        $("#btn_refresh").on("click", function () { gGrid.refresh(); });

        $("#uploadType").on("change", function () {
            var type = $(this).val();
            $("#uploadTypeHint").text(gTypeHints[type]);
            $("#templateLabel").text("Download " + gTypeNames[type] + " template");
        }).trigger("change");

        $("#btn_template").on("click", function (e) {
            e.preventDefault();
            window.location.href = "{{ url('/OgsImport/DownloadTemplate') }}?importType=" + $("#uploadType").val();
        });

        $("#btn_upload").on("click", function () {
            $("#uploadFile").val("");
            $("#modalUpload").modal("show");
        });

        $("#btn_doUpload").on("click", function () {
            var file = $("#uploadFile")[0].files[0];
            if (!file) {
                showErrorMesgGrowl("Please choose a file to upload.");
                return;
            }
            var type = $("#uploadType").val();
            var label = $("#uploadType option:selected").text();
            showConfirmMesg("Import " + HMA.escapeHtml(file.name) + " as <b>" + label + "</b>?<br>" + gTypeHints[type], function () {
                var form = new FormData();
                form.append("importType", type);
                form.append("file", file);

                popUpProgressShow();
                $.ajax({
                    type: "POST",
                    url: "{{ url('/OgsImport/Upload') }}",
                    data: form,
                    processData: false,
                    contentType: false,
                    dataType: "json"
                }).done(function (result) {
                    popUpProgressHide();
                    if (result.status === true) {
                        showSuccessMesgGrowl(HMA.formatMessage(result.message));
                        $("#modalUpload").modal("hide");
                        gGrid.refresh();
                        if (result.returnId) showDetail(result.returnId);
                    } else {
                        showErrorMesgGrowl(HMA.formatMessage(result.message));
                    }
                }).fail(function () {
                    popUpProgressHide();
                    showErrorMesgGrowl("Upload failed. Please try again.");
                });
            }, "Confirmation");
        });
    });
</script>
@endsection
