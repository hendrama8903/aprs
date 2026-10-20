<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-truck"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Add Supplier</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Register a new supplier</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Supplier Code <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditCode" type="text" maxlength="10" oninput="removeWhiteSpace(this)" placeholder="e.g. 1003" style="text-transform: uppercase;" />
                        <div class="dpf-hint">Cannot be changed later.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Supplier Name <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="100" placeholder="Enter supplier name" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Status</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <label class="sg-switch"><input type="checkbox" id="addEditIsActive" checked /> Active</label>
                    </div>
                </div>

                <div class="sup-addr-head">
                    <div class="dpf-label">Letter Addresses</div>
                </div>

                <div id="addressList" class="sup-addr-list"></div>

                {{-- Form alamat: untuk Add tampil di sini (di bawah daftar); untuk Edit dipindah ke posisi kartu yang diedit. --}}
                <div id="addressFormHome">
                <div class="sup-addr-form" id="addressForm" style="display:none">
                    <input type="hidden" id="addrIndex" />
                    <div class="sup-addr-grid">
                        <div>
                            <label>Label <span class="text-red">*</span></label>
                            <input class="form-control" id="addrLabel" type="text" maxlength="50" placeholder="e.g. Head Office, Plant Cikarang" />
                        </div>
                        <div>
                            <label>Plant Code</label>
                            <input class="form-control" id="addrPlantCode" type="text" maxlength="5" oninput="removeWhiteSpace(this)" placeholder="Supplier plant code in OGS, e.g. 01" style="text-transform: uppercase;" />
                        </div>
                        <div class="sup-span2">
                            <label>Address <span class="text-red">*</span></label>
                            <textarea class="form-control" id="addrAddress" rows="3" maxlength="255" placeholder="Street, city, postal code"></textarea>
                        </div>
                        <div>
                            <label>Attention</label>
                            <input class="form-control" id="addrAttentionName" type="text" maxlength="100" placeholder="e.g. Mr. Iwan Suriana" />
                        </div>
                        <div>
                            <label>Attention Title</label>
                            <input class="form-control" id="addrAttentionTitle" type="text" maxlength="100" placeholder="e.g. Director" />
                        </div>
                        <div>
                            <label>Email</label>
                            <input class="form-control" id="addrEmail" type="email" maxlength="100" />
                        </div>
                        <div>
                            <label class="sg-switch"><input type="checkbox" id="addrIsActive" checked /> Active</label>
                        </div>
                    </div>
                    <div class="sup-addr-form-actions">
                        <button type="button" class="dp-btn dp-btn-outline" id="btn_addrCancel">Cancel</button>
                        <button type="button" class="dp-btn dp-btn-primary" id="btn_addrOk"><i class="fa fa-check"></i> OK</button>
                    </div>
                </div>
                </div>

                <button type="button" class="sup-addr-add" id="btn_addAddress"><i class="fa fa-plus"></i> Add Address</button>
            </div>

            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_save"><i class="fa fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>
