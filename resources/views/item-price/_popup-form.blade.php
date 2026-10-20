<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-tags"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Add Price</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Register a new price</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="ip-note"><i class="fa fa-info-circle"></i> The next price master upload from OGS replaces all prices, including prices added or edited here.</div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Item <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditItem"></select>
                        <input class="form-control" id="addEditItemText" type="text" disabled style="display:none" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Supplier <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditSupplier"></select>
                        <input class="form-control" id="addEditSupplierText" type="text" disabled style="display:none" />
                        <div class="dpf-hint" id="itemSupplierHint">Item and supplier cannot be changed later.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Plant Code</div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditPlant" type="text" maxlength="5" oninput="removeWhiteSpace(this)" placeholder="e.g. 01" style="text-transform: uppercase;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Currency / Price <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right ip-pair">
                        <select class="form-control ip-currency" id="addEditCurrency">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                        <input class="form-control" id="addEditPrice" type="number" min="0" step="any" placeholder="Price" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Quotation</div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditQuotation" type="text" maxlength="50" placeholder="e.g. PC APR26-MAR27" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">Time Control <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right ip-pair">
                        <input class="form-control" id="addEditTcFrom" type="date" />
                        <input class="form-control" id="addEditTcTo" type="date" />
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
