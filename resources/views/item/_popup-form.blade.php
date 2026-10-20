<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-cube"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Add Item</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Register a new item</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Item Code <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditCode" type="text" maxlength="20" oninput="removeWhiteSpace(this)" placeholder="e.g. 10030010" style="text-transform: uppercase;" />
                        <div class="dpf-hint">Cannot be changed later.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Item Name <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="150" placeholder="Enter item name" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Unit / Category</div>
                        </div>
                    </div>
                    <div class="dpf-row-right item-pair">
                        <input class="form-control" id="addEditUnit" type="text" maxlength="20" placeholder="Unit, e.g. PCS" />
                        <input class="form-control" id="addEditCategory" type="text" maxlength="50" list="itemCategoryList" placeholder="Category" style="text-transform: uppercase;" />
                        <datalist id="itemCategoryList">
                            @foreach ($categories as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Tool / Drawing Number</div>
                        </div>
                    </div>
                    <div class="dpf-row-right item-pair">
                        <input class="form-control" id="addEditTool" type="text" maxlength="100" placeholder="Tool number" />
                        <input class="form-control" id="addEditDrawing" type="text" maxlength="100" placeholder="Drawing number" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Time Control</div>
                        </div>
                    </div>
                    <div class="dpf-row-right item-pair">
                        <input class="form-control" id="addEditTcFrom" type="date" />
                        <input class="form-control" id="addEditTcTo" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div>
                            <div class="dpf-label">Remark</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditRemark" type="text" maxlength="255" />
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
