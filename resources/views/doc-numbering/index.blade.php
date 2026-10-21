{{-- Nomor urut dokumen per jenis & tahun. Dipakai untuk melanjutkan nomor dokumen manual saat mulai memakai sistem. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Edit</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <span class="text-muted" style="font-size:12px;"><i class="fa fa-info-circle"></i> Numbers restart from 1 every year. Numbers already used by documents cannot be reused.</span>
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-sort-numeric-asc"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Edit Document Numbering</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <input id="addEditId" type="hidden" />
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Document</div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditDocType" type="text" disabled />
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Year</div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditYear" type="text" disabled />
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Next Number <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditNextNo" type="number" min="1" step="1" />
                        <div class="dpf-hint" id="nextDocNoPreview"></div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_save"><i class="fa fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    var gPattern = "";

    // Preview nomor dokumen berikutnya, padding 3 digit seperti DocNumber::format().
    function renderPreview() {
        var no = parseInt($("#addEditNextNo").val(), 10);
        $("#nextDocNoPreview").text(no > 0 ? "Next document number: " + gPattern.replace("{no}", String(no).padStart(3, "0")) : "");
    }

    var gPage = HMA.crudPage({
        entity: "Document Numbering",
        key: "CounterKey",
        keyParam: "counterKey",
        canEdit: @json($edit),
        canDelete: false,
        urls: {
            search: "{{ url('/DocNumbering/Search') }}",
            getByKey: "{{ url('/DocNumbering/GetByKey') }}",
            save: "{{ url('/DocNumbering/AddEdit') }}",
            remove: "",
            exportExcel: ""
        },
        columns: [
            { dataField: "Year", caption: "Year", dataType: "number", format: "0", alignment: "center", width: 80 },
            { dataField: "DocType", caption: "Document" },
            { dataField: "LastNo", caption: "Last Number", dataType: "number", alignment: "center", width: 120 },
            { dataField: "NextNo", caption: "Next Number", dataType: "number", alignment: "center", width: 120 },
            { dataField: "NextDocNo", caption: "Next Document No" }
        ],
        focusEdit: "#addEditNextNo",
        resetForm: function () {},
        fillForm: function (data) {
            $("#addEditId").val(data.CounterKey);
            $("#addEditDocType").val(data.DocType);
            $("#addEditYear").val(data.Year);
            $("#addEditNextNo").val(data.NextNo);
            $("#modal-subtitle").text("Set the next number for " + data.Year);
            gPattern = data.Pattern;
            renderPreview();
        },
        collectForm: function () {
            return {
                CounterKey: $("#addEditId").val(),
                NextNo: $("#addEditNextNo").val()
            };
        }
    });

    $(function () {
        $("#addEditNextNo").on("input", renderPreview);
    });
</script>
@endsection
