{{-- Supplier dari OGS: tanpa Add/Delete, Edit hanya untuk data surat Price Confirmation. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Edit</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_toggleFilters"><i class="fa fa-filter"></i> Filters</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
            </div>
        </div>
        <div class="dp-filter-bar" id="filterBar">
            <div class="sg-filter">
                <label for="filterStatus">Status</label>
                <select class="form-control" id="filterStatus">
                    <option value="">All</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
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
                <div class="dpf-header-icon"><i class="fa fa-truck"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Edit Supplier</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Update supplier data</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-tag"></i></div>
                        <div>
                            <div class="dpf-label">Supplier</div>
                            <div class="dpf-sub">From OGS, cannot be changed here</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" disabled />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-map-marker"></i></div>
                        <div>
                            <div class="dpf-label">Address</div>
                            <div class="dpf-sub">Printed on the Price Confirmation letter</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <textarea class="form-control" id="addEditAddress" rows="3" maxlength="255" placeholder="Street, city, postal code"></textarea>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="dpf-label">Attention</div>
                            <div class="dpf-sub">Letter recipient and title</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditAttentionName" type="text" maxlength="100" placeholder="e.g. Mr. Iwan Suriana" />
                        <input class="form-control" id="addEditAttentionTitle" type="text" maxlength="100" placeholder="e.g. Director" style="margin-top: 6px;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-envelope-o"></i></div>
                        <div>
                            <div class="dpf-label">Email</div>
                            <div class="dpf-sub">Optional</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditEmail" type="email" maxlength="100" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
                            <div class="dpf-sub">Inactive suppliers are hidden from dropdowns</div>
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
        entity: "Supplier",
        key: "SupplierId",
        keyParam: "supplierId",
        canEdit: @json($edit),
        canDelete: false,
        urls: {
            search: "{{ url('/Supplier/Search') }}",
            getByKey: "{{ url('/Supplier/GetByKey') }}",
            save: "{{ url('/Supplier/AddEdit') }}",
            remove: "",
            exportExcel: "{{ url('/Supplier/DownloadExcel') }}"
        },
        params: function () {
            return { isActive: $("#filterStatus").val() };
        },
        columns: [
            { dataField: "SupplierCode", caption: "Supplier Code", fixed: true, fixedPosition: "left", width: 120 },
            { dataField: "SupplierName", caption: "Supplier Name" },
            { dataField: "Address", caption: "Address", visible: false },
            { dataField: "AttentionName", caption: "Attention" },
            { dataField: "AttentionTitle", caption: "Title", visible: false },
            { dataField: "Email", caption: "Email", visible: false },
            { dataField: "PriceCount", caption: "Price Rows", dataType: "number", alignment: "center", width: 100 },
            { dataField: "ActivePriceCount", caption: "Active Price", dataType: "number", alignment: "center", width: 110 },
            HMA.col.status()
        ],
        focusEdit: "#addEditAddress",
        resetForm: function () {},
        fillForm: function (data) {
            $("#addEditId").val(data.SupplierId);
            $("#addEditName").val(data.SupplierCode + " - " + data.SupplierName);
            $("#addEditAddress").val(data.Address);
            $("#addEditAttentionName").val(data.AttentionName);
            $("#addEditAttentionTitle").val(data.AttentionTitle);
            $("#addEditEmail").val(data.Email);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                SupplierId: $("#addEditId").val(),
                Address: $("#addEditAddress").val(),
                AttentionName: $("#addEditAttentionName").val(),
                AttentionTitle: $("#addEditAttentionTitle").val(),
                Email: $("#addEditEmail").val(),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });

    $(function () {
        $("#filterStatus").on("change", gPage.search);
    });
</script>
@endsection
