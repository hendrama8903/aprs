{{-- Surat Price Confirmation ke supplier (hal. 1) + Attachment Letter (hal. 2), dompdf. Sebelum ISSUED dicetak sebagai draft. --}}
@php
    $a = $d->Apr;
    $s = $a->supplier;
    $issued = $a->status === \App\Models\Apr::StatusIssued;
    // Alamat & attention diambil dari snapshot di dokumen (letter_*), bukan master supplier saat ini.
    $price = fn ($v, $cur) => number_format((float) $v, $cur === 'IDR' ? 0 : 2, '.', ',');
    $showCurr = collect($d->Details)->contains(fn ($l) => $l->Currency !== 'IDR');
    $director = collect($d->Signatures)->firstWhere('StepName', 'Director');

    // Urutan seperti surat kertas: syarat pertama (mis. VAT), kurs, periode berlaku, lalu syarat lainnya.
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $a->letter_conditions))));
    // Kurs selalu dicantumkan (juga untuk supplier IDR), format seperti surat kertas: "1 US$ = Rp. 16,503,- JPY = Rp. 109.48".
    $rates = [];
    if ($a->rate_usd_new > 0) {
        $usd = (float) $a->rate_usd_new;
        $rates[] = '1 US$ = Rp. ' . (floor($usd) == $usd ? number_format($usd, 0, '.', ',') . ',-' : number_format($usd, 2, '.', ','));
    }
    if ($a->rate_jpy_new > 0) {
        $rates[] = 'JPY = Rp. ' . number_format($a->rate_jpy_new, 2, '.', ',');
    }
    $fixed = [];
    if ($rates !== []) {
        $fixed[] = 'Exchange rate ' . implode(' ', $rates);
    }
    $fixed[] = 'Effective period : ' . $a->effective_from->format('F Y') . ' - ' . $a->effective_to->format('F Y');
    $conditions = array_merge(array_slice($lines, 0, 1), $fixed, array_slice($lines, 1));
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $a->ext_doc_no ?? $a->doc_no }}</title>
<style>
    @page { margin: 34px 50px 30px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; line-height: 1.45; }
    table { border-collapse: collapse; }
    .top { width: 100%; margin-bottom: 18px; }
    .top td { vertical-align: top; padding: 0; }
    .logo img { height: 34px; }
    .logo .name { font-size: 14px; font-weight: bold; letter-spacing: .5px; }
    .form-no { text-align: right; font-size: 9px; }
    .meta td { padding: 1px 6px 1px 0; vertical-align: top; }
    .to-box { border: 1px solid #111; padding: 4px 8px; }
    .subject { margin: 18px 0 14px 150px; text-decoration: underline; }
    ol { margin: 4px 0 0 0; padding-left: 18px; }
    .signs { width: 100%; margin-top: 26px; }
    .signs td { width: 50%; vertical-align: top; }
    .signs .space { height: 56px; color: #17824f; font-size: 8px; padding-top: 16px; }
    .bottom { width: 100%; margin-top: 22px; }
    .bottom td { vertical-align: bottom; }
    /* Kop alamat perusahaan di dasar halaman surat (hanya hal. 1). */
    .kop { position: absolute; bottom: 0; left: 0; right: 0; border-top: 2px solid #111; padding-top: 3px; text-align: center; }
    .kop .name { font-size: 11px; font-weight: bold; }
    .kop .addr { font-size: 8px; }
    .watermark { position: fixed; top: 38%; left: 8%; font-size: 70px; color: rgba(200, 0, 0, .10); transform: rotate(-30deg); font-weight: bold; }
    .att-title { margin-bottom: 8px; }
    .att { width: 100%; }
    .att td, .att th { border: 1px solid #333; padding: 3px 5px; font-size: 9px; }
    .att th { background: #e4e4e4; font-weight: bold; text-align: center; }
    .r { text-align: right; }
    .c { text-align: center; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>
@unless ($issued)
    <div class="watermark">DRAFT</div>
@endunless

<table class="top">
    <tr>
        <td class="logo">
            @if ($company['logo_path'])
                <img src="{{ $company['logo_path'] }}" alt="">
            @else
                <span class="name">{{ $company['name'] }}</span>
            @endif
        </td>
        <td class="form-no">{{ $company['letter_form_no'] }}</td>
    </tr>
</table>

<div>{{ $company['city'] }}, {{ ($a->issued_at ?? now())->format('d-M-y') }}</div>
<div>No. {{ $a->ext_doc_no ?? '(assigned when issued)' }}</div>

<table class="meta" style="margin-top: 14px;">
    <tr>
        <td>To</td><td>:</td>
        <td class="to-box"><b>{{ $s->supplier_name }}</b>@if ($a->letter_address)<br>{!! nl2br(e($a->letter_address)) !!}@endif</td>
    </tr>
    @if ($a->letter_attention_name)
        <tr><td style="padding-top: 8px;">Attn.</td><td style="padding-top: 8px;">:</td>
            <td style="padding-top: 8px;"><u>{{ $a->letter_attention_name }}</u>@if ($a->letter_attention_title)<br>{{ $a->letter_attention_title }}@endif</td></tr>
    @endif
</table>

<div class="subject">Subject : Price Confirmation</div>

<p>Dear Sir,</p>
<p>With this letter, we would like to inform you the Price Confirmation for the listed/attached jobs as below :</p>
<p style="margin-left: 150px;"><b>attached</b> ({{ count($d->Details) }} item{{ count($d->Details) > 1 ? 's' : '' }})</p>

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
        <td class="space">@if ($director?->Status === 'SIGNED')Approved electronically<br>{{ $director->Date?->format('d-m-Y H:i') }}@endif</td>
        <td class="space"></td>
    </tr>
    <tr>
        <td><u>{{ $director?->Name ?? '..............................' }}</u><br>Director</td>
        <td><u>{{ $a->letter_attention_name ?? '..............................' }}</u><br>{{ $a->letter_attention_title ?? $s->supplier_name }}</td>
    </tr>
</table>

<table class="bottom">
    <tr>
        <td>
            <table class="meta">
                <tr><td>Cc.</td><td>:</td><td>- Finance &amp; Accounting Div-Head</td></tr>
                <tr><td>Refer to</td><td>:</td><td>{{ $a->doc_no }}</td></tr>
            </table>
        </td>
        <td class="r">{{ $company['letter_rev'] }}</td>
    </tr>
</table>

<div class="kop">
    <div class="name">{{ $company['name'] }}</div>
    <div class="addr">{{ $company['address'] }}</div>
</div>

<div class="page-break"></div>
<div class="att-title">Attachment Letter &nbsp;No. {{ $a->ext_doc_no ?? '(assigned when issued)' }}</div>
<table class="att">
    <thead>
        <tr>
            <th rowspan="2" style="width: 26px;">NO</th>
            <th rowspan="2" style="width: 70px;">ITEM CODE</th>
            <th rowspan="2">ITEM NAME</th>
            <th rowspan="2" style="width: 42px;">UNIT</th>
            @if ($showCurr)<th rowspan="2" style="width: 34px;">CURR</th>@endif
            <th colspan="2">PRICE/UNIT</th>
        </tr>
        <tr><th style="width: 78px;">CURRENT</th><th style="width: 78px;">NEW</th></tr>
    </thead>
    <tbody>
        @foreach ($d->Details as $l)
            <tr>
                <td class="c">{{ $l->LineNo }}</td>
                <td>{{ $l->ItemCode }}</td>
                <td>{{ $l->ItemName }}</td>
                <td class="c">{{ $l->Unit }}</td>
                @if ($showCurr)<td class="c">{{ $l->Currency }}</td>@endif
                <td class="r">{{ $price($l->CurrentPrice, $l->Currency) }}</td>
                <td class="r">{{ $price($l->NewPrice, $l->Currency) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
