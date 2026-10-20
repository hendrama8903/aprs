<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-user"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Add User</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Register a new user</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-sign-in"></i></div>
                        <div>
                            <div class="dpf-label">Login Type <span class="text-red">*</span></div>
                            <div class="dpf-sub">How the user signs in</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <label class="radio-inline"><input type="radio" name="addEditLoginType" value="AD"> Active Directory</label>
                        <label class="radio-inline"><input type="radio" name="addEditLoginType" value="LOCAL" checked> Local account</label>
                        <div class="dpf-hint">Active Directory login is not available yet in this version &mdash; use a Local account.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-at"></i></div>
                        <div>
                            <div class="dpf-label">Username <span class="text-red">*</span></div>
                            <div class="dpf-sub">Cannot be changed later</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditUsername" type="text" maxlength="50" oninput="removeWhiteSpace(this)" placeholder="e.g. john.doe" autocomplete="off" />
                    </div>
                </div>

                <div class="dpf-row" id="rowPassword">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-lock"></i></div>
                        <div>
                            <div class="dpf-label">Password <span class="text-red" id="passwordRequired">*</span></div>
                            <div class="dpf-sub">Minimum 8 characters</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditPassword" type="password" maxlength="100" autocomplete="new-password" />
                        <div class="dpf-hint" id="passwordHint">Required for local accounts.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-card-o"></i></div>
                        <div>
                            <div class="dpf-label">Full Name <span class="text-red">*</span></div>
                            <div class="dpf-sub">Employee No (optional)</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditFullName" type="text" maxlength="100" placeholder="Full name" />
                        <input class="form-control" id="addEditEmployeeNo" type="text" maxlength="20" placeholder="Employee No" style="margin-top: 8px;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-envelope-o"></i></div>
                        <div>
                            <div class="dpf-label">Contact</div>
                            <div class="dpf-sub">Email &amp; phone (optional)</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditEmail" type="email" maxlength="100" placeholder="Email" />
                        <input class="form-control" id="addEditPhone" type="text" maxlength="20" placeholder="Phone" style="margin-top: 8px;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user-circle-o"></i></div>
                        <div>
                            <div class="dpf-label">Direct Supervisor</div>
                            <div class="dpf-sub">First approver of item requests</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditSupervisor"></select>
                        <div class="dpf-hint">If empty, this user's item requests go straight to GAD approval.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-key"></i></div>
                        <div>
                            <div class="dpf-label">Role <span class="text-red">*</span></div>
                            <div class="dpf-sub">Determines accessible menus</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditRole"></select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
                            <div class="dpf-sub">Inactive users cannot log in</div>
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

<div class="modal fade sg-modal" id="modalResetPassword" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-key"></i></div>
                <div>
                    <div class="dpf-header-title">Reset Password</div>
                    <div class="dpf-header-sub" id="resetPasswordUser"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body" style="padding: 14px 22px;">
                <input type="hidden" id="resetPasswordId" />
                <div class="form-group">
                    <label for="resetPasswordNew">New Password</label>
                    <input type="password" class="form-control" id="resetPasswordNew" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="resetPasswordConfirm">Confirm New Password</label>
                    <input type="password" class="form-control" id="resetPasswordConfirm" autocomplete="new-password">
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_resetPasswordSave"><i class="fa fa-save"></i> Reset</button>
            </div>
        </div>
    </div>
</div>
