<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Membuat file export OGS DUMMY (item master, price master, PO transaction) dengan format kolom
 * yang sama seperti export OGS asli. Dipakai untuk development & demo, supaya data asli tidak
 * pernah masuk repository/database dev. Hasil selalu sama (seed tetap).
 *
 * Sengaja menyisipkan beberapa cacat data seperti di OGS asli (item tanpa nama, plant code tidak
 * seragam, periode harga tumpang tindih, tanggal berisi jam) supaya warning import ikut teruji.
 * Price master tidak punya kolom ACTIVE (sama dengan export OGS): harga berlaku ditentukan dari Time Control.
 */
class MakeDummyOgs extends Command
{
    protected $signature = 'apr:dummy-ogs {--out=database/dummy : Output folder}';

    protected $description = 'Generate dummy OGS export files (item master, price master, PO transactions)';

    private const Suppliers = [
        ['1001', 'ABADI SENTOSA (DUMMY) PT.', 'IDR'],
        ['1002', 'CAHAYA TEKNIK (DUMMY) CV.', 'IDR'],
        ['1003', 'MANDIRI PRIMA (DUMMY) PT.', 'IDR'],
        ['1004', 'SEJAHTERA UTAMA (DUMMY) PT.', 'IDR'],
        ['1005', 'JAYA MAKMUR (DUMMY) PT.', 'IDR'],
        ['1006', 'PERKASA GEMILANG (DUMMY) UD.', 'IDR'],
        ['1007', 'SINAR ABADI (DUMMY) PT.', 'IDR'],
        ['1008', 'KARYA MANDIRI (DUMMY) CV.', 'IDR'],
        ['2001', 'NIPPON DUMMY TRADING CO. LTD.', 'JPY'],
        ['2002', 'GLOBAL DUMMY SUPPLY PTE. LTD.', 'USD'],
    ];

    /** [kategori, prefix kode, template nama, varian, unit, harga dasar IDR, qty per PO min-max] */
    private const Templates = [
        ['CONSUMABLE', '1003', 'ABRASIVE PAPER 9" X 11" #%s', ['80', '120', '240', '400', '600', '1000', '1200', '1500'], 'LBR', 2500, [50, 400]],
        ['CONSUMABLE', '1004', 'CUTTING WHEEL D%s X 2 X 16', ['100', '125', '150', '180', '355'], 'PCS', 9000, [20, 200]],
        ['CONSUMABLE', '1005', 'GRINDING WHEEL D%s X 6 X 16', ['100', '125', '150', '180'], 'PCS', 11000, [10, 100]],
        ['CONSUMABLE', '1006', 'MASKING TAPE %s"', ['0.5', '1', '1.5', '2'], 'ROLL', 6500, [20, 300]],
        ['CONSUMABLE', '1007', 'LAP CLOTH %s 40X40 CM', ['WHITE', 'BLUE', 'GREY'], 'PCS', 42000, [5, 60]],
        ['CONSUMABLE', '1008', 'SCOTCH PAD %s', ['GREEN', 'MAROON', 'GREY', 'WHITE'], 'PCS', 12500, [20, 200]],
        ['SAFETY GOODS', '1001', 'SARUNG TANGAN %s', ['KATUN B4', 'KATUN B5', 'KULIT', 'KARET HIJAU', 'NITRILE', 'ANTI CUT'], 'PSG', 15000, [24, 240]],
        ['SAFETY GOODS', '1002', 'SAFETY SHOES SIZE %s', ['38', '39', '40', '41', '42', '43', '44'], 'PSG', 385000, [2, 20]],
        ['SAFETY GOODS', '1009', 'WELDING MASK %s', ['STANDARD', 'AUTO DARK'], 'PCS', 650000, [1, 10]],
        ['SAFETY GOODS', '1010', 'EAR PLUG %s', ['FOAM', 'CORDED'], 'PSG', 3500, [100, 1000]],
        ['CUTTING TOOLS', '3001', 'DRILL BIT HSS D%s MM', ['3', '4', '5', '6', '8', '10', '12'], 'PCS', 28000, [5, 50]],
        ['CUTTING TOOLS', '3002', 'END MILL CARBIDE D%s MM', ['6', '8', '10', '12', '16'], 'PCS', 450000, [2, 20]],
        ['CUTTING TOOLS', '3003', 'TAP HSS M%s', ['6', '8', '10', '12'], 'PCS', 95000, [2, 30]],
        ['CUTTING TOOLS', '3004', 'INSERT TIP %s', ['CNMG120408', 'WNMG080408', 'TNMG160404', 'DNMG150608'], 'PCS', 120000, [10, 100]],
        ['SUBMATERIAL A', '2201', 'WELDING WIRE ER70S-6 D%s MM', ['0.8', '1.0', '1.2'], 'KG', 32000, [50, 500]],
        ['SUBMATERIAL A', '2202', 'CABLE TIE %s MM', ['100', '150', '200', '300'], 'PACK', 18000, [10, 100]],
        ['SUBMATERIAL B', '2203', 'COMPOUND POLISH %s', ['FINE', 'MEDIUM', 'COARSE'], 'BOTOL', 175000, [2, 20]],
        ['SUBMATERIAL B', '2204', 'SEALANT %s', ['SILICONE CLEAR', 'SILICONE BLACK', 'GASKET MAKER'], 'TUBE', 65000, [5, 50]],
        ['PAINT', '2701', 'PAINT THINNER %s', ['A SPECIAL', 'NC', 'PU'], 'LITER', 38000, [20, 200]],
        ['PAINT', '2702', 'PRIMER SURFACER %s', ['GREY', 'WHITE', 'BLACK'], 'KG', 87000, [10, 100]],
        ['FUEL AND OIL', '2801', 'HYDRAULIC OIL ISO VG %s', ['32', '46', '68'], 'LITER', 42000, [100, 800]],
        ['FUEL AND OIL', '2802', 'GREASE %s', ['EP2', 'EP3', 'MOLY'], 'KG', 56000, [10, 80]],
        ['TOOLING', '4001', 'HEX KEY SET %s', ['METRIC', 'INCH'], 'SET', 145000, [1, 10]],
        ['TOOLING', '4002', 'TORQUE WRENCH %s NM', ['5-25', '20-100', '40-200'], 'PCS', 1750000, [1, 3]],
    ];

