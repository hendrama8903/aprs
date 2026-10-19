<?php

/*
| Pengaturan aplikasi APR (Approval Price Confirmation).
| Identitas perusahaan dicetak di kop form APR dan surat Price Confirmation ke supplier.
*/

return [
    'company' => [
        'name' => env('APR_COMPANY_NAME', 'PT. CONTOH MANUFAKTUR INDONESIA'),
        'address' => env('APR_COMPANY_ADDRESS', 'Kawasan Industri Contoh Blok A No. 1, Purwakarta 41181, Jawa Barat - Indonesia'),
        'city' => env('APR_COMPANY_CITY', 'Purwakarta'),
        'department' => env('APR_COMPANY_DEPARTMENT', 'PUD'),
    ],

    // Pemilik aplikasi di footer halaman login: "© {tahun} {login_footer}. All rights reserved."
    'login_footer' => env('APR_LOGIN_FOOTER', 'General Affairs Department'),

    // Kenaikan harga (%) di atas nilai ini wajib diberi remark.
    'remark_threshold_pct' => (float) env('APR_REMARK_THRESHOLD_PCT', 5),

    // Format nomor dokumen: {no} nomor urut per tahun, {dept}, {roman} bulan romawi, {yy} tahun 2 digit.
    'doc_no_format' => [
        'internal' => env('APR_DOC_NO_INTERNAL', '{no}/PC-INT/{dept}/{roman}/{yy}'),
        'external' => env('APR_DOC_NO_EXTERNAL', '{no}/PC-EXT/{dept}/{roman}/{yy}'),
    ],

    // Isi awal form APR baru.
    'defaults' => [
        'part_category' => 'CONSUMABLE',
        'part_status' => 'Non Component Part',
        'model' => 'ALL MODEL',
        'background' => 'Annual Price Review for Non Component Part',
        'letter_conditions' => "Exclude VAT 12%\nThe price include packaging & transportation",
    ],
];
