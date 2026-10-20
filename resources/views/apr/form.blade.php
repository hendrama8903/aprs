{{--
    Form Approval for Price Confirmation (header-detail).
    Mode edit (DRAFT/REJECTED + hak APR-EDIT) atau read-only; tombol approve/reject muncul untuk approver tahap saat ini.
    Harga current, qty & statistik PO dihitung ulang di server setiap Save; angka di layar sebelum Save adalah perkiraan.
--}}
@extends('layouts.admin')

@section('content')
<section class="content apr-form">
    <div class="dp-card apr-topbar">
        <div class="apr-title">
            <a href="{{ $backUrl }}" class="dp-btn dp-btn-outline" title="Back"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div class="apr-docno" id="docNo">New APR</div>
                <div class="apr-sub"><span id="statusBadge"></span> <span id="waitingInfo"></span></div>
            </div>
        </div>
        <div class="apr-actions">
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" style="display:none"><i class="fa fa-trash-o"></i> Delete</button>
            <button type="button" class="dp-btn dp-btn-outline" id="btn_printApr" style="display:none"><i class="fa fa-print"></i> Print APR</button>
            <button type="button" class="dp-btn dp-btn-outline" id="btn_printLetter" style="display:none"><i class="fa fa-envelope-o"></i> Print Letter</button>
            <button type="button" class="dp-btn dp-btn-outline-primary" id="btn_save" style="display:none"><i class="fa fa-save"></i> Save Draft</button>
            <button type="button" class="dp-btn dp-btn-primary" id="btn_submit" style="display:none"><i class="fa fa-paper-plane"></i> Submit</button>
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_reject" style="display:none"><i class="fa fa-times"></i> Reject</button>
            <button type="button" class="dp-btn dp-btn-success" id="btn_approve" style="display:none"><i class="fa fa-check"></i> Approve</button>
            <button type="button" class="dp-btn dp-btn-primary" id="btn_issue" style="display:none"><i class="fa fa-envelope"></i> Issue Letter</button>
        </div>
    </div>

    <div class="apr-alert" id="rejectInfo" style="display:none"></div>

    <div class="dp-card apr-section">
        <div class="apr-section-title">I. Background</div>
        <div class="apr-grid">
            <div class="apr-field apr-span2">
                <label>Supplier <span class="text-red">*</span></label>
                <select class="form-control apr-input" id="supplierId"></select>
            </div>
            <div class="apr-field apr-span2">
                <label>Letter Address <span class="text-red">*</span> <small>- printed on the Price Confirmation letter</small></label>
                <select class="form-control apr-input" id="supplierAddressId"></select>
                <div class="apr-addr-preview" id="addressPreview"></div>
            </div>
            <div class="apr-field">
                <label>Document Date <span class="text-red">*</span></label>
                <input type="date" class="form-control apr-input" id="docDate" />
            </div>
            <div class="apr-field">
                <label>Part Category <span class="text-red">*</span></label>
                <input type="text" class="form-control apr-input" id="partCategory" maxlength="50" />
            </div>
            <div class="apr-field">
                <label>Part Status <span class="text-red">*</span></label>
                <input type="text" class="form-control apr-input" id="partStatus" maxlength="50" />
            </div>
            <div class="apr-field">
                <label>Model <span class="text-red">*</span></label>
                <input type="text" class="form-control apr-input" id="model" maxlength="50" />
            </div>
            <div class="apr-field apr-span2">
                <label>Background <span class="text-red">*</span></label>
                <input type="text" class="form-control apr-input" id="background" maxlength="255" />
            </div>
            <div class="apr-field apr-span2">
                <label>Effective Period <span class="text-red">*</span></label>
                <div class="apr-range">
                    <input type="date" class="form-control apr-input" id="effectiveFrom" />
                    <span>to</span>
                    <input type="date" class="form-control apr-input" id="effectiveTo" />
                </div>
            </div>
            <div class="apr-field apr-span2">
                <label>Purchase Period (PO data for qty &amp; price statistics) <span class="text-red">*</span></label>
                <div class="apr-range">
                    <input type="date" class="form-control apr-input" id="purchaseFrom" />
                    <span>to</span>
                    <input type="date" class="form-control apr-input" id="purchaseTo" />
                </div>
            </div>
            <div class="apr-field apr-span2">
                <label>Exchange Rate (IDR) <small>- required when items are in USD / JPY</small></label>
                <div class="apr-rate">
                    <div class="apr-rate-h"></div><div class="apr-rate-h">Current</div><div class="apr-rate-h">New</div><div class="apr-rate-h">%</div>
                    <div class="apr-rate-cur">USD</div>
                    <input type="number" step="0.01" min="0" class="form-control apr-input apr-rate-input" id="rateUsdCurrent" />
                    <input type="number" step="0.01" min="0" class="form-control apr-input apr-rate-input" id="rateUsdNew" />
                    <div class="apr-rate-pct" id="rateUsdPct"></div>
                    <div class="apr-rate-cur">JPY</div>
                    <input type="number" step="0.01" min="0" class="form-control apr-input apr-rate-input" id="rateJpyCurrent" />
                    <input type="number" step="0.01" min="0" class="form-control apr-input apr-rate-input" id="rateJpyNew" />
                    <div class="apr-rate-pct" id="rateJpyPct"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="dp-card apr-section">
        <div class="apr-section-title">
            II. Cost Study
            <span class="apr-section-hint">Qty and PO statistics come from PO transactions in the purchase period. Increase above {{ $threshold }}% needs a remark.</span>
        </div>
        <div class="apr-toolbar" id="detailToolbar">
            <button type="button" class="dp-btn dp-btn-outline-primary" id="btn_addItems"><i class="fa fa-plus"></i> Add Items</button>
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_removeItems" disabled><i class="fa fa-minus"></i> Remove Selected</button>
            <span class="apr-legend"><i class="fa fa-clock-o text-yellow"></i> current price not valid on document date &nbsp; <i class="fa fa-exclamation-triangle text-yellow"></i> new price above highest PO price &nbsp; <i class="fa fa-pencil text-red"></i> remark required</span>
        </div>
        <div id="detailGrid"></div>
    </div>

    <div class="apr-row2">
        <div class="dp-card apr-section">
            <div class="apr-section-title">III. Total Purchase Amount (IDR)</div>
            <div class="apr-totals">
                <div><span>Current</span><b id="totCurrent">0</b></div>
                <div><span>New</span><b id="totNew">0</b></div>
                <div><span>Difference</span><b id="totDiff">0</b></div>
                <div><span>%</span><b id="totPct">-</b></div>
            </div>
            <div class="apr-bars">
                <div class="apr-bar"><div class="apr-bar-fill current" id="barCurrent"></div><span>Current</span></div>
                <div class="apr-bar"><div class="apr-bar-fill new" id="barNew"></div><span>New</span></div>
            </div>
        </div>
        <div class="dp-card apr-section">
            <div class="apr-section-title">IV. Conclusion</div>
            <div class="apr-field">
                <label class="apr-radio"><input type="radio" name="conclusion" value="ACCEPTABLE" class="apr-input" checked /> Price is <b>ACCEPTABLE</b></label>
                <label class="apr-radio"><input type="radio" name="conclusion" value="NOT_ACCEPTABLE" class="apr-input" /> Price is <b>NOT ACCEPTABLE</b></label>
            </div>
            <div class="apr-field">
                <label>Conclusion Note</label>
                <textarea class="form-control apr-input" id="conclusionNote" rows="2" maxlength="500"></textarea>
            </div>
            <div class="apr-field">
                <label>Conditions on Supplier Letter <small>(one per line)</small></label>
                <textarea class="form-control apr-input" id="letterConditions" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="dp-card apr-section" id="approvalSection" style="display:none">
        <div class="apr-section-title">Approval</div>
        <div class="apr-signs" id="signatures"></div>
        <div class="apr-history-title">History</div>
        <ul class="apr-history" id="history"></ul>
    </div>
