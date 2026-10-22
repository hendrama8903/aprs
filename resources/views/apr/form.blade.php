{{--
    Form Approval for Price Confirmation (header-detail).
    Mode edit (DRAFT/REJECTED + hak APR-EDIT) atau read-only; tombol approve/reject muncul untuk approver tahap saat ini.
    Harga current, qty & statistik PO dihitung ulang di server setiap Save; angka di layar sebelum Save adalah perkiraan.
--}}
@extends('layouts.admin')

@section('content')
<section class="content apr-form">
    <div class="apr-shell">
        <div class="apr-topbar">
            <a href="{{ $backUrl }}" class="apr-back" title="Back"><i class="fa fa-angle-left"></i></a>
            <div class="apr-docno" id="docNo">New APR</div>
            <span id="statusBadge"></span>
            <span class="apr-sub" id="waitingInfo"></span>
        </div>

        <div class="apr-alert" id="rejectInfo" style="display:none"></div>

        {{-- Background --}}
        <div class="apr-card">
            <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-file-text-o"></i></span> Background</div>
            <div class="apr-card-body">
                <div class="apr-row apr-row-sup">
                    <div class="apr-field">
                        <label>Supplier <span class="text-red">*</span></label>
                        <select class="form-control apr-input" id="supplierId"></select>
                    </div>
                    <div class="apr-field">
                        <label>Letter Address <span class="text-red">*</span></label>
                        <select class="form-control apr-input" id="supplierAddressId"></select>
                    </div>
                    <div class="apr-addr">
                        <i class="fa fa-map-marker"></i>
                        <div class="apr-addr-preview" id="addressPreview"></div>
                    </div>
                </div>
                <div class="apr-row apr-row-4">
                    <div class="apr-field">
                        <label>Document Date <span class="text-red">*</span></label>
                        <input type="date" class="form-control apr-input" id="docDate" />
                    </div>
                    <div class="apr-field">
                        <label>Part Category <span class="text-red">*</span></label>
                        <select class="form-control apr-input apr-combo" id="partCategory" data-max="50"></select>
                    </div>
                    <div class="apr-field">
                        <label>Part Status <span class="text-red">*</span></label>
                        <select class="form-control apr-input apr-combo" id="partStatus" data-max="50"></select>
                    </div>
                    <div class="apr-field">
                        <label>Model <span class="text-red">*</span></label>
                        <select class="form-control apr-input apr-combo" id="model" data-max="50"></select>
                    </div>
                </div>
                <div class="apr-field">
                    <label>Background <span class="text-red">*</span></label>
                    <input type="text" class="form-control apr-input" id="background" maxlength="255" />
                </div>
            </div>
        </div>

        {{-- Period + Exchange Rate --}}
        <div class="apr-pair">
            <div class="apr-card">
                <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-calendar"></i></span> Period</div>
                <div class="apr-card-body">
                    <div class="apr-field">
                        <label>Effective Period <span class="text-red">*</span>
                            <i class="fa fa-info-circle apr-help" title="Period in which the new price applies"></i></label>
                        <div class="apr-range">
                            <input type="date" class="form-control apr-input" id="effectiveFrom" />
                            <span>to</span>
                            <input type="date" class="form-control apr-input" id="effectiveTo" />
                        </div>
                    </div>
                    <div class="apr-field">
                        <label>Purchase Period <span class="text-red">*</span>
                            <i class="fa fa-info-circle apr-help" title="PO transactions in this period are used for qty and PO price statistics"></i></label>
                        <div class="apr-range">
                            <input type="date" class="form-control apr-input" id="purchaseFrom" />
                            <span>to</span>
                            <input type="date" class="form-control apr-input" id="purchaseTo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="apr-card">
                <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-database"></i></span> Exchange Rate (IDR)
                    <i class="fa fa-info-circle apr-help" title="Taken from the Exchange Rate master: current = rate valid on the document date, new = rate valid at the start of the effective period. Required when items are in USD / JPY."></i></div>
                <div class="apr-card-body">
                    <div class="apr-row apr-row-rate">
                        <div class="apr-field">
                            <label>Currency</label>
                            <select class="form-control apr-input" id="rateCurrency">
                                <option value="">IDR (no rate)</option>
                                <option value="USD">USD</option>
                                <option value="JPY">JPY</option>
                            </select>
                        </div>
                        <div class="apr-field">
                            <label>Current Rate</label>
                            <div class="apr-readonly" id="rateValue">-</div>
                        </div>
                        <div class="apr-field">
                            <label>Valid Period</label>
                            <div class="apr-readonly" id="rateValid">-</div>
                        </div>
                    </div>
                    <div class="apr-rate-note" id="rateNote"></div>
                    {{-- Nilai kurs yang disimpan: otomatis dari master sesuai currency terpilih. --}}
                    <input type="hidden" id="rateUsdCurrent" /><input type="hidden" id="rateUsdNew" />
                    <input type="hidden" id="rateJpyCurrent" /><input type="hidden" id="rateJpyNew" />
                </div>
            </div>
        </div>

        {{-- Cost Study --}}
        <div class="apr-card">
            <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-list-alt"></i></span> Cost Study <span class="apr-count" id="itemCount"></span>
                <i class="fa fa-info-circle apr-help" title="Qty comes from PO transactions in the purchase period. Increase above {{ $threshold }}% needs a remark."></i></div>
            <div class="apr-card-body">
                <div class="apr-toolbar">
                    <div class="apr-toolbar-left" id="detailToolbar">
                        <button type="button" class="dp-btn dp-btn-primary" id="btn_addItems"><i class="fa fa-plus"></i> Add Items</button>
                        <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_removeItems" disabled><i class="fa fa-trash-o"></i> Remove Selected</button>
                    </div>
                    <div class="apr-toolbar-right">
                        <div class="apr-search"><i class="fa fa-search"></i><input type="text" class="form-control" id="itemSearch" placeholder="Search item..." /></div>
                        <span class="apr-show">Show</span>
                        <select class="form-control apr-pagesize" id="itemPageSize">
                            <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="0">All</option>
                        </select>
                        <span class="apr-show">items</span>
                    </div>
                </div>
                <div id="detailGrid"></div>
            </div>
        </div>

        {{-- Total + Conclusion --}}
        <div class="apr-pair">
            <div class="apr-card">
                <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-calculator"></i></span> Total Purchase Amount (IDR)</div>
                <div class="apr-card-body">
                    <div class="apr-totals">
                        <div><span>Current</span><b id="totCurrent">0</b></div>
                        <div><span>New</span><b id="totNew">0</b></div>
                        <div><span>Difference</span><b id="totDiff">0</b></div>
                        <div><span>Change</span><b id="totPct">-</b></div>
                    </div>
                </div>
            </div>
            <div class="apr-card">
                <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-check-square-o"></i></span> Conclusion</div>
                <div class="apr-card-body">
                    <div class="apr-choice">
                        <label class="apr-radio ok"><input type="radio" name="conclusion" value="ACCEPTABLE" class="apr-input" checked /> Price is ACCEPTABLE</label>
                        <label class="apr-radio ng"><input type="radio" name="conclusion" value="NOT_ACCEPTABLE" class="apr-input" /> Price is NOT ACCEPTABLE</label>
                    </div>
                    <div class="apr-row apr-row-2">
                        <div class="apr-field">
                            <label>Conclusion Note</label>
                            <textarea class="form-control apr-input" id="conclusionNote" rows="2" maxlength="500" placeholder="Enter conclusion note..."></textarea>
                        </div>
                        <div class="apr-field">
                            <label>Conditions on Supplier Letter <i class="fa fa-info-circle apr-help" title="One condition per line; printed on the supplier letter"></i></label>
                            <textarea class="form-control apr-input" id="letterConditions" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dokumen pendukung --}}
        <div class="apr-card">
            <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-paperclip"></i></span> Supporting Documents <span class="apr-count" id="attachCount"></span></div>
            <div class="apr-card-body">
                <div class="apr-drop" id="attachDrop">
                    <i class="fa fa-cloud-upload"></i>
                    <div>
                        <div><b>Drag &amp; drop files here</b> or <a href="javascript:void(0)" id="attachBrowse">browse</a></div>
                        <small id="attachHint"></small>
                    </div>
                    <input type="file" id="attachInput" multiple style="display: none;" />
                </div>
                <ul class="apr-files" id="attachList"></ul>
            </div>
        </div>

        <div class="apr-card" id="approvalSection" style="display:none">
            <div class="apr-card-head"><span class="apr-card-ico"><i class="fa fa-users"></i></span> Approval</div>
            <div class="apr-card-body">
                <div class="apr-signs" id="signatures"></div>
                <div class="apr-history-title">History</div>
                <ul class="apr-history" id="history"></ul>
            </div>
        </div>

        {{-- Tombol aksi di bawah (menempel di bawah layar). --}}
        <div class="apr-footer">
            <div class="apr-footer-left">
                <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" style="display:none"><i class="fa fa-trash-o"></i> Delete</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_printApr" style="display:none"><i class="fa fa-print"></i> Print APR</button>
                <button type="button" class="dp-btn dp-btn-outline" id="btn_printLetter" style="display:none"><i class="fa fa-envelope-o"></i> Print Letter</button>
            </div>
            <div class="apr-footer-right">
                <a href="{{ $backUrl }}" class="dp-btn dp-btn-outline" id="btn_cancel">Cancel</a>
                <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_reject" style="display:none"><i class="fa fa-times"></i> Reject</button>
                <button type="button" class="dp-btn dp-btn-success" id="btn_approve" style="display:none"><i class="fa fa-check"></i> Approve</button>
                <button type="button" class="dp-btn dp-btn-primary" id="btn_issue" style="display:none"><i class="fa fa-envelope"></i> Issue Letter</button>
                <button type="button" class="dp-btn dp-btn-primary" id="btn_save" style="display:none"><i class="fa fa-save"></i> Save Draft</button>
                <button type="button" class="dp-btn dp-btn-success" id="btn_submit" style="display:none"><i class="fa fa-paper-plane"></i> Submit</button>
            </div>
        </div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalItems" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-cubes"></i></div>
                <div>
                    <div class="dpf-header-title">Add Items</div>
                    <div class="dpf-header-sub" id="itemsSub">Active prices of the supplier, PO data in the purchase period and new price from Set New Price</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body"><div id="candidateGrid"></div></div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_addSelected"><i class="fa fa-plus"></i> Add Selected</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalNote" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-comment-o"></i></div>
                <div>
                    <div class="dpf-header-title" id="noteTitle">Approve</div>
                    <div class="dpf-header-sub" id="noteSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <textarea class="form-control" id="noteText" rows="4" maxlength="500" placeholder="Note"></textarea>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_noteOk"><i class="fa fa-check"></i> OK</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('apr._status')
