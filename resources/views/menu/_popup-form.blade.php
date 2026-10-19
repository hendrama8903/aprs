<style>
    #modalAddEdit .modal-dialog { width: 680px; max-width: calc(100vw - 24px); }
    .mnf-body { padding: 16px 22px 4px; max-height: calc(100vh - 210px); overflow-y: auto; }

    /* ---------- Breadcrumb lokasi di header ---------- */
    .mnf-path { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; font-size: 12px; color: rgba(255,255,255,.85); margin-top: 2px; }
    .mnf-path i.fa-angle-right { opacity: .6; }
    .mnf-path b { color: #fff; font-weight: 600; }

    /* ---------- Field ---------- */
    .mnf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
    .mnf-field { margin-bottom: 14px; min-width: 0; }
    .mnf-field.full { grid-column: 1 / -1; }
    .mnf-label { display: block; font-size: 12px; font-weight: 700; color: #344054; margin: 0 0 5px; }
    .mnf-label .req { color: #D92D20; }
    .mnf-label .opt { color: #98A2B3; font-weight: 400; }
    .mnf-help { font-size: 11px; color: #98A2B3; margin-top: 4px; line-height: 1.35; }
    .mnf-error { display: none; font-size: 11px; color: #D92D20; margin-top: 4px; }
    .mnf-field.invalid .form-control { border-color: #D92D20; box-shadow: 0 0 0 3px rgba(217,45,32,.08); }
    .mnf-field.invalid .mnf-error { display: block; }
    .mnf-field.invalid .mnf-help { display: none; }

    .mnf-input { position: relative; }
    .mnf-input > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #98A2B3; font-size: 13px; pointer-events: none; }
    .mnf-input > .form-control { padding-left: 32px; }
    .mnf-mono { font-family: "IBM Plex Mono", monospace; font-size: 12.5px; }

    /* ---------- Icon picker (dropdown) ---------- */
    .mnf-icon-wrap { position: relative; }
    .mnf-icon-wrap .form-control { padding-right: 38px; }
    .mnf-icon-toggle {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        width: 30px; height: 26px; border: none; border-radius: 6px; background: #F2F4F7; color: #475467; cursor: pointer;
    }
    .mnf-icon-toggle:hover, .mnf-icon-wrap.open .mnf-icon-toggle { background: #2450D8; color: #fff; }
    .mnf-icon-pop {
        display: none; position: absolute; z-index: 20; top: calc(100% + 6px); right: 0; width: 300px; max-width: 80vw;
        background: #fff; border: 1px solid #E3E8EF; border-radius: 12px; padding: 10px; box-shadow: 0 12px 28px rgba(16,24,40,.16);
    }
    .mnf-icon-wrap.open .mnf-icon-pop { display: block; }

    .mnf-icons { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; }
    .mnf-icon-opt {
        height: 32px; border: 1px solid #E3E8EF; border-radius: 8px; background: #fff; color: #475467;
        display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; padding: 0;
    }
    .mnf-icon-opt:hover { border-color: #2450D8; color: #2450D8; }
    .mnf-icon-opt.active { background: #2450D8; border-color: #2450D8; color: #fff; }

    /* ---------- Footer ---------- */
    .mnf-footer { display: flex; align-items: center; gap: 10px; padding: 12px 22px; border-top: 1px solid #EEF1F5; background: #fff; }
    .mnf-footer .mnf-status small { display: block; font-size: 10.5px; color: #98A2B3; font-weight: 400; }
    .mnf-kbd { font-size: 10.5px; color: #98A2B3; margin-right: 4px; }

    @media (max-width: 640px) {
        .mnf-grid { grid-template-columns: 1fr; }
        .mnf-kbd { display: none; }
    }
</style>

<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" aria-labelledby="modal-title" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-sitemap" id="modal-icon"></i></div>
                <div style="min-width:0;">
                    <div class="dpf-header-title" id="modal-title">Add Menu</div>
                    <div class="mnf-path" id="modal-path"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
            </div>

            <form class="mnf-body" id="menuForm" autocomplete="off" novalidate>
                <input id="addEditId" type="hidden" />

                <div class="mnf-grid">
                    <!-- 1. Tipe -->
                    <div class="mnf-field" id="fieldType">
                        <label class="mnf-label" for="addEditType">Type <span class="req">*</span></label>
                        <div class="mnf-input"><i class="fa fa-folder-o" id="typeGlyph"></i>
                            <select class="form-control" id="addEditType">
                                <option value="MENU">Menu</option>
                                <option value="SCREEN">Screen</option>
                                <option value="BUTTON">Button</option>
                            </select>
                        </div>
                        <div class="mnf-help" id="typeHelp"></div>
                    </div>

                    <!-- 2. Lokasi -->
                    <div class="mnf-field" id="fieldParent">
                        <label class="mnf-label" for="addEditParent"><span id="parentLabel">Parent Menu</span> <span class="opt" id="parentOpt">(optional)</span></label>
                        <div class="mnf-input"><i class="fa fa-level-up"></i><select class="form-control" id="addEditParent"></select></div>
                        <div class="mnf-help" id="parentHelp"></div>
                        <div class="mnf-error">Please select a screen.</div>
                    </div>

                    <!-- 3a. Preset aksi (button) -->
                    <div class="mnf-field full mn-only-button presetable">
                        <label class="mnf-label" for="addEditPreset">Quick pick <span class="opt">(optional)</span></label>
                        <div class="mnf-input"><i class="fa fa-bolt" id="presetGlyph"></i>
                            <select class="form-control" id="addEditPreset"></select>
                        </div>
                        <div class="mnf-help">Fills the button name and code. Choose Custom to type your own.</div>
                    </div>

                    <!-- 3. Nama + icon / kode -->
                    <div class="mnf-field" id="fieldName">
                        <label class="mnf-label" for="addEditName"><span id="nameLabel">Menu Name</span> <span class="req">*</span></label>
                        <div class="mnf-input"><i class="fa fa-font"></i><input class="form-control" id="addEditName" type="text" maxlength="100" placeholder="e.g. Report" /></div>
                        <div class="mnf-error">Name is required.</div>
                    </div>

                    <div class="mnf-field mn-only-menu">
                        <label class="mnf-label" for="addEditIcon">Icon <span class="opt">(Font Awesome 4)</span></label>
                        <div class="mnf-icon-wrap">
                            <div class="mnf-input">
                                <i class="fa fa-circle-o" id="iconInputGlyph"></i>
                                <input class="form-control mnf-mono" id="addEditIcon" type="text" maxlength="50" placeholder="fa-folder-o" />
                                <button type="button" class="mnf-icon-toggle" id="btn_iconToggle" title="Choose icon" aria-label="Choose icon"><i class="fa fa-th"></i></button>
                            </div>
                            <div class="mnf-icon-pop" id="iconPop">
                                <div class="mnf-icons" id="iconPalette"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mnf-field mn-only-button" id="fieldCode">
                        <label class="mnf-label" for="addEditCode">Button Code <span class="req">*</span></label>
                        <div class="mnf-input"><i class="fa fa-code"></i><input class="form-control mnf-mono" id="addEditCode" type="text" maxlength="50" placeholder="ITEM-ADD" style="text-transform: uppercase;" /></div>
                        <div class="mnf-help" id="codeHelp">Checked by the program. Cannot be changed later.</div>
                        <div class="mnf-error">Use letters, numbers, "-" or "_" only.</div>
                    </div>

                    <!-- 4. URL (screen) + urutan -->
                    <div class="mnf-field mn-only-screen" id="fieldUrl">
                        <label class="mnf-label" for="addEditUrl">Screen URL <span class="req">*</span></label>
                        <div class="mnf-input"><i class="fa fa-link"></i><input class="form-control mnf-mono" id="addEditUrl" type="text" maxlength="200" placeholder="/StockReport/Index" /></div>
                        <div class="mnf-help">Must match the menu.access middleware on the route.</div>
                        <div class="mnf-error">Must start with "/" without spaces, e.g. /StockReport/Index.</div>
                    </div>

                    <div class="mnf-field mn-only-menu" id="fieldSort">
                        <label class="mnf-label" for="addEditSort">Sort Order</label>
                        <div class="mnf-input"><i class="fa fa-sort-numeric-asc"></i><input class="form-control" id="addEditSort" type="number" min="0" step="10" /></div>
                        <div class="mnf-help">Smaller numbers appear first</div>
                    </div>
                </div>

                <button type="submit" style="display:none;"></button>
            </form>

            <div class="mnf-footer">
                <label class="sg-switch mnf-status mn-only-menu"><input type="checkbox" id="addEditIsActive" checked />
                    <span>Active<small>Inactive items are hidden from the sidebar</small></span>
                </label>
                <span class="mnf-kbd" style="margin-left:auto;">Enter to save</span>
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_save"><i class="fa fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>