</section>

<div class="modal fade sg-modal" id="modalItems" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-cubes"></i></div>
                <div>
                    <div class="dpf-header-title">Add Items</div>
                    <div class="dpf-header-sub" id="itemsSub">Active prices of the supplier with PO data in the purchase period</div>
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
    .apr-form .dp-card { margin-bottom: 14px; }
    .apr-topbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; }
    .apr-title { display: flex; align-items: center; gap: 12px; }
    .apr-docno { font-size: 18px; font-weight: 700; }
    .apr-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .apr-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .apr-alert { background: #FDECEA; color: #B42318; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
    .apr-section { padding: 14px 16px; }
    .apr-section-title { font-weight: 700; font-size: 14px; margin-bottom: 12px; }
    .apr-section-hint { font-weight: 400; font-size: 12px; color: #6b7280; margin-left: 8px; }
    .apr-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px 16px; }
    .apr-span2 { grid-column: span 2; }
    .apr-field label { font-size: 12px; color: #4b5563; margin-bottom: 4px; display: block; font-weight: 600; }
    .apr-field { margin-bottom: 8px; }
    .apr-range { display: flex; align-items: center; gap: 8px; }
    .apr-range input { flex: 1; min-width: 0; }
    .apr-range span { font-size: 12px; color: #6b7280; }
    .apr-rate { display: grid; grid-template-columns: 44px 1fr 1fr 64px; gap: 4px 8px; align-items: center; }
    .apr-rate-h { font-size: 11px; color: #6b7280; font-weight: 600; }
    .apr-rate-cur { font-weight: 600; font-size: 12px; }
    .apr-rate-pct { text-align: right; font-size: 12px; }
    .apr-toolbar { display: flex; gap: 6px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
    .apr-legend { margin-left: auto; font-size: 12px; color: #6b7280; }
    .apr-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .apr-row2 .dp-card { margin-bottom: 14px; }
    .apr-totals { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
    .apr-totals div { background: #F7F8FA; border-radius: 8px; padding: 8px 10px; }
    .apr-totals span { display: block; font-size: 11px; color: #6b7280; }
    .apr-totals b { font-size: 15px; }
    .apr-bars { display: flex; align-items: flex-end; gap: 40px; height: 150px; padding: 0 40px; border-bottom: 1px solid #e5e7eb; }
    .apr-bar { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; width: 80px; }
    .apr-bar span { font-size: 12px; margin-top: 4px; }
    .apr-bar-fill { width: 100%; border-radius: 4px 4px 0 0; min-height: 2px; }
    .apr-bar-fill.current { background: #9CA3AF; }
    .apr-bar-fill.new { background: #2453D4; }
    .apr-radio { font-weight: 400 !important; display: inline-block !important; margin-right: 18px; }
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
    .apr-history { list-style: none; padding: 0; margin: 0; font-size: 12px; }
    .apr-history li { padding: 5px 0; border-bottom: 1px dashed #eee; }
    .apr-history .act { font-weight: 700; }
    .apr-history .act.REJECT { color: #B42318; }
    .apr-history .act.APPROVE { color: #17824F; }
    .apr-cell-warn { color: #9A6200; margin-left: 4px; }
    .apr-cell-need { color: #B42318; }
    .apr-addr-preview { font-size: 12px; color: #4b5563; margin-top: 6px; white-space: pre-line; }
    .apr-addr-preview .muted { color: #9ca3af; }
    .apr-addr-preview .warn { color: #B42318; }
    @media (max-width: 1100px) { .apr-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .apr-row2 { grid-template-columns: 1fr; } }
</style>
<script type="text/javascript">
    (function () {
        var aprId = {{ (int) $aprId }};
        var defaults = @json($defaults);
        var threshold = {{ (float) $threshold }};
        var urls = {
            getByKey: "{{ url('/Apr/GetByKey') }}",
            candidates: "{{ url('/Apr/CandidateItems') }}",
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
            var max = Math.max(cur, nw, 1);
            $("#barCurrent").css("height", (cur / max * 100) + "%").attr("title", fmt(cur, 0));
            $("#barNew").css("height", (nw / max * 100) + "%").attr("title", fmt(nw, 0));

            $.each([["Usd", "#rateUsdPct"], ["Jpy", "#rateJpyPct"]], function (_, r) {
                var c = num($("#rate" + r[0] + "Current").val()), n = num($("#rate" + r[0] + "New").val());
                $(r[1]).empty().append(c > 0 && n > 0 ? AprStatus.pct(Math.round((n - c) / c * 10000) / 100) : "");
            });
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
        function buildDetailGrid() {
            detailGrid = $("#detailGrid").addClass("sg-grid").dxDataGrid($.extend(HMA.filterDefaults(), {
                dataSource: lines,
                keyExpr: "ItemCode",
                showBorders: true,
                showRowLines: true,
                showColumnLines: true,
                columnAutoWidth: true,
                allowColumnResizing: true,
                noDataText: "No items yet. Choose the supplier and purchase period, then click Add Items.",
                selection: { mode: "multiple", showCheckBoxesMode: "always" },
                editing: { mode: "cell", allowUpdating: true },
                paging: { enabled: false },
                scrolling: { mode: "standard" },
                onSelectionChanged: function (e) { $("#btn_removeItems").prop("disabled", e.selectedRowKeys.length === 0); },
                onRowUpdated: function () { refreshGrid(); },
                columns: [
                    { caption: "No", width: 45, alignment: "center", allowEditing: false, cellTemplate: function (c, o) { c.text(o.rowIndex + 1); } },
                    { dataField: "ItemCode", caption: "Item Code", allowEditing: false },
                    { dataField: "ItemName", caption: "Item Name", allowEditing: false },
                    { dataField: "Unit", caption: "Unit", allowEditing: false, alignment: "center" },
                    { dataField: "Currency", caption: "Curr", allowEditing: false, alignment: "center" },
                    { dataField: "Qty", caption: "Qty (period)", dataType: "number", format: "#,##0.##", allowEditing: false },
                    { dataField: "CurrentPrice", caption: "Current Price", dataType: "number", format: "#,##0.##", allowEditing: false, cellTemplate: currentPriceCell },
                    {
                        caption: "PO Price (period)", columns: [
                            { dataField: "PoCount", caption: "PO", dataType: "number", allowEditing: false, alignment: "center" },
                            { dataField: "PoAvgPrice", caption: "Avg", dataType: "number", format: "#,##0.##", allowEditing: false },
                            { dataField: "PoMinPrice", caption: "Min", dataType: "number", format: "#,##0.##", allowEditing: false },
                            { dataField: "PoMaxPrice", caption: "Max", dataType: "number", format: "#,##0.##", allowEditing: false },
                            { dataField: "PoLastPrice", caption: "Last", dataType: "number", format: "#,##0.##", allowEditing: false }
                        ]
                    },
                    {
                        dataField: "NewPrice", caption: "New Price", dataType: "number", format: "#,##0.##",
                        editorOptions: { min: 0 },
                        cssClass: "apr-edit-col",
                        cellTemplate: function (c, o) {
                            c.append($("<b>").text(fmt(o.value)));
                            if (o.data.OverPoMax) {
                                c.append($("<i class='fa fa-exclamation-triangle apr-cell-warn'>").attr("title", "Above highest PO price in period (" + fmt(o.data.PoMaxPrice) + ")"));
                            }
                        }
                    },
                    { dataField: "Movement", caption: "Movement", dataType: "number", format: "#,##0.##", allowEditing: false },
                    {
                        dataField: "MovementPct", caption: "%", dataType: "number", allowEditing: false, minWidth: 72,
                        cellTemplate: function (c, o) { c.append(AprStatus.pct(o.value)); }
                    },
                    { dataField: "CurrentAmount", caption: "Current Amt (IDR)", dataType: "number", format: "#,##0", allowEditing: false, visible: false },
                    { dataField: "NewAmount", caption: "New Amt (IDR)", dataType: "number", format: "#,##0", allowEditing: false },
                    {
                        dataField: "Remark", caption: "Remark", width: 200,
                        editorOptions: { maxLength: 255 },
                        cellTemplate: function (c, o) {
                            if (o.data.NeedRemark && !$.trim(o.value || "")) {
                                c.append($("<span class='apr-cell-need'><i class='fa fa-pencil'></i> required</span>"));
                            } else {
                                c.text(o.value || "");
                            }
                        }
                    }
                ]
            })).dxDataGrid("instance");
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
            $("#supplierAddressId").empty().append(new Option(snap ? snap.Label : "-", "", true, true));
            renderAddressPreview(snap, snap ? null : "No letter address on this document.");
        }

        // selectedId: alamat tersimpan di dokumen, kosong = pilih default.
        // snap: snapshot dokumen, untuk pesan kalau alamat tersimpan sudah tidak aktif / dihapus.
        function loadAddresses(supplierId, selectedId, snap) {
            var $sel = $("#supplierAddressId").empty();
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
                $sel.val(pick ? pick.AddressId : "");

                if (!addresses.length) {
                    renderAddressPreview(null, "This supplier has no address yet. Add one in the Supplier menu.");
                } else if (!pick && snap) {
                    renderAddressPreview(snap, "Address \"" + snap.Label + "\" is no longer available. Please choose another one.");
                } else {
                    renderAddressPreview(pick);
                }
            });
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
            $("#partCategory").val(defaults.part_category);
            $("#partStatus").val(defaults.part_status);
            $("#model").val(defaults.model);
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
            $("#partCategory").val(h.PartCategory);
            $("#partStatus").val(h.PartStatus);
            $("#model").val(h.Model);
            $("#background").val(h.Background);
            $("#effectiveFrom").val(h.EffectiveFrom);
            $("#effectiveTo").val(h.EffectiveTo);
            $("#purchaseFrom").val(h.PurchaseFrom);
            $("#purchaseTo").val(h.PurchaseTo);
            $("#rateUsdCurrent").val(h.RateUsdCurrent || "");
            $("#rateUsdNew").val(h.RateUsdNew || "");
            $("#rateJpyCurrent").val(h.RateJpyCurrent || "");
            $("#rateJpyNew").val(h.RateJpyNew || "");
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
            applyMode(data.Can);
            refreshGrid();
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
            $(".apr-input").prop("disabled", !editable);
            $("#supplierId").prop("disabled", !editable || lines.length > 0);
            $("#detailToolbar").toggle(editable);
            detailGrid.option("editing.allowUpdating", editable);
            detailGrid.option("selection.mode", editable ? "multiple" : "none");

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
                    window.location.href = urls.form + "?id=" + result.returnId;
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

            HMA.post(urls.candidates, { supplierId: h.SupplierId, docDate: h.DocDate, purchaseFrom: h.PurchaseFrom, purchaseTo: h.PurchaseTo }, function (result) {
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
                lines.push(calcLine($.extend({}, c, { NewPrice: c.CurrentPrice, Remark: "", PoLastDate: null })));
            });
            $("#modalItems").modal("hide");
            $("#supplierId").prop("disabled", true);
            refreshGrid();
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
            }

            $(".apr-rate-input").on("input", refreshGrid);
            $("#btn_addItems").on("click", openCandidates);
            $("#btn_addSelected").on("click", addSelected);
            $("#btn_removeItems").on("click", function () {
                var keys = detailGrid.getSelectedRowKeys();
                lines = $.grep(lines, function (l) { return keys.indexOf(l.ItemCode) < 0; });
                detailGrid.clearSelection();
                $("#supplierId").prop("disabled", lines.length > 0);
                refreshGrid();
            });

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