<style>
    .apr-shell { background: #fff; border: 1px solid #E3E8EE; border-radius: 12px; padding: 0 18px 0; }
    .apr-topbar { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid #EEF0F3; margin-bottom: 14px; flex-wrap: wrap; }
    .apr-back { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border: 1px solid #E3E8EE;
        border-radius: 8px; color: #2450D8; font-size: 20px; }
    .apr-back:hover { background: #F5F8FC; color: #2450D8; }
    .apr-docno { font-size: 20px; font-weight: 700; color: #1D2939; }
    .apr-sub { font-size: 12px; color: #6b7280; }
    .apr-alert { background: #FDECEA; color: #B42318; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }

    .apr-card { border: 1px solid #E8ECF2; border-radius: 10px; margin-bottom: 14px; background: #fff; overflow: hidden; }
    .apr-card-head { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #F5F8FC; border-bottom: 1px solid #E8ECF2;
        font-size: 15px; font-weight: 700; color: #1C3FA6; }
    .apr-card-ico { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px;
        background: #E7EEFC; color: #2450D8; font-size: 14px; }
    .apr-card-body { padding: 12px 14px 4px; }
    .apr-count { font-size: 13px; font-weight: 600; color: #1C3FA6; }
    .apr-help { color: #98A2B3; cursor: help; font-size: 12px; font-weight: 400; }
    .apr-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: stretch; }
    .apr-pair .apr-card { margin-bottom: 14px; }

    .apr-row { display: grid; gap: 0 18px; }
    .apr-row-sup { grid-template-columns: minmax(0, 1fr) minmax(0, .85fr) minmax(0, 1.15fr); align-items: start; }
    .apr-row-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .apr-row-2 { grid-template-columns: 1fr 1fr; }
    .apr-row-rate { grid-template-columns: minmax(0, .7fr) minmax(0, 1fr) minmax(0, 1.25fr); gap: 0 12px; }
    .apr-field { margin-bottom: 12px; }
    .apr-field label { font-size: 12.5px; color: #344054; margin-bottom: 5px; display: block; font-weight: 600; }
    .apr-range { display: flex; align-items: center; gap: 10px; }
    .apr-range input { flex: 1; min-width: 0; }
    .apr-range span { font-size: 12px; color: #6b7280; }
    .apr-readonly { min-height: 34px; display: flex; align-items: center; padding: 6px 10px; white-space: nowrap; overflow: hidden; background: #F5F7FA; border: 1px solid #E3E8EE;
        border-radius: 6px; font-size: 13px; color: #475467; }
    .apr-readonly .warn, .apr-rate-note .warn { color: #B42318; }
    .apr-rate-note { font-size: 11.5px; color: #66788A; margin: -4px 0 10px; }
    .apr-rate-note:empty { display: none; }

    .apr-addr { display: flex; gap: 12px; align-items: flex-start; background: #F5F7FA; border: 1px solid #E8ECF2; border-radius: 8px;
        padding: 9px 14px; min-height: 56px; }
    .apr-addr > i { color: #475467; font-size: 18px; margin-top: 2px; }
    .apr-addr-preview { font-size: 12.5px; color: #475467; white-space: pre-line; line-height: 1.5; }
    .apr-addr-preview .muted { color: #9ca3af; }
    .apr-addr-preview .warn { color: #B42318; }

    .apr-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
    .apr-toolbar-left, .apr-toolbar-right { display: flex; align-items: center; gap: 8px; }
    .apr-search { position: relative; }
    .apr-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #98A2B3; }
    .apr-search input { padding-left: 30px; width: 220px; }
    .apr-show { font-size: 12.5px; color: #475467; }
    .apr-pagesize { width: 74px; }
    .apr-form #detailGrid .dx-header-row td { text-transform: none; letter-spacing: 0; font-size: 12.5px; color: #344054; background: #F5F8FC; }
    .apr-form #detailGrid .dx-datagrid-total-footer { background: #EEF3FB; }
    .apr-form #detailGrid .dx-datagrid-summary-item { font-weight: 700; color: #1D2939; }
    .apr-edit-col { background: #F5F8FF; }
    .apr-cell-warn { color: #9A6200; margin-left: 4px; }
    .apr-cell-need { color: #B42318; }
    .apr-cell-set { color: #2450D8; margin-left: 4px; font-size: 11px; }
    .apr-muted { color: #98A2B3; font-style: italic; }

    .apr-totals { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .apr-totals div { background: #F5F7FA; border: 1px solid #EEF0F3; border-radius: 8px; padding: 10px 14px; }
    .apr-totals span { display: block; font-size: 12px; color: #475467; margin-bottom: 4px; }
    .apr-totals b, .apr-totals b span { font-size: 17px; color: #1D2939; white-space: nowrap; }
    .apr-totals b .apr-up { color: #B42318; }
    .apr-totals b .apr-down { color: #17824F; }

    .apr-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 10px; }
    .apr-radio { font-weight: 500 !important; display: flex !important; align-items: center; gap: 8px; margin: 0 !important; font-size: 13px;
        border: 1px solid #E3E8EE; border-radius: 8px; padding: 8px 12px; cursor: pointer; color: #344054; }
    .apr-radio input { margin: 0; }
    .apr-radio.ok:has(input:checked) { border-color: #9AD1B0; background: #EEF8F1; color: #17824F; }
    .apr-radio.ng:has(input:checked) { border-color: #F1A9A0; background: #FEF3F2; color: #B42318; }

    .apr-drop { display: flex; align-items: center; gap: 14px; border: 1.5px dashed #C9D3E3; border-radius: 10px; background: #FAFBFD;
        padding: 14px 18px; margin-bottom: 10px; transition: border-color .15s, background .15s; }
    .apr-drop > i { font-size: 28px; color: #2450D8; }
    .apr-drop b { color: #1D2939; font-size: 13px; }
    .apr-drop a { font-weight: 600; }
    .apr-drop small { display: block; color: #66788A; font-size: 11.5px; margin-top: 2px; }
    .apr-drop.over { border-color: #2450D8; background: #EEF3FB; }
    .apr-files { list-style: none; margin: 0 0 10px; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 8px; }
    .apr-files:empty { display: none; }
    .apr-file { display: flex; align-items: center; gap: 10px; border: 1px solid #E8ECF2; border-radius: 8px; padding: 8px 10px; background: #fff; min-width: 0; }
    .apr-file-ico { flex: none; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px;
        background: #EEF3FB; color: #2450D8; }
    .apr-file-ico.pdf { background: #FDECEC; color: #C62828; }
    .apr-file-ico.xls { background: #E4F7EC; color: #1E9E63; }
    .apr-file-ico.img { background: #F1EAFB; color: #6C3FB5; }
    .apr-file-main { flex: 1; min-width: 0; }
    .apr-file-name { display: block; font-size: 12.5px; font-weight: 600; color: #1D2939; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    a.apr-file-name:hover { color: #2450D8; }
    .apr-file-meta { font-size: 11px; color: #66788A; }
    .apr-file-meta .pending { color: #9A6200; }
    .apr-file-del { flex: none; border: 0; background: transparent; color: #98A2B3; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; }
    .apr-file-del:hover { background: #FDECEC; color: #C62828; }
    .apr-footer { position: sticky; bottom: 0; z-index: 20; display: flex; justify-content: space-between; align-items: center; gap: 8px;
        flex-wrap: wrap; background: #fff; border-top: 1px solid #EEF0F3; padding: 12px 0 14px; margin-top: 4px; }
    .apr-footer-left, .apr-footer-right { display: flex; gap: 8px; flex-wrap: wrap; }

    .apr-signs { display: flex; gap: 8px; flex-wrap: wrap; }
    .apr-sign { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 10px; min-width: 150px; flex: 1; }
    .apr-sign .grp { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
    .apr-sign .step { font-weight: 700; font-size: 12px; }
    .apr-sign .name { font-size: 12px; margin-top: 4px; }
    .apr-sign .st { font-size: 11px; margin-top: 4px; }
    .apr-sign.SIGNED { border-color: #17824F; background: #F3FAF5; }
    .apr-sign.WAITING { border-color: #E3A008; background: #FFFBEB; }
    .apr-sign.REJECTED { border-color: #B42318; background: #FEF3F2; }
    .apr-history-title { font-weight: 600; font-size: 12px; margin: 14px 0 6px; color: #4b5563; }
    .apr-history { list-style: none; padding: 0; margin: 0 0 10px; font-size: 12px; }
    .apr-history li { padding: 5px 0; border-bottom: 1px dashed #eee; }
    .apr-history .act { font-weight: 700; }
    .apr-history .act.REJECT { color: #B42318; }
    .apr-history .act.APPROVE { color: #17824F; }

    .apr-form #detailGrid td.apr-act { padding: 4px 0 !important; overflow: visible; }
    .apr-kebab { border: 0; background: transparent; width: 26px; height: 26px; border-radius: 6px; color: #475467; cursor: pointer; }
    .apr-kebab:hover, .apr-kebab.open { background: #E7F1FB; color: #2450D8; }
    .apr-menu { position: absolute; z-index: 1600; min-width: 170px; background: #fff; border: 1px solid #E3E8EE; border-radius: 8px;
        box-shadow: 0 6px 20px rgba(16, 24, 40, .12); padding: 4px; display: none; }
    .apr-menu button { display: flex; align-items: center; gap: 8px; width: 100%; border: 0; background: transparent; text-align: left;
        font-size: 13px; color: #344054; padding: 7px 10px; border-radius: 6px; cursor: pointer; }
    .apr-menu button i { width: 14px; text-align: center; color: #66788A; }
    .apr-menu button:hover { background: #F3F5F8; }
    .apr-menu button.danger, .apr-menu button.danger i { color: #C62828; }

    @media (max-width: 1100px) {
        .apr-row-sup, .apr-row-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .apr-pair { grid-template-columns: 1fr; }
    }
    @media (max-width: 700px) {
        .apr-row-sup, .apr-row-4, .apr-row-2, .apr-row-rate, .apr-choice { grid-template-columns: 1fr; }
        .apr-totals { grid-template-columns: repeat(2, 1fr); }
        .apr-search input { width: 150px; }
    }
</style>
<script type="text/javascript">
    (function () {
        var aprId = {{ (int) $aprId }};
        var defaults = @json($defaults);
        var headerOptions = @json($headerOptions);
        var attachCfg = @json($attachment);
        var threshold = {{ (float) $threshold }};
        var urls = {
            getByKey: "{{ url('/Apr/GetByKey') }}",
            candidates: "{{ url('/Apr/CandidateItems') }}",
            exchangeRates: "{{ url('/Apr/ExchangeRates') }}",
            save: "{{ url('/Apr/Save') }}",
            submit: "{{ url('/Apr/Submit') }}",
            remove: "{{ url('/Apr/Delete') }}",
            issue: "{{ url('/Apr/Issue') }}",
            approve: "{{ url('/Approval/Approve') }}",
            reject: "{{ url('/Approval/Reject') }}",
            form: "{{ url('/Apr/Form') }}",
            list: "{{ $backUrl }}",
            printApr: "{{ url('/Apr/PrintApr') }}",
            printLetter: "{{ url('/Apr/PrintLetter') }}",
            uploadAttachment: "{{ url('/Apr/UploadAttachment') }}",
            deleteAttachment: "{{ url('/Apr/DeleteAttachment') }}",
            downloadAttachment: "{{ url('/Apr/DownloadAttachment') }}",
            supplierLookup: "{{ url('/Lookup/Supplier') }}",
            supplierAddress: "{{ url('/Lookup/SupplierAddress') }}"
        };

        var lines = [];       // baris detail (format sama dengan Details dari server)
        var editable = true;  // false setelah submit / tanpa hak edit
        var detailGrid, candidateGrid, noteAction;
        var addresses = [];   // alamat aktif supplier terpilih (dari master)
        var filling = false;  // true saat fill() mengisi supplier, supaya alamat tidak di-reset ke default

        // ---------- Util ----------
        function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
        function fmt(v, dec) {
            if (v === null || v === undefined || v === "") return "";
            return Number(v).toLocaleString("en-US", { minimumFractionDigits: 0, maximumFractionDigits: dec === undefined ? 2 : dec });
        }
        function rate(currency, isNew) {
            if (currency === "USD") return num($(isNew ? "#rateUsdNew" : "#rateUsdCurrent").val());
            if (currency === "JPY") return num($(isNew ? "#rateJpyNew" : "#rateJpyCurrent").val());
            return 1;
        }
        // ---------- Kurs (otomatis dari master Exchange Rate) ----------
        var rateMaster = null; // { Current: {USD, JPY}, New: {USD, JPY}, Options: {USD: [...], JPY: [...]} }

        // Isi nilai kurs tersembunyi dari master untuk currency terpilih (currency lain dikosongkan), lalu tampilkan.
        function rateOption(cur, rate) {
            return $.grep((rateMaster && rateMaster.Options[cur]) || [], function (x) { return Math.abs(x.Rate - rate) < 0.00001; })[0] || null;
        }

        function applyRates(overwrite) {
            var cur = $("#rateCurrency").val();
            if (overwrite && editable && rateMaster) {
                $.each([["Usd", "USD"], ["Jpy", "JPY"]], function (_, c) {
                    var on = c[1] === cur;
                    $("#rate" + c[0] + "Current").val(on ? (rateMaster.Current || {})[c[1]] || "" : "");
                    $("#rate" + c[0] + "New").val(on ? (rateMaster.New || {})[c[1]] || "" : "");
                });
            }
            var $v = $("#rateValue").empty(), $valid = $("#rateValid").empty(), $note = $("#rateNote").empty();
            if (!cur) {
                $v.text("Not needed (IDR)");
                $valid.text("-");
            } else {
                var key = cur === "USD" ? "Usd" : "Jpy";
                var rateCur = num($("#rate" + key + "Current").val()), rateNew = num($("#rate" + key + "New").val());
                if (rateCur > 0) {
                    $v.text("1 " + cur + " = " + fmt(rateCur, 4) + " IDR");
                    var o = rateOption(cur, rateCur);
                    $valid.text(o ? moment(o.ValidFrom).format("DD/MM/YYYY") + " - " + moment(o.ValidTo).format("DD/MM/YYYY") : "-");
                } else {
                    $v.append($("<span class='warn'>").text("No rate on " + moment($("#docDate").val()).format("DD/MM/YYYY")));
                    $valid.text("-");
                }
                if (rateNew > 0) {
                    $note.text("New rate for the effective period: 1 " + cur + " = " + fmt(rateNew, 4) + " IDR");
                } else {
                    $note.append($("<span class='warn'>").html("<i class='fa fa-exclamation-circle'></i> No " + cur + " rate in the Exchange Rate master on "
                        + moment($("#effectiveFrom").val()).format("DD/MM/YYYY") + " (start of effective period)"));
                }
            }
            refreshGrid();
        }

        // Ambil kurs dari master. overwrite: isi ulang kurs dokumen (form baru / tanggal / currency berubah).
        function loadRates(overwrite) {
            HMA.post(urls.exchangeRates, { docDate: $("#docDate").val(), effectiveFrom: $("#effectiveFrom").val() }, function (result) {
                rateMaster = $.extend({ Current: {}, New: {}, Options: { USD: [], JPY: [] } }, result.data);
                applyRates(overwrite);
            }, { block: false, successMessage: false });
        }

        // Currency dari item yang dipilih (mata uang asing pertama), kalau currency belum dipilih.
        function syncCurrencyFromItems() {
            if ($("#rateCurrency").val()) return;
            var foreign = $.grep(lines, function (l) { return l.Currency === "USD" || l.Currency === "JPY"; })[0];
            if (foreign) { $("#rateCurrency").val(foreign.Currency); applyRates(true); }
        }

        function firstOfMonth(m) { return m.clone().startOf("month").format("YYYY-MM-DD"); }

        // Hitung ulang kolom turunan satu baris (sama dengan AprRepo::mapDetail).
        function calcLine(l) {
            l.NewPrice = num(l.NewPrice);
            l.Movement = l.NewPrice - l.CurrentPrice;
            l.MovementPct = l.CurrentPrice > 0 ? Math.round(l.Movement / l.CurrentPrice * 10000) / 100 : null;
            l.CurrentAmount = l.Qty * l.CurrentPrice * rate(l.Currency, false);
            l.NewAmount = l.Qty * l.NewPrice * rate(l.Currency, true);
            l.NeedRemark = l.MovementPct !== null && l.MovementPct > threshold;
            l.OverPoMax = l.PoMaxPrice !== null && l.PoMaxPrice !== undefined && l.NewPrice > l.PoMaxPrice;
            return l;
        }

        function refreshTotals() {
            var cur = 0, nw = 0;
            $.each(lines, function (_, l) { calcLine(l); cur += l.CurrentAmount; nw += l.NewAmount; });
            var diff = nw - cur;
            $("#totCurrent").text(fmt(cur, 0));
            $("#totNew").text(fmt(nw, 0));
            $("#totDiff").text((diff > 0 ? "+" : "") + fmt(diff, 0));
            $("#totPct").empty().append(AprStatus.pct(cur > 0 ? Math.round(diff / cur * 10000) / 100 : null));
            $("#itemCount").text(lines.length ? "(" + lines.length + " item" + (lines.length > 1 ? "s" : "") + ")" : "");

        }

        function refreshGrid() {
            refreshTotals();
            detailGrid.option("dataSource", lines);
        }

        // Current price + tanda kalau Time Control-nya tidak mencakup tanggal dokumen (lihat AprRepo::currentPrices).
        function currentPriceCell(c, o) {
            c.text(fmt(o.value));
            var st = o.data.PriceStatus, to = o.data.PriceTcTo, from = o.data.PriceTcFrom;
            if (st === "EXPIRED" || st === "NOT_YET_VALID") {
                var tip = st === "EXPIRED"
                    ? "Price time control expired on " + moment(to).format("DD-MM-YYYY")
                    : "Price time control starts on " + moment(from).format("DD-MM-YYYY");
                c.append($("<i class='fa fa-clock-o apr-cell-warn'>").attr("title", tip + " (document date " + moment($("#docDate").val()).format("DD-MM-YYYY") + ")"));
            }
        }

        // ---------- Grid detail ----------
        // Menu aksi baris (⋮) grid Cost Study.
        var $rowMenu = $("<div class='apr-menu' role='menu'>").appendTo(document.body);
        function closeRowMenu() { $rowMenu.hide(); $(".apr-kebab.open").removeClass("open"); }
        function openRowMenu(btn, line) {
            var wasOpen = $(btn).hasClass("open");
            closeRowMenu();
            if (wasOpen) return;
            var item = function (icon, text, fn, cls) {
                return $("<button type='button'>").addClass(cls || "").append($("<i class='fa'>").addClass(icon), document.createTextNode(text))
                    .on("click", function () { closeRowMenu(); fn(); });
            };
            $rowMenu.empty().append(
                item("fa-pencil", "Edit new price", function () { detailGrid.editCell(detailGrid.getRowIndexByKey(line.ItemCode), "NewPrice"); }),
                item("fa-undo", "Reset to current price", function () { line.NewPrice = line.CurrentPrice; line.NewPriceSource = "CURRENT"; refreshGrid(); }),
                item("fa-trash-o", "Remove item", function () { removeItems([line.ItemCode]); }, "danger"));
            $(btn).addClass("open");
            var r = btn.getBoundingClientRect(), top = r.bottom + window.scrollY + 4;
            if (r.bottom + $rowMenu.outerHeight() + 8 > window.innerHeight) top = r.top + window.scrollY - $rowMenu.outerHeight() - 4;
            $rowMenu.css({ top: top, left: r.right + window.scrollX - $rowMenu.outerWidth() }).show();
        }
        $(document).on("click", function (e) { if (!$(e.target).closest(".apr-menu, .apr-kebab").length) closeRowMenu(); })
            .on("keydown", function (e) { if (e.key === "Escape") closeRowMenu(); });
        $(window).on("resize", closeRowMenu);

        function removeItems(keys) {
            lines = $.grep(lines, function (l) { return keys.indexOf(l.ItemCode) < 0; });
            detailGrid.clearSelection();
            $("#supplierId").prop("disabled", lines.length > 0);
            refreshGrid();
        }

        function buildDetailGrid() {
            detailGrid = $("#detailGrid").addClass("sg-grid").dxDataGrid({
                dataSource: lines,
                keyExpr: "ItemCode",
                showBorders: true,
                showRowLines: true,
                showColumnLines: true,
                columnAutoWidth: false,
                wordWrapEnabled: true,
                allowColumnResizing: true,
                noDataText: "No items yet. Choose the supplier and purchase period, then click Add Items.",
                selection: { mode: "multiple", showCheckBoxesMode: "always" },
                editing: { mode: "cell", allowUpdating: true },
                paging: { enabled: true, pageSize: 10 },
                pager: { visible: "auto", showInfo: true, showNavigationButtons: true, displayMode: "compact" },
                searchPanel: { visible: false },
                headerFilter: { visible: false },
                filterRow: { visible: false },
                scrolling: { mode: "standard" },
                onSelectionChanged: function (e) { $("#btn_removeItems").prop("disabled", e.selectedRowKeys.length === 0); },
                onRowUpdating: function (e) { if (e.newData.NewPrice !== undefined) e.newData.NewPriceSource = "MANUAL"; },
                onRowUpdated: function () { refreshGrid(); },
                summary: {
                    totalItems: [
                        { column: "ItemCode", showInColumn: "ItemName", summaryType: "count", customizeText: function (e) { return "Total (" + e.value + " item" + (e.value === 1 ? "" : "s") + ")"; } },
                        { column: "Qty", summaryType: "sum", valueFormat: "#,##0.##", displayFormat: "{0}" },
                        { column: "CurrentPrice", summaryType: "sum", valueFormat: "#,##0.##", displayFormat: "{0}" },
                        { column: "NewPrice", summaryType: "sum", valueFormat: "#,##0.##", displayFormat: "{0}" },
                        { name: "totalPct", showInColumn: "MovementPct", summaryType: "custom", alignment: "right" },
                        { column: "NewAmount", summaryType: "sum", valueFormat: "#,##0", displayFormat: "{0}" }
                    ],
                    // % total = perubahan total amount (IDR); kalau qty kosong, dari jumlah harga.
                    calculateCustomSummary: function (o) {
                        if (o.name !== "totalPct") return;
                        if (o.summaryProcess === "start") o.totalValue = { cur: 0, nw: 0, cp: 0, np: 0 };
                        else if (o.summaryProcess === "calculate") {
                            o.totalValue.cur += o.value.CurrentAmount || 0; o.totalValue.nw += o.value.NewAmount || 0;
                            o.totalValue.cp += o.value.CurrentPrice || 0; o.totalValue.np += o.value.NewPrice || 0;
                        } else if (o.summaryProcess === "finalize") {
                            var t = o.totalValue, base = t.cur > 0 ? t.cur : t.cp, diff = t.cur > 0 ? t.nw - t.cur : t.np - t.cp;
                            o.totalValue = base > 0 ? (diff / base * 100).toFixed(2) + "%" : "-";
                        }
                    }
                },
                columns: [
                    { type: "selection", width: 36 },
                    { caption: "No", width: 40, alignment: "center", allowEditing: false, allowSorting: false,
                        cellTemplate: function (c, o) { var g = o.component; c.text(g.pageIndex() * g.pageSize() + o.rowIndex + 1); } },
                    { dataField: "ItemCode", caption: "Item Code", allowEditing: false, width: 82 },
                    { dataField: "ItemName", caption: "Item Name", allowEditing: false, minWidth: 150 },
                    { dataField: "Unit", caption: "Unit", allowEditing: false, alignment: "center", width: 50 },
                    { dataField: "Currency", caption: "Curr", allowEditing: false, alignment: "center", width: 48 },
                    { dataField: "Qty", caption: "Qty (Period)", dataType: "number", format: "#,##0.##", allowEditing: false, width: 84 },
                    { dataField: "CurrentPrice", caption: "Current Price", dataType: "number", format: "#,##0.##", allowEditing: false, cellTemplate: currentPriceCell, width: 90 },
                    {
                        dataField: "NewPrice", caption: "New Price", dataType: "number", format: "#,##0.##", width: 90,
                        editorOptions: { min: 0 },
                        cssClass: "apr-edit-col",
                        cellTemplate: function (c, o) {
                            c.append($("<b>").text(fmt(o.value)));
                            if (o.data.NewPriceSource === "SET") {
                                c.append($("<i class='fa fa-tag apr-cell-set'>").attr("title", "From Set New Price (" + moment(o.data.SetNewFrom).format("DD-MM-YYYY") + " - " + moment(o.data.SetNewTo).format("DD-MM-YYYY") + ")"));
                            } else if (o.data.NewPriceSource === "CURRENT") {
                                c.append($("<i class='fa fa-exclamation-circle apr-cell-warn'>").attr("title", "No Set New Price for the effective period; current price is used. Fill it in Set New Price or edit here."));
                            }
                            if (o.data.OverPoMax) {
                                c.append($("<i class='fa fa-exclamation-triangle apr-cell-warn'>").attr("title", "Above highest PO price in period (" + fmt(o.data.PoMaxPrice) + ")"));
                            }
                        }
                    },
                    {
                        dataField: "MovementPct", caption: "%", dataType: "number", allowEditing: false, width: 66, alignment: "right",
                        cellTemplate: function (c, o) { c.append(AprStatus.pct(o.value)); }
                    },
                    { dataField: "NewAmount", caption: "New Amt (IDR)", dataType: "number", format: "#,##0", allowEditing: false, width: 104 },
                    {
                        dataField: "Remark", caption: "Remark", width: 104,
                        editorOptions: { maxLength: 255 },
                        cellTemplate: function (c, o) {
                            if (o.data.NeedRemark && !$.trim(o.value || "")) {
                                c.append($("<span class='apr-cell-need'><i class='fa fa-pencil'></i> required</span>"));
                            } else {
                                c.text(o.value || "");
                            }
                        }
                    },
                    {
                        name: "actions", caption: "", width: 46, alignment: "center", allowEditing: false, allowSorting: false, cssClass: "apr-act",
                        cellTemplate: function (c, o) {
                            $("<button type='button' class='apr-kebab' title='Actions'><i class='fa fa-ellipsis-v'></i></button>")
                                .on("click", function (ev) { ev.stopPropagation(); openRowMenu(this, o.data); })
                                .appendTo(c);
                        }
                    }
                ]
            }).dxDataGrid("instance");
        }

        function buildCandidateGrid() {
            candidateGrid = $("#candidateGrid").addClass("sg-grid").dxDataGrid($.extend(HMA.filterDefaults(), {
                dataSource: [],
                keyExpr: "ItemCode",
                showBorders: true,
                showRowLines: true,
                showColumnLines: true,
                columnAutoWidth: true,
                height: 460,
                selection: { mode: "multiple", showCheckBoxesMode: "always", allowSelectAll: true },
                paging: { pageSize: 50 },
                columns: [
                    { dataField: "ItemCode", caption: "Item Code" },
                    { dataField: "ItemName", caption: "Item Name" },
                    { dataField: "Unit", caption: "Unit", alignment: "center" },
                    { dataField: "Currency", caption: "Curr", alignment: "center" },
                    { dataField: "CurrentPrice", caption: "Current Price", dataType: "number", format: "#,##0.##", cellTemplate: currentPriceCell },
                    {
                        dataField: "SetNewPrice", caption: "Set New Price", dataType: "number", format: "#,##0.##",
                        cellTemplate: function (c, o) {
                            if (o.value === null || o.value === undefined) {
                                c.append($("<span class='apr-muted'>").text("not set").attr("title", "No new price in Set New Price for the effective period; current price is used"));
                            } else {
                                c.append($("<b>").text(fmt(o.value)), " ", AprStatus.pct(o.data.CurrentPrice > 0 ? Math.round((o.value - o.data.CurrentPrice) / o.data.CurrentPrice * 10000) / 100 : null));
                            }
                        }
                    },
                    { dataField: "Quotation", caption: "Quotation" },
                    { dataField: "PriceTcTo", caption: "Valid Until", dataType: "date", format: "dd-MM-yyyy", alignment: "right" },
                    { dataField: "Qty", caption: "Qty (period)", dataType: "number", format: "#,##0.##" },
                    { dataField: "PoCount", caption: "PO", dataType: "number" },
                    { dataField: "PoAvgPrice", caption: "PO Avg", dataType: "number", format: "#,##0.##" },
                    { dataField: "PoMaxPrice", caption: "PO Max", dataType: "number", format: "#,##0.##" }
                ]
            })).dxDataGrid("instance");
        }

        // ---------- Letter address ----------
        // Preview alamat yang akan tercetak: dari master (draft) atau snapshot dokumen (sudah submit / alamat sudah tidak ada).
        function renderAddressPreview(a, note) {
            var $p = $("#addressPreview").empty();
            if (!a) {
                $p.append($("<span class='muted'>").text(note || "No address selected."));
                return;
            }
            var attn = a.AttentionName ? "\nAttn. " + a.AttentionName + (a.AttentionTitle ? " - " + a.AttentionTitle : "") : "";
            $p.text(a.Address + attn);
            if (note) $p.append($("<div class='warn'>").text(note));
        }

        function snapshotOf(h) {
            return h && h.LetterAddress !== null ? {
                AddressId: h.SupplierAddressId, Label: h.LetterAddressLabel, Address: h.LetterAddress,
                AttentionName: h.LetterAttentionName, AttentionTitle: h.LetterAttentionTitle
            } : null;
        }

        function selectedAddress() {
            var id = parseInt($("#supplierAddressId").val(), 10);
            return $.grep(addresses, function (a) { return a.AddressId === id; })[0] || null;
        }

        // Dokumen yang tidak bisa diedit: tampilkan snapshot apa adanya.
        function showSnapshot(h) {
            var snap = snapshotOf(h);
            addresses = [];
            $("#supplierAddressId").empty().append(new Option(snap ? snap.Label : "-", "", true, true)).trigger("change.select2");
            renderAddressPreview(snap, snap ? null : "No letter address on this document.");
        }

        // selectedId: alamat tersimpan di dokumen, kosong = pilih default.
        // snap: snapshot dokumen, untuk pesan kalau alamat tersimpan sudah tidak aktif / dihapus.
        function loadAddresses(supplierId, selectedId, snap) {
            var $sel = $("#supplierAddressId").empty().trigger("change.select2");
            addresses = [];
            if (!supplierId) { renderAddressPreview(null, "Choose the supplier first."); return; }

            $.get(urls.supplierAddress, { supplierId: supplierId }).done(function (result) {
                addresses = result.status ? result.data : [];
                $sel.append(new Option("Choose address", ""));
                $.each(addresses, function (_, a) {
                    $sel.append(new Option(a.Label + (a.IsDefault ? " (default)" : ""), a.AddressId));
                });

                var pick = null;
                if (selectedId) {
                    pick = $.grep(addresses, function (a) { return a.AddressId === selectedId; })[0] || null;
                } else if (!snap) {
                    pick = addresses[0] || null; // default selalu di urutan pertama
                }
                $sel.val(pick ? pick.AddressId : "").trigger("change.select2");

                if (!addresses.length) {
                    renderAddressPreview(null, "This supplier has no address yet. Add one in the Supplier menu.");
                } else if (!pick && snap) {
                    renderAddressPreview(snap, "Address \"" + snap.Label + "\" is no longer available. Please choose another one.");
                } else {
                    renderAddressPreview(pick);
                }
            });
        }

        // ---------- Lampiran (dokumen pendukung) ----------
        var attachments = [];   // lampiran tersimpan (dari server)
        var pendingFiles = [];  // file dipilih di dokumen baru, di-upload setelah Save pertama

        function fileSize(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + " MB" : Math.max(1, Math.round(b / 1024)) + " KB"; }
        function fileIcon(name) {
            var ext = (name.split(".").pop() || "").toLowerCase();
            if (ext === "pdf") return ["pdf", "fa-file-pdf-o"];
            if (["xls", "xlsx", "csv"].indexOf(ext) >= 0) return ["xls", "fa-file-excel-o"];
            if (["jpg", "jpeg", "png"].indexOf(ext) >= 0) return ["img", "fa-file-image-o"];
            if (["doc", "docx"].indexOf(ext) >= 0) return ["", "fa-file-word-o"];
            if (["ppt", "pptx"].indexOf(ext) >= 0) return ["", "fa-file-powerpoint-o"];
            if (ext === "zip") return ["", "fa-file-archive-o"];
            return ["", "fa-file-o"];
        }

        function renderAttachments() {
            var $l = $("#attachList").empty();
            var total = attachments.length + pendingFiles.length;
            $("#attachCount").text(total ? "(" + total + " file" + (total > 1 ? "s" : "") + ")" : "");
            var row = function (name, size, meta, href, onDelete) {
                var ic = fileIcon(name);
                var $name = href ? $("<a class='apr-file-name' target='_blank'>").attr("href", href) : $("<span class='apr-file-name'>");
                var $li = $("<li class='apr-file'>").attr("title", name).append(
                    $("<span class='apr-file-ico'>").addClass(ic[0]).append($("<i class='fa'>").addClass(ic[1])),
                    $("<div class='apr-file-main'>").append($name.text(name), $("<div class='apr-file-meta'>").append(fileSize(size) + " · ", meta)));
                if (editable && onDelete) {
                    $li.append($("<button type='button' class='apr-file-del' title='Delete'><i class='fa fa-trash-o'></i></button>").on("click", onDelete));
                }
                $l.append($li);
            };
            $.each(attachments, function (_, a) {
                row(a.FileName, a.FileSize, "by " + a.CreatedBy + " · " + moment(a.CreatedDate).format("DD-MM-YYYY HH:mm"),
                    urls.downloadAttachment + "?id=" + a.AttachmentId, function () {
                        showConfirmMesg("Delete attachment " + a.FileName + "?", function () {
                            HMA.post(urls.deleteAttachment, { attachmentId: a.AttachmentId }, refreshAttachments);
                        }, "Confirmation");
                    });
            });
            $.each(pendingFiles, function (i, f) {
                row(f.name, f.size, $("<span class='pending'>").text("Pending · uploaded when you save"), null, function () {
                    pendingFiles.splice(i, 1);
                    renderAttachments();
                });
            });
            if (!editable && !total) $l.append($("<li class='apr-file-meta'>").text("No supporting documents."));
        }

        function refreshAttachments() {
            HMA.post(urls.getByKey, { aprId: aprId }, function (result) {
                attachments = result.data.Attachments || [];
                renderAttachments();
            }, { block: false, successMessage: false });
        }

        // Upload berurutan (satu file per request supaya tidak melewati batas post PHP). Selesai -> done(jumlah gagal).
        function uploadFiles(id, files, done) {
            var failed = 0, i = 0;
            var next = function () {
                if (i >= files.length) { popUpProgressHide(); done(failed); return; }
                var form = new FormData();
                form.append("aprId", id);
                form.append("file", files[i++]);
                $.ajax({ type: "POST", url: urls.uploadAttachment, data: form, processData: false, contentType: false, dataType: "json" })
                    .done(function (r) { if (r.status !== true) { failed++; showErrorMesgGrowl(HMA.formatMessage(r.message)); } })
                    .fail(function () { failed++; showErrorMesgGrowl("Failed to upload " + files[i - 1].name + "."); })
                    .always(next);
            };
            popUpProgressShow();
            next();
        }

        // File dari browse / drag & drop: cek tipe, ukuran & jumlah di browser dulu.
        function addFiles(list) {
            if (!editable) return;
            var ok = [];
            $.each(list, function (_, f) {
                var ext = (f.name.split(".").pop() || "").toLowerCase();
                if (attachCfg.extensions.indexOf(ext) < 0) { showErrorMesgGrowl(f.name + ": file type not allowed."); return; }
                if (f.size > attachCfg.maxBytes) { showErrorMesgGrowl(f.name + " is larger than " + fileSize(attachCfg.maxBytes) + "."); return; }
                ok.push(f);
            });
            if (attachments.length + pendingFiles.length + ok.length > attachCfg.maxFiles) {
                showErrorMesgGrowl("At most " + attachCfg.maxFiles + " attachments per document.");
                return;
            }
            if (!ok.length) return;
            if (aprId === 0) {
                pendingFiles = pendingFiles.concat(ok);
                renderAttachments();
            } else {
                uploadFiles(aprId, ok, function (failed) {
                    if (ok.length - failed > 0) showSuccessMesgGrowl((ok.length - failed) + " file(s) uploaded.");
                    refreshAttachments();
                });
            }
        }

        // ---------- Dropdown Part Category / Part Status / Model (pilih atau ketik baru) ----------
        function initCombo(id, options) {
            var $sel = $(id);
            $.each(options || [], function (_, v) { $sel.append(new Option(v, v)); });
            $sel.select2({
                width: "100%", tags: true, minimumResultsForSearch: 0,
                createTag: function (p) {
                    var t = $.trim(p.term).substring(0, $sel.data("max") || 50);
                    return t ? { id: t, text: t, newTag: true } : null;
                }
            });
        }
        function setCombo(id, value) {
            var $sel = $(id);
            if (value && !$sel.find("option").filter(function () { return this.value === value; }).length) $sel.append(new Option(value, value));
            $sel.val(value || null).trigger("change.select2");
        }

        // ---------- Form ----------
        function header() {
            return {
                AprId: aprId,
                SupplierId: $("#supplierId").val() || 0,
                SupplierAddressId: $("#supplierAddressId").val() || 0,
                DocDate: $("#docDate").val(),
                PartCategory: $("#partCategory").val(),
                PartStatus: $("#partStatus").val(),
                Model: $("#model").val(),
                Background: $("#background").val(),
                EffectiveFrom: $("#effectiveFrom").val(),
                EffectiveTo: $("#effectiveTo").val(),
                PurchaseFrom: $("#purchaseFrom").val(),
                PurchaseTo: $("#purchaseTo").val(),
                RateUsdCurrent: $("#rateUsdCurrent").val(),
                RateUsdNew: $("#rateUsdNew").val(),
                RateJpyCurrent: $("#rateJpyCurrent").val(),
                RateJpyNew: $("#rateJpyNew").val(),
                Conclusion: $("input[name=conclusion]:checked").val(),
                ConclusionNote: $("#conclusionNote").val(),
                LetterConditions: $("#letterConditions").val()
            };
        }

        function fillDefaults() {
            var today = moment();
            $("#docDate").val(today.format("YYYY-MM-DD"));
            setCombo("#partCategory", defaults.part_category);
            setCombo("#partStatus", defaults.part_status);
            setCombo("#model", defaults.model);
            $("#background").val(defaults.background);
            // Berlaku mulai bulan depan selama 6 bulan; data PO 6 bulan penuh terakhir.
            var effFrom = today.clone().add(1, "month").startOf("month");
            $("#effectiveFrom").val(effFrom.format("YYYY-MM-DD"));
            $("#effectiveTo").val(effFrom.clone().add(5, "month").endOf("month").format("YYYY-MM-DD"));
            $("#purchaseFrom").val(firstOfMonth(today.clone().subtract(6, "month")));
            $("#purchaseTo").val(today.clone().subtract(1, "month").endOf("month").format("YYYY-MM-DD"));
            $("#letterConditions").val(defaults.letter_conditions);
            $("#docNo").text("New APR");
            $("#statusBadge").empty().append(AprStatus.badge("DRAFT"));
        }

        function fill(data) {
            var h = data.Header;
            filling = true;
            HMA.setSelect2Value("#supplierId", h.SupplierId, h.SupplierCode + " - " + h.SupplierName);
            filling = false;
            if (data.Can.Edit) loadAddresses(h.SupplierId, h.SupplierAddressId, snapshotOf(h)); else showSnapshot(h);
            $("#docDate").val(h.DocDate);
            setCombo("#partCategory", h.PartCategory);
            setCombo("#partStatus", h.PartStatus);
            setCombo("#model", h.Model);
            $("#background").val(h.Background);
            $("#effectiveFrom").val(h.EffectiveFrom);
            $("#effectiveTo").val(h.EffectiveTo);
            $("#purchaseFrom").val(h.PurchaseFrom);
            $("#purchaseTo").val(h.PurchaseTo);
            $("#rateUsdCurrent").val(h.RateUsdCurrent || "");
            $("#rateUsdNew").val(h.RateUsdNew || "");
            $("#rateJpyCurrent").val(h.RateJpyCurrent || "");
            $("#rateJpyNew").val(h.RateJpyNew || "");
            $("#rateCurrency").val(h.RateUsdNew > 0 ? "USD" : (h.RateJpyNew > 0 ? "JPY" : ""));
            $("input[name=conclusion][value=" + h.Conclusion + "]").prop("checked", true);
            $("#conclusionNote").val(h.ConclusionNote);
            $("#letterConditions").val(h.LetterConditions);

            $("#docNo").text(h.DocNo + (h.ExtDocNo ? "  |  Letter " + h.ExtDocNo : ""));
            $("#statusBadge").empty().append(AprStatus.badge(h.Status));
            $("#waitingInfo").text(data.CurrentStep ? "Waiting for " + data.CurrentStep.StepName + " (" + (data.CurrentStep.ApproverName || "-") + ")" : "");

            var lastReject = null;
            $.each(data.Approvals, function (_, a) { if (a.Action === "REJECT" && a.Cycle === h.Cycle) lastReject = a; });
            if (h.Status === "REJECTED" && lastReject) {
                $("#rejectInfo").show().text("Rejected by " + lastReject.UserName + " (" + lastReject.StepName + "): " + (lastReject.Note || ""));
            } else {
                $("#rejectInfo").hide();
            }

            lines = $.map(data.Details, function (d) { return d; });
            editable = data.Can.Edit;
            renderApproval(data);
            attachments = data.Attachments || [];
            applyMode(data.Can);
            refreshGrid();
            loadRates(false);
        }

        function renderApproval(data) {
            var groups = { PREPARED: "Prepared", CHECKED: "Checked", APPROVED: "Approved" };
            var stLabel = { SIGNED: "Approved", WAITING: "Waiting", REJECTED: "Rejected", PENDING: "-" };
            var $s = $("#signatures").empty();
            $.each(data.Signatures, function (_, s) {
                var st = s.Group === "PREPARED" && s.Status === "SIGNED" ? "Submitted" : stLabel[s.Status];
                $("<div>").addClass("apr-sign " + s.Status)
                    .append($("<div class='grp'>").text(groups[s.Group]))
                    .append($("<div class='step'>").text(s.StepName))
                    .append($("<div class='name'>").text(s.Name || "-"))
                    .append($("<div class='st'>").text(st + (s.Date ? " · " + moment(s.Date).format("DD-MM-YYYY HH:mm") : "")))
                    .appendTo($s);
            });

            var $h = $("#history").empty();
            $.each(data.Approvals.slice().reverse(), function (_, a) {
                $("<li>")
                    .append($("<span>").text(moment(a.ActionDate).format("DD-MM-YYYY HH:mm") + "  "))
                    .append($("<span class='act'>").addClass(a.Action).text(a.Action))
                    .append($("<span>").text("  " + a.StepName + " - " + a.UserName + (a.Note ? ": " + a.Note : "")))
                    .appendTo($h);
            });
            if (!data.Approvals.length) $h.append("<li>No approval activity yet.</li>");
            $("#approvalSection").show();
        }

        function applyMode(can) {
            $("#attachDrop").toggle(editable);
            renderAttachments();
            $(".apr-input").prop("disabled", !editable);
            $("#supplierId").prop("disabled", !editable || lines.length > 0);
            $("#detailToolbar").toggle(editable);
            detailGrid.option("editing.allowUpdating", editable);
            detailGrid.option("selection.mode", editable ? "multiple" : "none");
            detailGrid.columnOption("actions", "visible", editable);

            $("#btn_save").toggle(editable);
            $("#btn_submit").toggle(!!can.Submit);
            $("#btn_delete").toggle(!!can.Delete);
            $("#btn_approve, #btn_reject").toggle(!!can.Approve);
            $("#btn_issue").toggle(!!can.Issue);
            $("#btn_printApr").toggle(aprId > 0);
            $("#btn_printLetter").toggle(!!can.PrintLetter);
        }

        function load() {
            HMA.post(urls.getByKey, { aprId: aprId }, function (result) { fill(result.data); }, { successMessage: false });
        }

        function save(onSaved) {
            detailGrid.saveEditData();
            var payload = {
                header: JSON.stringify(header()),
                lines: JSON.stringify($.map(lines, function (l) { return { ItemCode: l.ItemCode, NewPrice: l.NewPrice, Remark: l.Remark || "" }; }))
            };
            HMA.post(urls.save, payload, function (result) {
                if (aprId === 0) {
                    var go = function () { window.location.href = urls.form + "?id=" + result.returnId; };
                    if (pendingFiles.length) uploadFiles(result.returnId, pendingFiles, go); else go();
                    return;
                }
                if (onSaved) onSaved(); else load();
            }, { successMessage: !onSaved });
        }

        // ---------- Add items ----------
        function openCandidates() {
            var h = header();
            if (!h.SupplierId) { showErrorMesgGrowl("Please choose the supplier first."); return; }
            if (!h.PurchaseFrom || !h.PurchaseTo) { showErrorMesgGrowl("Please fill the purchase period first."); return; }

            if (!h.DocDate) { showErrorMesgGrowl("Please fill the document date first."); return; }

            HMA.post(urls.candidates, { supplierId: h.SupplierId, docDate: h.DocDate, purchaseFrom: h.PurchaseFrom, purchaseTo: h.PurchaseTo, effectiveFrom: h.EffectiveFrom }, function (result) {
                var existing = {};
                $.each(lines, function (_, l) { existing[l.ItemCode] = true; });
                var data = $.grep(result.data, function (c) { return !existing[c.ItemCode]; });
                $("#itemsSub").text($("#supplierId option:selected").text() + " - " + data.length + " item(s) available, PO period " +
                    moment(h.PurchaseFrom).format("DD MMM YYYY") + " - " + moment(h.PurchaseTo).format("DD MMM YYYY"));
                candidateGrid.option("dataSource", data);
                candidateGrid.clearSelection();
                $("#modalItems").modal("show");
                // Tinggi grid mengikuti layar supaya popup tidak punya scrollbar kedua.
                setTimeout(function () {
                    candidateGrid.option("height", Math.max(240, Math.min(460, $(window).height() - 300)));
                    candidateGrid.updateDimensions();
                }, 300);
            }, { successMessage: false });
        }

        function addSelected() {
            var rows = candidateGrid.getSelectedRowsData();
            if (!rows.length) { showErrorMesgGrowl("Please select at least one item."); return; }
            $.each(rows, function (_, c) {
                // New price dari master Set New Price; kalau belum ada, sementara sama dengan current price.
                var fromSet = c.SetNewPrice !== null && c.SetNewPrice !== undefined;
                lines.push(calcLine($.extend({}, c, {
                    NewPrice: fromSet ? c.SetNewPrice : c.CurrentPrice, Remark: fromSet ? (c.SetNewRemark || "") : "",
                    NewPriceSource: fromSet ? "SET" : "CURRENT", PoLastDate: null
                })));
            });
            $("#modalItems").modal("hide");
            $("#supplierId").prop("disabled", true);
            refreshGrid();
            syncCurrencyFromItems();
        }

        // ---------- Approve / reject ----------
        function openNote(action) {
            noteAction = action;
            $("#noteTitle").text(action === "approve" ? "Approve" : "Reject");
            $("#noteSub").text(action === "approve" ? "Optional note for the history" : "Reason is required; the document returns to the preparer");
            $("#noteText").val("");
            $("#btn_noteOk").toggleClass("dpf-btn-primary", action === "approve");
            $("#modalNote").modal("show");
        }

        $(function () {
            buildDetailGrid();
            buildCandidateGrid();
            HMA.select2("#supplierId", urls.supplierLookup, "Choose supplier");
            $("#supplierAddressId").select2({ width: "100%", placeholder: "Choose address", minimumResultsForSearch: 10 });
            $("#attachHint").text(attachCfg.extensions.join(", ").toUpperCase() + " · max " + fileSize(attachCfg.maxBytes) + " per file, up to " + attachCfg.maxFiles + " files");
            $("#attachInput").attr("accept", $.map(attachCfg.extensions, function (e) { return "." + e; }).join(","));
            $("#attachBrowse, #attachDrop > i").on("click", function () { $("#attachInput").trigger("click"); });
            $("#attachInput").on("change", function () { addFiles(this.files); this.value = ""; });
            $("#attachDrop").on("dragover dragenter", function (e) { e.preventDefault(); $(this).addClass("over"); })
                .on("dragleave dragend", function () { $(this).removeClass("over"); })
                .on("drop", function (e) { e.preventDefault(); $(this).removeClass("over"); addFiles(e.originalEvent.dataTransfer.files); });
            initCombo("#partCategory", headerOptions.PartCategory);
            initCombo("#partStatus", headerOptions.PartStatus);
            initCombo("#model", headerOptions.Model);
            $("#itemSearch").on("input", function () { detailGrid.searchByText($(this).val()); });
            $("#itemPageSize").on("change", function () {
                var n = parseInt($(this).val(), 10);
                detailGrid.option("paging", n > 0 ? { enabled: true, pageSize: n } : { enabled: false });
            });
            $("#supplierId").on("change", function () {
                if (!filling) loadAddresses($(this).val(), null, null);
            });
            $("#supplierAddressId").on("change", function () { renderAddressPreview(selectedAddress()); });

            if (aprId > 0) {
                load();
            } else {
                fillDefaults();
                loadAddresses(null);
                applyMode({ Submit: false });
                refreshTotals();
                loadRates(true);
            }

            $("#rateCurrency").on("change", function () { applyRates(true); });
            $("#docDate, #effectiveFrom").on("change", function () { if (editable) loadRates(true); });
            $("#btn_addItems").on("click", openCandidates);
            $("#btn_addSelected").on("click", addSelected);
            $("#btn_removeItems").on("click", function () { removeItems(detailGrid.getSelectedRowKeys()); });

            $("#btn_save").on("click", function () { save(); });
            $("#btn_submit").on("click", function () {
                showConfirmMesg("Save and submit this document for approval?", function () {
                    save(function () {
                        HMA.post(urls.submit, { aprId: aprId }, function () { load(); }).fail(load);
                    });
                }, "Confirmation");
            });
            $("#btn_delete").on("click", function () {
                showConfirmMesg("Delete this draft document?", function () {
                    HMA.post(urls.remove, { aprId: aprId }, function () { window.location.href = urls.list; });
                }, "Confirmation");
            });
            $("#btn_issue").on("click", function () {
                showConfirmMesg("Issue the Price Confirmation letter to the supplier? A letter number will be assigned.", function () {
                    HMA.post(urls.issue, { aprId: aprId }, function () {
                        load();
                        window.open(urls.printLetter + "?id=" + aprId);
                    });
                }, "Confirmation");
            });
            $("#btn_approve").on("click", function () { openNote("approve"); });
            $("#btn_reject").on("click", function () { openNote("reject"); });
            $("#btn_noteOk").on("click", function () {
                HMA.post(noteAction === "approve" ? urls.approve : urls.reject, { aprId: aprId, note: $("#noteText").val() }, function () {
                    $("#modalNote").modal("hide");
                    load();
                });
            });
            $("#btn_printApr").on("click", function () { window.open(urls.printApr + "?id=" + aprId); });
            $("#btn_printLetter").on("click", function () { window.open(urls.printLetter + "?id=" + aprId); });
        });
    })();
</script>
@endsection
