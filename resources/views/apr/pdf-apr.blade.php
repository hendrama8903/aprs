{{-- Form APR internal (dompdf). $d = AprRepo::printData(), $company = config('apr.company') + logo_path. --}}
@php
    $a = $d->Apr;
    $t = $d->Totals;
    $n = fn ($v, $dec = 2) => $v === null ? '-' : number_format((float) $v, $dec, '.', ',');
    $price = fn ($v, $cur) => $v === null ? '-' : number_format((float) $v, $cur === 'IDR' ? 0 : 2, '.', ',');
    $pct = fn ($c, $nw) => $c > 0 ? number_format(($nw - $c) / $c * 100, 1) . '%' : '-';
    $showCurr = collect($d->Details)->contains(fn ($l) => $l->Currency !== 'IDR');
    $priceUnit = $showCurr ? '/unit' : '(Idr/unit)';
    $signs = collect($d->Signatures);
    $statusLabel = ['DRAFT' => 'DRAFT', 'IN_APPROVAL' => 'IN APPROVAL', 'REJECTED' => 'REJECTED', 'APPROVED' => 'APPROVED', 'ISSUED' => 'ISSUED'][$a->status];
    $maxAmount = max($t->CurrentAmount, $t->NewAmount, 1);
    $period = $a->purchase_from->format('M') . '-' . $a->purchase_to->format('M');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $a->doc_no }}</title>
