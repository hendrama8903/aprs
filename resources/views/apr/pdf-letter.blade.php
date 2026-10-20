{{-- Surat Price Confirmation ke supplier + lampiran (dompdf). Hanya untuk dokumen ISSUED. --}}
@php
    $a = $d->Apr;
    $s = $a->supplier;
    // Alamat & attention diambil dari snapshot di dokumen (letter_*), bukan master supplier saat ini.
    $price = fn ($v, $cur) => number_format((float) $v, $cur === 'IDR' ? 0 : 2, '.', ',');
    $director = collect($d->Signatures)->firstWhere('StepName', 'Director');
    $conditions = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $a->letter_conditions))));
    $rates = [];
    if ($a->rate_usd_new > 0 && collect($d->Details)->contains('Currency', 'USD')) {
        $rates[] = '1 US$ = Rp. ' . number_format($a->rate_usd_new, 2, '.', ',');
    }
    if ($a->rate_jpy_new > 0 && collect($d->Details)->contains('Currency', 'JPY')) {
        $rates[] = 'JPY = Rp. ' . number_format($a->rate_jpy_new, 2, '.', ',');
    }
    if ($rates !== []) {
        $conditions[] = 'Exchange rate ' . implode(', ', $rates);
    }
    $conditions[] = 'Effective period : ' . $a->effective_from->format('F Y') . ' - ' . $a->effective_to->format('F Y');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $a->ext_doc_no }}</title>
<style>
    @page { margin: 36px 48px 40px 48px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; line-height: 1.45; }
    .kop { border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 18px; }
    .kop .name { font-size: 15px; font-weight: bold; }
    .kop .addr { font-size: 8px; color: #333; }
    table { border-collapse: collapse; }
    .meta td { padding: 1px 6px 1px 0; vertical-align: top; }
    .subject { margin: 16px 0; font-weight: bold; text-decoration: underline; }
    ol { margin: 4px 0 0 0; padding-left: 18px; }
    .signs { width: 100%; margin-top: 26px; }
    .signs td { width: 50%; vertical-align: top; }
    .signs .space { height: 50px; color: #17824f; font-size: 8px; padding-top: 14px; }
    .att { width: 100%; }
    .att td, .att th { border: 1px solid #333; padding: 3px 5px; font-size: 9px; }
    .att th { background: #e9e9e9; }
    .r { text-align: right; }
    .c { text-align: center; }
    .page-break { page-break-before: always; }
    .footer { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 7px; color: #666; text-align: right; }
</style>
</head>
<body>
<div class="footer">{{ $a->ext_doc_no }}</div>

<div class="kop">
    <div class="name">{{ $company['name'] }}</div>
    <div class="addr">{{ $company['address'] }}</div>
</div>

<div>{{ $company['city'] }}, {{ $a->issued_at->format('d F Y') }}</div>
<div>No. {{ $a->ext_doc_no }}</div>

<table class="meta" style="margin-top: 14px;">
    <tr><td>To</td><td>:</td><td><b>{{ $s->supplier_name }}</b>@if ($a->letter_address)<br>{!! nl2br(e($a->letter_address)) !!}@endif</td></tr>
    @if ($a->letter_attention_name)
        <tr><td>Attn.</td><td>:</td><td>{{ $a->letter_attention_name }}@if ($a->letter_attention_title)<br>{{ $a->letter_attention_title }}@endif</td></tr>
    @endif
</table>

<div class="subject">Subject : Price Confirmation</div>

<p>Dear Sir,</p>
<p>With this letter, we would like to inform you the Price Confirmation for the listed/attached jobs as below :</p>
<p style="margin-left: 18px;"><b>attached</b> ({{ count($d->Details) }} item{{ count($d->Details) > 1 ? 's' : '' }})</p>

<p style="margin-bottom: 0;">With conditions :</p>
<ol>
    @foreach ($conditions as $c)
        <li>{{ $c }}</li>
    @endforeach
</ol>

<p style="margin-top: 14px;">Thank you for your attention and cooperation.</p>

<table class="signs">
    <tr><td>Yours Faithfully,</td><td>Confirmed by,</td></tr>
    <tr>
        <td class="space">Approved electronically<br>{{ $director?->Date?->format('d-m-Y H:i') }}</td>
        <td class="space"></td>
    </tr>
    <tr>
        <td><u>{{ $director?->Name ?? '..............................' }}</u><br>Director</td>
        <td><u>{{ $a->letter_attention_name ?? '..............................' }}</u><br>{{ $a->letter_attention_title ?? $s->supplier_name }}</td>
    </tr>
</table>

<table class="meta" style="margin-top: 22px;">
    <tr><td>Cc.</td><td>:</td><td>- Finance &amp; Accounting Div-Head</td></tr>
    <tr><td>Refer to</td><td>:</td><td>{{ $a->doc_no }}</td></tr>
</table>

<div class="page-break"></div>
<div style="margin-bottom: 8px;">Attachment Letter No. {{ $a->ext_doc_no }}</div>
<table class="att">
    <thead>
        <tr>
            <th rowspan="2">No</th><th rowspan="2">Item Code</th><th rowspan="2">Item Name</th><th rowspan="2">Unit</th><th rowspan="2">Curr</th>
            <th colspan="2">Price / Unit</th>
        </tr>
        <tr><th>Current</th><th>New</th></tr>
    </thead>
    <tbody>
        @foreach ($d->Details as $l)
            <tr>
                <td class="c">{{ $l->LineNo }}</td>
                <td>{{ $l->ItemCode }}</td>
                <td>{{ $l->ItemName }}</td>
                <td class="c">{{ $l->Unit }}</td>
                <td class="c">{{ $l->Currency }}</td>
                <td class="r">{{ $price($l->CurrentPrice, $l->Currency) }}</td>
                <td class="r"><b>{{ $price($l->NewPrice, $l->Currency) }}</b></td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
