<style>
    .acc-list { list-style: none; padding: 0; margin: 0; }
    .acc-list .acc-list { padding-left: 28px; }
    .acc-item { padding: 9px 0; border-bottom: 1px solid #EEF1F5; }
    .acc-list .acc-list .acc-item:last-child { border-bottom: none; padding-bottom: 0; }
    .acc-menu { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13px; color: #1D2939; margin: 0; cursor: pointer; }
    .acc-menu i { color: #2450D8; width: 16px; text-align: center; }
    .acc-perms { display: flex; flex-wrap: wrap; gap: 6px 16px; padding: 6px 0 0 30px; }
    .acc-perms label { font-weight: 500; font-size: 12px; color: #475467; margin: 0; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
    .acc-note { font-size: 11.5px; color: #98A2B3; padding: 10px 0 2px; }
</style>

<div class="modal fade sg-modal" id="modalAccess" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-key"></i></div>
                <div>
                    <div class="dpf-header-title">Set Access</div>
                    <div class="dpf-header-sub" id="accessRoleName"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input type="hidden" id="accessRoleId" />
                <ul class="acc-list" id="accessTree"></ul>
                <div class="acc-note"><i class="fa fa-info-circle"></i> Changes apply the next time users with this role log in.</div>
            </div>

            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_accessSave"><i class="fa fa-save"></i> Save Access</button>
            </div>
        </div>
    </div>
</div>