<style>
    @page { margin: 22px 26px 30px 26px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
    .top { width: 100%; }
    .top td { vertical-align: middle; padding: 0; }
    .logo { width: 150px; }
    .logo img { height: 26px; }
    .logo .name { font-size: 10px; font-weight: bold; }
    .title { text-align: center; font-size: 13px; font-weight: bold; text-decoration: underline; }
    .watermark { position: fixed; top: 38%; left: 12%; font-size: 70px; color: rgba(200, 0, 0, .10); transform: rotate(-30deg); font-weight: bold; }
    table { border-collapse: collapse; width: 100%; }
    .box td, .box th { border: 1px solid #333; padding: 3px 4px; }
    .head { margin-top: 8px; }
    .head td { padding: 3px 4px; border: 1px solid #333; }
    .head .lbl { width: 120px; font-weight: bold; }
    h3 { font-size: 9px; margin: 10px 0 4px; text-decoration: underline; }
    .sub { margin: 2px 0 2px 10px; }
    th { background: #e4e4e4; font-weight: bold; text-align: center; }
    .r { text-align: right; }
    .c { text-align: center; }
    .b { font-weight: bold; }
    .up { color: #b42318; }
    .total td { font-weight: bold; background: #f4f4f4; }
    .bars { height: 90px; border-bottom: 1px solid #333; border-left: 1px solid #333; }
    .bar { display: inline-block; width: 70px; margin: 0 24px; vertical-align: bottom; text-align: center; }
    .bar div { background: #888; }
    .bar.new div { background: #2453d4; }
    .sign td { border: 1px solid #333; text-align: center; padding: 2px; }
    .sign .grp { font-weight: bold; background: #f1f1f1; }
    .sign .date { text-align: left; font-size: 7px; }
    .sign .space { height: 36px; vertical-align: middle; font-size: 7px; color: #17824f; }
    .sign .space.wait { color: #9a6200; }
    .sign .space.rej { color: #b42318; }
    .footer { position: fixed; bottom: -18px; left: 0; right: 0; font-size: 7px; color: #666; }
</style>
</head>
<body>
@if (!in_array($a->status, ['APPROVED', 'ISSUED'], true))
    <div class="watermark">{{ $statusLabel }}</div>
@endif
<div class="footer">{{ $a->doc_no }} &middot; printed {{ now()->format('d-m-Y H:i') }} &middot; status {{ $statusLabel }}</div>

<table class="top">
    <tr>
        <td class="logo">
            @if ($company['logo_path'])
                <img src="{{ $company['logo_path'] }}" alt="">
            @else
                <span class="name">{{ $company['name'] }}</span>
            @endif
        </td>
        <td class="title">APPROVAL FOR PRICE CONFIRMATION</td>
        <td class="logo"></td>
    </tr>
</table>

<table class="head">
    <tr>
        <td class="lbl">SUPPLIER</td><td>: {{ $a->supplier->supplier_name }}</td>
        <td class="lbl">DOCUMENT NO</td><td colspan="3">: {{ $a->doc_no }}</td>
    </tr>
    <tr>
        <td class="lbl">PART CATEGORY</td><td>: {{ $a->part_category }}</td>
        <td class="lbl">PUR AMOUNT PER PERIOD</td><td colspan="3">: Rp {{ $n($t->NewAmount, 0) }}</td>
    </tr>
    <tr>
        <td class="lbl"></td><td></td>
        <td class="lbl">MODEL</td><td colspan="3">: {{ $a->model }}</td>
    </tr>
    <tr>
        <td class="lbl">PART STATUS</td><td>: {{ $a->part_status }}</td>
        <td class="lbl">EXC RATE</td>
        <td>
            @if ($a->rate_usd_new > 0) Rp {{ $n($a->rate_usd_new) }}/USD<br> @endif
            @if ($a->rate_jpy_new > 0) Rp {{ $n($a->rate_jpy_new) }}/JPY @endif
        </td>
        <td class="lbl" style="width: 52px;">Effective :</td>
        <td>{{ $a->effective_from->format("F'y") }} - {{ $a->effective_to->format("F'y") }}</td>
    </tr>
</table>

<h3>I. BACKGROUND</h3>
<div class="sub">A. {{ $a->background }}</div>
<div class="sub">B. Part : {{ $a->part_category }}</div>
<div class="sub">C. Supplier : {{ $a->supplier->supplier_name }}</div>
<div class="sub">D. Background Condition</div>
<table class="box" style="width: 55%; margin-left: 10px;">
    <tr><th>Category</th><th></th><th>Current</th><th>New</th><th>%</th></tr>
    <tr>
        <td rowspan="2">Exchange Rate</td><td class="c">USD</td>
        <td class="r">{{ $a->rate_usd_current > 0 ? $n($a->rate_usd_current) : '-' }}</td>
        <td class="r">{{ $a->rate_usd_new > 0 ? $n($a->rate_usd_new) : '-' }}</td>
        <td class="r">{{ $pct($a->rate_usd_current, $a->rate_usd_new) }}</td>
    </tr>
    <tr>
        <td class="c">JPY</td>
        <td class="r">{{ $a->rate_jpy_current > 0 ? $n($a->rate_jpy_current) : '-' }}</td>
        <td class="r">{{ $a->rate_jpy_new > 0 ? $n($a->rate_jpy_new) : '-' }}</td>
        <td class="r">{{ $pct($a->rate_jpy_current, $a->rate_jpy_new) }}</td>
    </tr>
</table>

<h3>II. COST STUDY</h3>
<div class="sub">A. Detail cost of part</div>
<table class="box">
    <thead>
        <tr>
            <th rowspan="2" style="width: 18px;">No</th>
            <th rowspan="2" style="width: 52px;">Item Code</th>
            <th rowspan="2">Item Name</th>
            <th rowspan="2" style="width: 40px;">Qty<br>{{ $period }}</th>
            <th rowspan="2" style="width: 30px;">Unit</th>
            @if ($showCurr)<th rowspan="2" style="width: 26px;">Curr</th>@endif
            <th rowspan="2" style="width: 58px;">Current Price<br>{{ $priceUnit }}</th>
            <th colspan="2">Price Movement</th>
            <th rowspan="2" style="width: 58px;">New Price<br>{{ $priceUnit }}</th>
            <th rowspan="2" style="width: 80px;">Remark</th>
        </tr>
        <tr><th style="width: 46px;">Amount</th><th style="width: 34px;">%</th></tr>
    </thead>
    <tbody>
        @foreach ($d->Details as $l)
            <tr>
                <td class="c">{{ $l->LineNo }}</td>
                <td>{{ $l->ItemCode }}</td>
                <td>{{ $l->ItemName }}</td>
                <td class="r">{{ $n($l->Qty, 0) }}</td>
                <td class="c">{{ $l->Unit }}</td>
                @if ($showCurr)<td class="c">{{ $l->Currency }}</td>@endif
                <td class="r">{{ $price($l->CurrentPrice, $l->Currency) }}</td>
                <td class="r">{{ $l->Movement == 0 ? '-' : $price($l->Movement, $l->Currency) }}</td>
                <td class="r {{ $l->MovementPct > 0 ? 'up' : '' }}">{{ $l->MovementPct === null || $l->Movement == 0 ? '-' : number_format($l->MovementPct, 1) . '%' }}</td>
                <td class="r">{{ $price($l->NewPrice, $l->Currency) }}</td>
                <td>{{ $l->Remark }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="{{ $showCurr ? 6 : 5 }}">TOTAL AMOUNT (Rp)</td>
            <td class="r">{{ $n($t->CurrentAmount, 0) }}</td>
            <td class="r">{{ $t->Diff == 0 ? '-' : $n($t->Diff, 0) }}</td>
            <td class="r">{{ $t->DiffPct === null || $t->Diff == 0 ? '-' : number_format($t->DiffPct, 2) . '%' }}</td>
            <td class="r">{{ $n($t->NewAmount, 0) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<h3>III. PURCHASE AMOUNT</h3>
<table style="width: 100%;">
    <tr>
        <td style="width: 52%; vertical-align: top;">
            <div class="sub">A. Total Purchase amount base on actual {{ $a->purchase_from->format('M') }} - {{ $a->purchase_to->format('M Y') }}</div>
            <table class="box" style="margin-left: 10px; width: 95%;">
                <tr><th>Total Amount</th><th>Current</th><th>New</th><th>Movement</th><th>%</th></tr>
                <tr>
                    <td class="c">Rp</td>
                    <td class="r">{{ $n($t->CurrentAmount, 0) }}</td>
                    <td class="r">{{ $n($t->NewAmount, 0) }}</td>
                    <td class="r">{{ $n($t->Diff, 0) }}</td>
                    <td class="r">{{ $t->DiffPct === null ? '-' : number_format($t->DiffPct, 2) . '%' }}</td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: top;">
            <div class="sub">B. Total purchase amount Figure (x 1000)</div>
            <div class="bars">
                <div class="bar"><span>Rp {{ $n($t->CurrentAmount / 1000, 0) }}</span><div style="height: {{ round($t->CurrentAmount / $maxAmount * 70) }}px;"></div></div>
                <div class="bar new"><span>Rp {{ $n($t->NewAmount / 1000, 0) }}</span><div style="height: {{ round($t->NewAmount / $maxAmount * 70) }}px;"></div></div>
            </div>
            <div><span class="bar" style="height:auto">Current</span><span class="bar" style="height:auto">New</span></div>
        </td>
    </tr>
</table>

<h3>IV. CONCLUSION</h3>
<div class="sub">
    Based on above study, {{ $a->supplier->supplier_name }} price is <b>{{ $a->conclusion === 'ACCEPTABLE' ? 'ACCEPTABLE' : 'NOT ACCEPTABLE' }}</b>.
    @if ($a->conclusion_note) <br>{{ $a->conclusion_note }} @endif
</div>

<div style="margin-top: 12px; text-align: right;">{{ $company['city'] }}, {{ ($a->submitted_at ?? $a->doc_date)->format('d F Y') }}</div>
@php
    // Seperti form kertas: jabatan tertinggi paling kiri.
    $approved = $signs->where('Group', 'APPROVED')->reverse()->values();
    $checked = $signs->where('Group', 'CHECKED')->reverse()->values();
    $prepared = $signs->where('Group', 'PREPARED')->values();
    $cols = $approved->concat($checked)->concat($prepared);
    $stamp = fn ($s) => match ($s->Status) {
        'SIGNED' => ['', $s->Group === 'PREPARED' ? 'SUBMITTED' : 'APPROVED'],
        'WAITING' => ['wait', 'WAITING'],
        'REJECTED' => ['rej', 'REJECTED'],
        default => ['', ''],
    };
@endphp
<table class="sign" style="margin-top: 4px;">
    <tr>
        @if ($approved->count())<td class="grp" colspan="{{ $approved->count() }}">Approved</td>@endif
        @if ($checked->count())<td class="grp" colspan="{{ $checked->count() }}">Checked</td>@endif
        <td class="grp" colspan="{{ $prepared->count() }}">Prepared</td>
    </tr>
    <tr>@foreach ($cols as $s)<td class="date">Date: {{ in_array($s->Status, ['SIGNED', 'REJECTED'], true) ? $s->Date?->format('d/m/y') : '' }}</td>@endforeach</tr>
    <tr>
        @foreach ($cols as $s)
            @php([$cls, $txt] = $stamp($s))
            <td class="space {{ $cls }}">{{ $txt }}</td>
        @endforeach
    </tr>
    <tr>@foreach ($cols as $s)<td>{{ $s->Name ?? '-' }}</td>@endforeach</tr>
    <tr>@foreach ($cols as $s)<td>{{ $s->StepName }}</td>@endforeach</tr>
</table>
<div style="font-size: 7px; color: #666; margin-top: 3px;">Approved electronically in APR system; approval history is kept in the system.</div>
</body>
</html>