    private const ItemHeader = ['ACTION', 'NO.', 'ITEM CODE', 'ITEM NAME', 'UNIT', 'CATEGORY', 'MINUMUM ORDER', 'TOOL NUMBER', 'DRAWING NUMBER', 'TC FROM', 'TC TO', 'REMARK'];
    private const PriceHeader = ['ACTION', 'NO.', 'ITEM CODE', 'ITEM NAME', 'UNIT', 'SUPPLIER PLANT CODE', 'SUPPLIER CODE', 'SUPPLIER NAME', 'CURRENCY', 'PRICE', 'QUOTATION', 'TIME CONTROL FROM', 'TIME CONTROL TO', 'INPUT BY', 'INPUT DATE', 'UPDATE BY', 'UPDATE DATE'];
    private const PoHeader = ['PO NO', 'PO LINE', 'PO DATE', 'ITEM CODE', 'ITEM NAME', 'UNIT', 'SUPPLIER CODE', 'SUPPLIER NAME', 'CURRENCY', 'QTY', 'UNIT PRICE'];

    private const Rates = ['IDR' => 1, 'USD' => 16500, 'JPY' => 110];

    public function handle(): int
    {
        mt_srand(20261019);

        $out = base_path($this->option('out'));
        if (!is_dir($out)) {
            mkdir($out, 0775, true);
        }

        [$items, $itemRows] = $this->buildItems();
        [$pairs, $priceRows] = $this->buildPrices($items);
        $poRows = $this->buildPos($items, $pairs);

        $this->write("{$out}/ogs_itemmaster_dummy.xlsx", self::ItemHeader, $itemRows);
        $this->write("{$out}/ogs_pricemaster_dummy.xlsx", self::PriceHeader, $priceRows);
        $this->write("{$out}/ogs_potransaction_dummy.xlsx", self::PoHeader, $poRows);

        $this->info(sprintf('Created %d items, %d price rows, %d PO lines in %s', count($itemRows), count($priceRows), count($poRows), $out));

        return self::SUCCESS;
    }

