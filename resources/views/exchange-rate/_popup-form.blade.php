<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-exchange"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Add Exchange Rate</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Register a new exchange rate</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Currency <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditCurrency">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Rate (IDR) <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditRate" type="number" min="0" step="any" placeholder="IDR per 1 unit, e.g. 16500" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Valid Period <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right er-pair">
                        <input class="form-control" id="addEditValidFrom" type="date" />
                        <input class="form-control" id="addEditValidTo" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Remark</div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditRemark" type="text" maxlength="255" placeholder="e.g. Budget rate FY2026" />
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
<style>
    .er-pair { display: flex; gap: 8px; }
    .er-pair > .form-control { flex: 1 1 0; min-width: 0; }
</style>
