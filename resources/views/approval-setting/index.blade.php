{{-- Approver tetap per tahap approval APR. Tahap & urutan dari seeder; yang diubah di sini hanya approver & status aktif. --}}
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
                <span class="text-muted" style="font-size:12px;"><i class="fa fa-info-circle"></i> Documents already in approval use the new approver from the step they are currently on.</span>
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
                <div class="dpf-header-icon"><i class="fa fa-sort-amount-asc"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Edit Approval Step</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <input id="addEditId" type="hidden" />
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-list-ol"></i></div>
                        <div>
                            <div class="dpf-label">Step</div>
                            <div class="dpf-sub">Order and name are fixed</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditStep" type="text" disabled />
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="dpf-label">Approver <span class="text-red">*</span></div>
                            <div class="dpf-sub">Gets APR documents at this step in the Approval Inbox</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditApprover"></select>
                        <div class="dpf-hint">The user's role must include the Approval Inbox menu.</div>
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
                            <div class="dpf-sub">Inactive steps are skipped</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <label class="sg-switch"><input type="checkbox" id="addEditIsActive" checked /> Active</label>
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
    var gPage = HMA.crudPage({
        entity: "Approval Step",
        key: "StepId",
        keyParam: "stepId",
        canEdit: @json($edit),
        canDelete: false,
        urls: {
            search: "{{ url('/ApprovalSetting/Search') }}",
            getByKey: "{{ url('/ApprovalSetting/GetByKey') }}",
            save: "{{ url('/ApprovalSetting/AddEdit') }}",
            remove: "",
            exportExcel: ""
        },
        columns: [
            { dataField: "StepNo", caption: "Step", dataType: "number", alignment: "center", width: 70 },
            { dataField: "StepName", caption: "Step Name" },
            { dataField: "SignGroup", caption: "Signs As", width: 110, alignment: "center" },
            { dataField: "ApproverName", caption: "Approver" },
            { dataField: "ApproverUsername", caption: "Username" },
            HMA.col.status()
        ],
        resetForm: function () {},
        fillForm: function (data) {
            $("#addEditId").val(data.StepId);
            $("#addEditStep").val(data.StepNo + ". " + data.StepName + " (" + data.SignGroup + ")");
            $("#modal-subtitle").text("Set the approver for " + data.StepName);
            HMA.setSelect2Value("#addEditApprover", data.ApproverUserId, data.ApproverName ? data.ApproverName + " (" + data.ApproverUsername + ")" : "");
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                StepId: $("#addEditId").val(),
                ApproverUserId: $("#addEditApprover").val() || "",
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });

    $(function () {
        HMA.select2("#addEditApprover", "{{ url('/Lookup/UserList') }}", "Choose user", "#modalAddEdit");
    });
</script>
@endsection