    private function buildItems(): array
    {
        $items = [];
        $rows = [];
        $tcFrom = $this->excelDate('2012-01-01');
        $tcTo = $this->excelDate('2089-12-31');

        foreach (self::Templates as [$category, $prefix, $pattern, $variants, $unit, $basePrice, $qtyRange]) {
            foreach ($variants as $i => $variant) {
                $code = $prefix . str_pad((string) ($i + 1) * 10, 4, '0', STR_PAD_LEFT);
                $items[$code] = [
                    'name' => sprintf($pattern, $variant),
                    'unit' => $unit,
                    'category' => $category,
                    // Variasi harga per varian supaya tidak semua sama.
                    'base' => round($basePrice * (0.8 + 0.08 * $i) * (0.95 + mt_rand(0, 10) / 100), -1),
                    'qty' => $qtyRange,
                ];
                $rows[] = [null, null, $code, $items[$code]['name'], $unit, $category, '1', '', '', $tcFrom, $tcTo, ''];
            }
        }

        // Cacat data seperti di OGS asli: item tanpa nama.
        $rows[] = [null, null, '49990010', '', 'PCS', 'TOOLING', '1', '', '', $tcFrom, $tcTo, ''];

        return [$items, $rows];
    }

    private function buildPrices(array $items): array
    {
        $pairs = [];
        $rows = [];
        $localSuppliers = array_slice(self::Suppliers, 0, 8);
        $users = ['staff.dummy1', 'staff.dummy2', 'System'];

        foreach ($items as $code => $item) {
            // Supplier utama (dipilih berdasarkan kategori supaya satu supplier punya banyak item), kadang ada supplier kedua.
            $main = $localSuppliers[crc32($item['category']) % count($localSuppliers)];
            $suppliers = [$main];
            if (mt_rand(1, 100) <= 25) {
                $suppliers[] = $localSuppliers[mt_rand(0, count($localSuppliers) - 1)];
            }
            if ($item['category'] === 'CUTTING TOOLS' && mt_rand(1, 100) <= 40) {
                $suppliers[] = self::Suppliers[mt_rand(8, 9)];
            }

            foreach (array_unique($suppliers, SORT_REGULAR) as [$supCode, $supName, $currency]) {
                $current = $this->convert($item['base'] * (0.97 + mt_rand(0, 8) / 100), $currency);
                $previous = $this->convert($item['base'] * (0.90 + mt_rand(0, 6) / 100), $currency);
                $plant = $this->plantCode();

                // Harga lama periode Okt 2025-Mar 2026 (sudah lewat).
                $rows[] = $this->priceRow($code, $item, $plant, $supCode, $supName, $currency, $previous, 'PC OCT-MAR26',
                    '2025-10-01', '2026-03-31', $users[mt_rand(0, 2)], '2025-09-20 09:15');
                // Harga current periode Apr 2026-Mar 2027. Sebagian pasangan sengaja tidak diperpanjang di OGS
                // (hanya punya harga lama) supaya tanda "expired" di APR ikut teruji.
                $renewed = mt_rand(1, 100) > 12;
                if ($renewed) {
                    $rows[] = $this->priceRow($code, $item, $plant, $supCode, $supName, $currency, $current, 'PC APR26-MAR27',
                        '2026-04-01', '2027-03-31', $users[mt_rand(0, 2)], '2026-03-25 10:30');
                }

                $pairs[] = ['code' => $code, 'supplier' => [$supCode, $supName, $currency], 'price' => $renewed ? $current : $previous, 'previous' => $previous];
            }
        }

        // Cacat data: satu pasangan item/supplier punya dua harga dengan periode sama (tumpang tindih),
        // dan satu tanggal Time Control hanya berisi jam (baris itu dilewati saat import).
        $dup = $rows[3];
        $dup[9] = round($dup[9] * 1.02, 2);
        $rows[] = $dup;
        $rows[5][12] = 0.4375;

        return [$pairs, $rows];
    }

