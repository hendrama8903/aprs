{{--
    Popup upload file export OGS + popup hasil upload (warning). Dipakai menu Item, Item Price, PO Transaction.
    Pasangannya di controller: App\Http\Controllers\Concerns\HandlesOgsUpload.

    Parameter:
      $title     : judul, mis. "Item Master"
      $baseUrl   : prefix route, mis. url('/Item') -> /Upload, /DownloadTemplate, /ImportDetail
      $hint      : penjelasan cara data diperbarui
    Tombol pemicu: #btn_upload di toolbar. Setelah upload sukses dipanggil window.onOgsUploaded() kalau ada.
--}}
<div class="modal fade sg-modal" id="modalUpload" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-upload"></i></div>
                <div>
                    <div class="dpf-header-title">Upload {{ $title }}</div>
                    <div class="dpf-header-sub">Excel export from OGS (.xlsx / .xls)</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">File <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="uploadFile" type="file" accept=".xlsx,.xls" />
                        <a href="{{ $baseUrl }}/DownloadTemplate" class="ogs-template"><i class="fa fa-download"></i> Download template</a>
                        <div class="dpf-hint">Max 20 MB. {{ $hint }}</div>
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

<div class="modal fade sg-modal" id="modalUploadResult" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <div>
                    <div class="dpf-header-title">Upload Result</div>
                    <div class="dpf-header-sub" id="uploadResultSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <ul id="uploadResultWarnings" class="ogs-warnings"></ul>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
            </div>
        </div>
    </div>
</div>

@push('ogs-upload-scripts')
<style>
    .ogs-last { display: inline-block; margin-right: 10px; font-size: 12px; color: #6b7280; }
    .ogs-template { display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 600; }
    .ogs-warnings { max-height: 420px; overflow-y: auto; padding-left: 18px; margin: 0; }
    .ogs-warnings li { margin-bottom: 6px; word-break: break-word; }
</style>
<script type="text/javascript">
    (function () {
        var baseUrl = @json($baseUrl), title = @json($title);

        function showResult(importId) {
            HMA.post(baseUrl + "/ImportDetail", { importId: importId }, function (result) {
                var d = result.data;
                $("#uploadResultSub").text(d.FileName + " - " + d.TotalRows + " rows, " + d.InsertedRows + " inserted, "
                    + d.UpdatedRows + " updated, " + d.SkippedRows + " skipped, " + d.WarningCount + " warning(s)");
                var $list = $("#uploadResultWarnings").empty();
                if (!d.Warnings.length) $list.append($("<li>").text("No warnings."));
                $.each(d.Warnings, function (_, w) { $list.append($("<li>").text(w)); });
                if (d.WarningCount > d.Warnings.length) {
                    $list.append($("<li>").text("... and " + (d.WarningCount - d.Warnings.length) + " more."));
                }
                $("#modalUploadResult").modal("show");
            }, { successMessage: false });
        }

        $(function () {
            $("#btn_upload").on("click", function () {
                $("#uploadFile").val("");
                $("#modalUpload").modal("show");
            });

            $("#btn_doUpload").on("click", function () {
                var file = $("#uploadFile")[0].files[0];
                if (!file) { showErrorMesgGrowl("Please choose a file to upload."); return; }

                showConfirmMesg("Upload " + HMA.escapeHtml(file.name) + " to <b>" + HMA.escapeHtml(title) + "</b>?", function () {
                    var form = new FormData();
                    form.append("file", file);

                    popUpProgressShow();
                    $.ajax({
                        type: "POST",
                        url: baseUrl + "/Upload",
                        data: form,
                        processData: false,
                        contentType: false,
                        dataType: "json"
                    }).done(function (result) {
                        popUpProgressHide();
                        if (result.status === true) {
                            showSuccessMesgGrowl(HMA.formatMessage(result.message));
                            $("#modalUpload").modal("hide");
                            if (typeof window.onOgsUploaded === "function") window.onOgsUploaded();
                            if (result.returnId) showResult(result.returnId);
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
    })();
</script>
@endpush
