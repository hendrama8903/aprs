{{-- Form APR internal (dompdf). $d = AprRepo::printData(), $company = config('apr.company'). --}}
@php
    $a = $d->Apr;
    $t = $d->Totals;
    $n = fn ($v, $dec = 2) => $v === null ? '-' : number_format((float) $v, $dec, '.', ',');
    $price = fn ($v, $cur) => $v === null ? '-' : number_format((float) $v, $cur === 'IDR' ? 0 : 2, '.', ',');
    $pct = fn ($c, $nw) => $c > 0 ? number_format(($nw - $c) / $c * 100, 1) . '%' : '-';
    $signs = collect($d->Signatures);
    $statusLabel = ['DRAFT' => 'DRAFT', 'IN_APPROVAL' => 'IN APPROVAL', 'REJECTED' => 'REJECTED', 'APPROVED' => 'APPROVED', 'ISSUED' => 'ISSUED'][$a->status];
    $maxAmount = max($t->CurrentAmount, $t->NewAmount, 1);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $a->doc_no }}</title>
<style>
    @page { margin: 22px 26px 30px 26px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
    .company { font-size: 11px; font-weight: bold; }
    .title { text-align: center; font-size: 13px; font-weight: bold; text-decoration: underline; margin: 6px 0 8px; }
    .watermark { position: fixed; top: 38%; left: 12%; font-size: 70px; color: rgba(200, 0, 0, .10); transform: rotate(-30deg); font-weight: bold; }
    table { border-collapse: collapse; width: 100%; }
    .box td, .box th { border: 1px solid #333; padding: 3px 4px; }
    .head td { padding: 3px 4px; border: 1px solid #333; }
    .head .lbl { width: 90px; font-weight: bold; background: #f1f1f1; }
    h3 { font-size: 9px; margin: 10px 0 4px; }
    .sub { margin: 2px 0 2px 10px; }
    th { background: #e9e9e9; font-weight: bold; text-align: center; }
    .r { text-align: right; }
    .c { text-align: center; }
    .up { color: #b42318; }
    .bars { height: 90px; border-bottom: 1px solid #333; }
    .bar { display: inline-block; width: 70px; margin: 0 24px; vertical-align: bottom; text-align: center; }
    .bar div { background: #888; }
    .bar.new div { background: #2453d4; }
    .sign td { border: 1px solid #333; text-align: center; padding: 2px; }
    .sign .grp { font-weight: bold; background: #f1f1f1; }
    .sign .space { height: 34px; vertical-align: middle; font-size: 7px; color: #17824f; }
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

<div class="company">{{ $company['name'] }}</div>
<div class="title">APPROVAL FOR PRICE CONFIRMATION</div>

<table class="head">
    <tr>
        <td class="lbl">SUPPLIER</td><td>{{ $a->supplier->supplier_name }}</td>
        <td class="lbl">DOCUMENT NO</td><td>{{ $a->doc_no }}</td>
    </tr>
    <tr>
        <td class="lbl">PART CATEGORY</td><td>{{ $a->part_category }}</td>
        <td class="lbl">PUR AMOUNT PER PERIOD</td><td>Rp {{ $n($t->NewAmount, 0) }}</td>
    </tr>
    <tr>
        <td class="lbl">MODEL</td><td>{{ $a->model }}</td>
        <td class="lbl">PURCHASE PERIOD</td><td>{{ $a->purchase_from->format('M Y') }} - {{ $a->purchase_to->format('M Y') }}</td>
    </tr>
    <tr>
        <td class="lbl">PART STATUS</td><td>{{ $a->part_status }}</td>
        <td class="lbl">EXC RATE / EFFECTIVE</td>
        <td>
            @if ($a->rate_usd_new > 0) Rp {{ $n($a->rate_usd_new) }}/USD @endif
            @if ($a->rate_jpy_new > 0) &nbsp; Rp {{ $n($a->rate_jpy_new) }}/JPY @endif
            &nbsp;|&nbsp; {{ $a->effective_from->format('F Y') }} - {{ $a->effective_to->format('F Y') }}
        </td>
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
<div class="sub">A. Detail cost of part (PO data {{ $a->purchase_from->format('d M Y') }} - {{ $a->purchase_to->format('d M Y') }})</div>
<table class="box">
    <thead>
        <tr>
            <th rowspan="2">No</th><th rowspan="2">Item Code</th><th rowspan="2">Item Name</th><th rowspan="2">Qty</th><th rowspan="2">Unit</th><th rowspan="2">Curr</th>
            <th colspan="2">PO Price (period)</th>
            <th rowspan="2">Current Price</th><th rowspan="2">Price Movement</th><th rowspan="2">New Price</th><th rowspan="2">%</th><th rowspan="2">Remark</th>
        </tr>
        <tr><th>Avg</th><th>Max</th></tr>
    </thead>
    <tbody>
        @foreach ($d->Details as $l)
            <tr>
                <td class="c">{{ $l->LineNo }}</td>
                <td>{{ $l->ItemCode }}</td>
                <td>{{ $l->ItemName }}</td>
                <td class="r">{{ $n($l->Qty, 0) }}</td>
                <td class="c">{{ $l->Unit }}</td>
                <td class="c">{{ $l->Currency }}</td>
                <td class="r">{{ $price($l->PoAvgPrice, $l->Currency) }}</td>
                <td class="r">{{ $price($l->PoMaxPrice, $l->Currency) }}</td>
                <td class="r">{{ $price($l->CurrentPrice, $l->Currency) }}</td>
                <td class="r">{{ $l->Movement == 0 ? '-' : $price($l->Movement, $l->Currency) }}</td>
                <td class="r"><b>{{ $price($l->NewPrice, $l->Currency) }}</b></td>
                <td class="r {{ $l->MovementPct > 0 ? 'up' : '' }}">{{ $l->MovementPct === null ? '-' : number_format($l->MovementPct, 1) . '%' }}</td>
                <td>{{ $l->Remark }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>III. PURCHASE AMOUNT</h3>
<table style="width: 100%;">
    <tr>
        <td style="width: 52%; vertical-align: top;">
            <div class="sub">A. Total purchase amount based on actual {{ $a->purchase_from->format('M Y') }} - {{ $a->purchase_to->format('M Y') }} (IDR)</div>
            <table class="box" style="margin-left: 10px; width: 95%;">
                <tr><th>Current</th><th>New</th><th>Movement</th><th>%</th></tr>
                <tr>
                    <td class="r">{{ $n($t->CurrentAmount, 0) }}</td>
                    <td class="r">{{ $n($t->NewAmount, 0) }}</td>
                    <td class="r">{{ $n($t->Diff, 0) }}</td>
                    <td class="r">{{ $t->DiffPct === null ? '-' : number_format($t->DiffPct, 2) . '%' }}</td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: top;">
            <div class="sub">B. Total purchase amount figure (x 1000)</div>
            <div class="bars">
                <div class="bar"><span>{{ $n($t->CurrentAmount / 1000, 0) }}</span><div style="height: {{ round($t->CurrentAmount / $maxAmount * 70) }}px;"></div></div>
                <div class="bar new"><span>{{ $n($t->NewAmount / 1000, 0) }}</span><div style="height: {{ round($t->NewAmount / $maxAmount * 70) }}px;"></div></div>
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
        'SIGNED' => ['', ($s->Group === 'PREPARED' ? 'SUBMITTED' : 'APPROVED') . '<br>' . $s->Date?->format('d-m-Y H:i')],
        'WAITING' => ['wait', 'WAITING'],
        'REJECTED' => ['rej', 'REJECTED<br>' . $s->Date?->format('d-m-Y H:i')],
        default => ['', ''],
    };
@endphp
<table class="sign" style="margin-top: 4px;">
    <tr>
        @if ($approved->count())<td class="grp" colspan="{{ $approved->count() }}">Approved</td>@endif
        @if ($checked->count())<td class="grp" colspan="{{ $checked->count() }}">Checked</td>@endif
        <td class="grp" colspan="{{ $prepared->count() }}">Prepared</td>
    </tr>
    <tr>
        @foreach ($cols as $s)
            @php([$cls, $txt] = $stamp($s))
            <td class="space {{ $cls }}">{!! $txt !!}</td>
        @endforeach
    </tr>
    <tr>@foreach ($cols as $s)<td>{{ $s->Name ?? '-' }}</td>@endforeach</tr>
    <tr>@foreach ($cols as $s)<td>{{ $s->StepName }}</td>@endforeach</tr>
</table>
<div style="font-size: 7px; color: #666; margin-top: 3px;">Approved electronically in APR system; approval history is kept in the system.</div>
</body>
</html>