    private function buildPos(array $items, array $pairs): array
    {
        $rows = [];
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2026-09-30');

        // Purchase period default APR (6 bulan penuh terakhir): setiap pasangan item/supplier yang punya harga
        // minimal punya 2 PO di sini, supaya Qty di APR tidak 0.
        $recentFrom = Carbon::parse('2026-04-01');
        $minRecentPos = 2;

        foreach ($pairs as $pair) {
            $item = $items[$pair['code']];
            [$supCode, $supName, $currency] = $pair['supplier'];

            $months = [];
            for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
                if (mt_rand(1, 100) <= 45) {
                    $months[] = $month->copy();
                }
            }
            $recentMonths = array_filter($months, fn (Carbon $m) => $m->gte($recentFrom));
            while (count($recentMonths) < $minRecentPos) {
                $m = $recentFrom->copy()->addMonths(mt_rand(0, 5));
                if (!in_array($m->format('Y-m'), array_map(fn (Carbon $x) => $x->format('Y-m'), $months), true)) {
                    $months[] = $recentMonths[] = $m;
                }
            }
            usort($months, fn (Carbon $a, Carbon $b) => $a <=> $b);

            foreach ($months as $month) {
                $date = $month->copy()->day(mt_rand(1, 28));
                // Harga PO mengikuti periode harga: sebelum Apr 2026 pakai harga lama, sesudahnya harga current,
                // dengan sedikit variasi (diskon/negosiasi per PO).
                $base = $date->lt(Carbon::parse('2026-04-01')) ? $pair['previous'] : $pair['price'];
                $price = $currency === 'IDR' ? round($base * (0.98 + mt_rand(0, 4) / 100), -1) : round($base * (0.98 + mt_rand(0, 4) / 100), 2);
                $qty = mt_rand($item['qty'][0], $item['qty'][1]);

                $rows[] = [$date, $pair['code'], $item['name'], $item['unit'], $supCode, $supName, $currency, $qty, $price];
            }
        }

        // Satu PO per supplier per bulan (beberapa line), nomor PO berurutan menurut tanggal.
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row[4] . '|' . $row[0]->format('Y-m')][] = $row;
        }
        uasort($groups, fn ($a, $b) => min(array_map(fn ($r) => $r[0], $a)) <=> min(array_map(fn ($r) => $r[0], $b)));

        $result = [];
        $seqPerYear = [];
        foreach ($groups as $lines) {
            $poDate = min(array_map(fn ($r) => $r[0], $lines));
            $year = $poDate->format('y');
            $seqPerYear[$year] = ($seqPerYear[$year] ?? 0) + 1;
            $poNo = sprintf('PO%s-%05d', $year, $seqPerYear[$year]);
            foreach (array_values($lines) as $i => [, $code, $name, $unit, $supCode, $supName, $currency, $qty, $price]) {
                $result[] = [$poNo, $i + 1, $this->excelDate($poDate->toDateString()), $code, $name, $unit, $supCode, $supName, $currency, $qty, $price];
            }
        }

        return $result;
    }

    private function priceRow(string $code, array $item, string $plant, string $supCode, string $supName, string $currency, float $price,
        string $quotation, string $from, string $to, string $user, string $inputDate): array
    {
        return [null, null, $code, $item['name'], $item['unit'], $plant, $supCode, $supName, $currency, $price, $quotation,
            $this->excelDate($from), $this->excelDate($to), $user, $this->excelDate($inputDate), $user, $this->excelDate($inputDate)];
    }

    private function convert(float $idr, string $currency): float
    {
        return $currency === 'IDR' ? round($idr, -1) : round($idr / self::Rates[$currency], 2);
    }

    /** Plant code OGS tidak seragam: kebanyakan '01', sebagian '1' atau 'I'. */
    private function plantCode(): string
    {
        $r = mt_rand(1, 100);

        return $r <= 85 ? '01' : ($r <= 93 ? '1' : 'I');
    }

    private function excelDate(string $date): float
    {
        return ExcelDate::PHPToExcel(Carbon::parse($date));
    }

    private function write(string $path, array $header, array $rows): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header, null, 'A1');
        // Kode supplier & item harus tetap teks (leading zero), jadi tulis sel satu per satu sebagai string untuk kolom kode.
        $textColumns = array_keys(array_intersect($header, ['ITEM CODE', 'SUPPLIER CODE', 'SUPPLIER PLANT CODE', 'PO NO']));
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $cell = $sheet->getCell([$c + 1, $r + 2]);
                if (in_array($c, $textColumns, true)) {
                    $cell->setValueExplicit((string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $cell->setValue($value);
                }
            }
        }
        (new Xlsx($spreadsheet))->save($path);
    }
}
