<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

/**
 * Kurs dummy USD & JPY ke IDR, mengikuti periode price master dummy, ditambah satu periode lampau
 * dan satu periode berikutnya (FY2027, untuk kurs "new" di APR). Aman dijalankan berulang.
 *
 *   php artisan db:seed --class=DummyExchangeRateSeeder
 */
class DummyExchangeRateSeeder extends Seeder
{
    /** [currency, rate, valid_from, valid_to, remark] */
    private const Rates = [
        ['USD', 16100, '2025-04-01', '2025-09-30', 'Dummy rate H1 FY2025'],
        ['USD', 16300, '2025-10-01', '2026-03-31', 'Dummy rate H2 FY2025'],
        ['USD', 16500, '2026-04-01', '2027-03-31', 'Dummy rate FY2026'],
        ['USD', 16750, '2027-04-01', '2028-03-31', 'Dummy budget rate FY2027'],
        ['JPY', 106.5, '2025-04-01', '2025-09-30', 'Dummy rate H1 FY2025'],
        ['JPY', 108, '2025-10-01', '2026-03-31', 'Dummy rate H2 FY2025'],
        ['JPY', 110, '2026-04-01', '2027-03-31', 'Dummy rate FY2026'],
        ['JPY', 111.5, '2027-04-01', '2028-03-31', 'Dummy budget rate FY2027'],
    ];

    public function run(): void
    {
        foreach (self::Rates as [$currency, $rate, $from, $to, $remark]) {
            ExchangeRate::firstOrCreate(['currency' => $currency, 'valid_from' => $from], [
                'rate' => $rate, 'valid_to' => $to, 'remark' => $remark, 'created_by' => 'system',
            ]);
        }
    }
}
