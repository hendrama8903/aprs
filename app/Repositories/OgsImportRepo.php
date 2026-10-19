<?php

namespace App\Repositories;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\OgsImport;
use App\Models\Supplier;
use App\Support\DBResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import file export OGS (item master & price master) ke APR.
 * Data hanya mengalir satu arah OGS -> APR; APR tidak pernah menulis balik ke OGS.
 *
 * - ITEM : upsert per item_code. Item yang tidak ada di file tidak dihapus.
 * - PRICE: snapshot lengkap, tabel item_prices diganti penuh. Supplier di-upsert dari file yang sama.
 * Kolom dicari berdasarkan nama header (bukan posisi), jadi urutan kolom export OGS boleh berubah.
 * Temuan kualitas data dicatat sebagai warning, tidak menggagalkan import.
 */
class OgsImportRepo
{
    private const MaxWarnings = 300;

    private const ItemColumns = [
        'ITEM CODE' => true, 'ITEM NAME' => true, 'UNIT' => true, 'CATEGORY' => true,
        'MINUMUM ORDER' => false, 'TOOL NUMBER' => false, 'DRAWING NUMBER' => false,
        'TC FROM' => false, 'TC TO' => false, 'REMARK' => false,
    ];

    private const PriceColumns = [
        'ITEM CODE' => true, 'ITEM NAME' => true, 'UNIT' => false,
        'SUPPLIER PLANT CODE' => false, 'SUPPLIER CODE' => true, 'SUPPLIER NAME' => true,
        'CURRENCY' => true, 'PRICE' => true, 'QUOTATION' => false,
        'TIME CONTROL FROM' => false, 'TIME CONTROL TO' => false,
        'INPUT BY' => false, 'INPUT DATE' => false, 'UPDATE BY' => false, 'UPDATE DATE' => false,
        'ACTIVE' => true,
    ];

    /** @var string[] */
    private array $warnings = [];
    private int $warningCount = 0;

    /** @return object[] */
    public function search(?string $importType): array
    {
        return OgsImport::query()
            ->when($importType, fn ($q) => $q->where('import_type', $importType))
            ->orderByDesc('import_id')
            ->limit(500)
            ->get()
            ->map(fn (OgsImport $i) => $this->mapImport($i, false))
            ->all();
    }

    public function getByKey(int $importId): ?object
    {
        $import = OgsImport::query()->find($importId);

        return $import === null ? null : $this->mapImport($import, true);
    }

    /** Waktu import terakhir per jenis, untuk ditampilkan di dashboard / layar import. */
    public function lastImports(): array
    {
        $result = [];
        foreach ([OgsImport::TypeItem, OgsImport::TypePrice] as $type) {
            $last = OgsImport::query()->where('import_type', $type)->orderByDesc('import_id')->first();
            $result[$type] = $last === null ? null : $this->mapImport($last, false);
        }

        return $result;
    }

    public function import(string $importType, string $filePath, string $fileName, string $loginUser): DBResult
    {
        $this->warnings = [];
        $this->warningCount = 0;

        $rows = $this->readSheet($filePath);
        if ($rows === []) {
            return new DBResult(false, 'The file is empty.');
        }

        $columns = $importType === OgsImport::TypeItem ? self::ItemColumns : self::PriceColumns;
        $header = array_shift($rows);
        $map = $this->mapHeader($header, $columns);
        if (is_string($map)) {
            return new DBResult(false, $map);
        }

        // Baris kosong di akhir export OGS dibuang.
        $rows = array_values(array_filter($rows, fn (array $r) => trim((string) ($r[$map['ITEM CODE']] ?? '')) !== ''));
        if ($rows === []) {
            return new DBResult(false, 'No data rows found in the file.');
        }

        return DB::transaction(function () use ($importType, $rows, $map, $fileName, $loginUser) {
            $import = OgsImport::create([
                'import_type' => $importType,
                'file_name' => mb_substr($fileName, 0, 200),
                'total_rows' => count($rows),
                'created_by' => $loginUser,
            ]);

            $counts = $importType === OgsImport::TypeItem
                ? $this->importItems($rows, $map, $import, $loginUser)
                : $this->importPrices($rows, $map, $import, $loginUser);

            $import->update($counts + [
                'warning_count' => $this->warningCount,
                'warnings' => $this->warnings,
            ]);

            $message = sprintf(
                '%s import finished: %d rows, %d inserted, %d updated, %d skipped, %d warning(s).',
                $importType === OgsImport::TypeItem ? 'Item master' : 'Price master',
                count($rows), $counts['inserted_rows'], $counts['updated_rows'], $counts['skipped_rows'], $this->warningCount,
            );

            return new DBResult(true, $message, $import->import_id);
        });
    }

    private function importItems(array $rows, array $map, OgsImport $import, string $loginUser): array
    {
        $existing = Item::query()->pluck('item_id', 'item_code')->all();
        $seen = [];
        $records = [];
        $skipped = 0;
        $now = now();

        foreach ($rows as $index => $r) {
            $line = $index + 2;
            $code = $this->text($r, $map, 'ITEM CODE');
            $name = $this->text($r, $map, 'ITEM NAME');

            if ($name === '') {
                $this->warn("Row {$line}: item {$code} has no item name, skipped.");
                $skipped++;
                continue;
            }
            if (isset($seen[$code])) {
                $this->warn("Row {$line}: item code {$code} appears more than once, last row is used.");
            }
            $seen[$code] = true;

            $records[$code] = [
                'item_code' => mb_substr($code, 0, 20),
                'item_name' => mb_substr($name, 0, 150),
                'unit' => $this->nullable($this->text($r, $map, 'UNIT'), 20),
                'category' => $this->nullable($this->text($r, $map, 'CATEGORY'), 50),
                'minimum_order' => $this->nullable($this->text($r, $map, 'MINUMUM ORDER'), 20),
                'tool_number' => $this->nullable($this->text($r, $map, 'TOOL NUMBER'), 100),
                'drawing_number' => $this->nullable($this->text($r, $map, 'DRAWING NUMBER'), 100),
                'tc_from' => $this->date($r, $map, 'TC FROM', $line)?->toDateString(),
                'tc_to' => $this->date($r, $map, 'TC TO', $line)?->toDateString(),
                'remark' => $this->nullable($this->text($r, $map, 'REMARK'), 255),
                'last_import_id' => $import->import_id,
                'created_by' => $loginUser,
                'created_at' => $now,
                'updated_by' => $loginUser,
                'updated_at' => $now,
            ];
        }

        $noCategory = count(array_filter($records, fn ($rec) => $rec['category'] === null));
        if ($noCategory > 0) {
            $this->warn("{$noCategory} item(s) have no category.");
        }

        foreach (array_chunk(array_values($records), 500) as $chunk) {
            // created_* tidak ikut di-update supaya jejak import pertama tetap ada.
            Item::query()->upsert($chunk, ['item_code'], [
                'item_name', 'unit', 'category', 'minimum_order', 'tool_number', 'drawing_number',
                'tc_from', 'tc_to', 'remark', 'last_import_id', 'updated_by', 'updated_at',
            ]);
        }

        $updated = count(array_intersect_key($records, $existing));

        return [
            'inserted_rows' => count($records) - $updated,
            'updated_rows' => $updated,
            'skipped_rows' => $skipped,
        ];
    }

    private function importPrices(array $rows, array $map, OgsImport $import, string $loginUser): array
    {
        $now = now();
        $skipped = 0;

        // 1. Supplier: upsert kode + nama. Alamat/attention yang diisi di APR tidak tersentuh.
        $supplierNames = [];
        foreach ($rows as $r) {
            $code = $this->text($r, $map, 'SUPPLIER CODE');
            if ($code !== '') {
                $supplierNames[$code][$this->text($r, $map, 'SUPPLIER NAME')] = true;
            }
        }
        foreach ($supplierNames as $code => $names) {
            if (count($names) > 1) {
                $this->warn("Supplier {$code} has more than one name in the file: " . implode(' / ', array_keys($names)) . '. The last one is used.');
            }
        }
        $supplierRecords = [];
        foreach ($supplierNames as $code => $names) {
            $supplierRecords[] = [
                'supplier_code' => mb_substr((string) $code, 0, 10),
                'supplier_name' => mb_substr((string) array_key_last($names), 0, 100),
                'created_by' => $loginUser,
                'created_at' => $now,
                'updated_by' => $loginUser,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($supplierRecords, 500) as $chunk) {
            Supplier::query()->upsert($chunk, ['supplier_code'], ['supplier_name', 'updated_by', 'updated_at']);
        }

        $supplierIds = Supplier::query()->pluck('supplier_id', 'supplier_code')->all();
        $itemIds = Item::query()->pluck('item_id', 'item_code')->all();

        // 2. Price: snapshot penuh menggantikan data lama.
        ItemPrice::query()->delete();

        $records = [];
        $missingItems = [];
        $badPlants = [];
        $activeKeys = [];

        foreach ($rows as $index => $r) {
            $line = $index + 2;
            $itemCode = $this->text($r, $map, 'ITEM CODE');
            $supplierCode = $this->text($r, $map, 'SUPPLIER CODE');
            $priceRaw = $r[$map['PRICE']] ?? null;

            if ($supplierCode === '' || !isset($supplierIds[$supplierCode])) {
                $this->warn("Row {$line}: item {$itemCode} has no supplier code, skipped.");
                $skipped++;
                continue;
            }
            if (!is_numeric($priceRaw)) {
                $this->warn("Row {$line}: item {$itemCode} / supplier {$supplierCode} has an invalid price '{$priceRaw}', skipped.");
                $skipped++;
                continue;
            }

            if (!isset($itemIds[$itemCode])) {
                $missingItems[$itemCode] = true;
            }

            $plant = $this->text($r, $map, 'SUPPLIER PLANT CODE');
            if ($plant !== '' && !preg_match('/^\d{2}$/', $plant)) {
                $badPlants[$plant] = ($badPlants[$plant] ?? 0) + 1;
            }

            $isActive = $this->bool($r[$map['ACTIVE']] ?? null);
            if ($isActive) {
                $activeKeys[$itemCode . '|' . $supplierCode][] = $line;
            }

            $records[] = [
                'item_code' => mb_substr($itemCode, 0, 20),
                'item_name' => mb_substr($this->text($r, $map, 'ITEM NAME'), 0, 150),
                'unit' => $this->nullable($this->text($r, $map, 'UNIT'), 20),
                'item_id' => $itemIds[$itemCode] ?? null,
                'supplier_id' => $supplierIds[$supplierCode],
                'supplier_plant_code' => $this->nullable($plant, 5),
                'currency' => mb_strtoupper(mb_substr($this->text($r, $map, 'CURRENCY'), 0, 3)),
                'price' => (float) $priceRaw,
                'quotation' => $this->nullable($this->text($r, $map, 'QUOTATION'), 50),
                'tc_from' => $this->date($r, $map, 'TIME CONTROL FROM', $line)?->toDateString(),
                'tc_to' => $this->date($r, $map, 'TIME CONTROL TO', $line)?->toDateString(),
                'ogs_input_by' => $this->nullable($this->text($r, $map, 'INPUT BY'), 50),
                'ogs_input_date' => $this->date($r, $map, 'INPUT DATE', $line, true)?->toDateTimeString(),
                'ogs_update_by' => $this->nullable($this->text($r, $map, 'UPDATE BY'), 50),
                'ogs_update_date' => $this->date($r, $map, 'UPDATE DATE', $line, true)?->toDateTimeString(),
                'is_active' => $isActive,
                'import_id' => $import->import_id,
                'created_at' => $now,
            ];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            ItemPrice::query()->insert($chunk);
        }

        if ($missingItems !== []) {
            $codes = array_keys($missingItems);
            $this->warn(count($codes) . ' item code(s) in price master are not in item master: ' . implode(', ', array_slice($codes, 0, 50)) . (count($codes) > 50 ? ', ...' : ''));
        }
        if ($badPlants !== []) {
            $this->warn('Non-standard supplier plant code (expected 2 digits): ' . implode(', ', array_map(fn ($v, $n) => "'{$v}' ({$n} rows)", array_keys($badPlants), $badPlants)));
        }
        $duplicates = array_filter($activeKeys, fn ($lines) => count($lines) > 1);
        if ($duplicates !== []) {
            $this->warn(count($duplicates) . ' item/supplier pair(s) have more than one ACTIVE price. Examples: ' . implode('; ', array_map(
                fn ($key, $lines) => str_replace('|', ' / ', $key) . ' (rows ' . implode(', ', $lines) . ')',
                array_slice(array_keys($duplicates), 0, 10),
                array_slice($duplicates, 0, 10),
            )));
        }

        return [
            'inserted_rows' => count($records),
            'updated_rows' => 0,
            'skipped_rows' => $skipped,
        ];
    }

    // ---------- Helper baca sheet ----------

    private function readSheet(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        return $reader->load($filePath)->getActiveSheet()->toArray(null, false, false, false);
    }

    /** @return array<string,int>|string peta nama header -> index kolom, atau pesan error. */
    private function mapHeader(array $header, array $columns): array|string
    {
        $index = [];
        foreach ($header as $i => $name) {
            $key = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $name)));
            if ($key !== '' && !isset($index[$key])) {
                $index[$key] = $i;
            }
        }

        $missing = array_keys(array_filter($columns, fn ($required, $name) => $required && !isset($index[$name]), ARRAY_FILTER_USE_BOTH));
        if ($missing !== []) {
            return 'Wrong file format. Missing column(s): ' . implode(', ', $missing) . '. Make sure you upload the right OGS export.';
        }

        return array_intersect_key($index, $columns);
    }

    private function text(array $row, array $map, string $column): string
    {
        if (!isset($map[$column])) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', (string) ($row[$map[$column]] ?? '')));
    }

    private function nullable(string $value, int $maxLength): ?string
    {
        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtoupper(trim((string) $value)), ['TRUE', '1', 'Y', 'YES'], true);
    }

    /** Tanggal OGS berupa serial Excel; nilai < 1 hanya berisi jam dan dianggap tidak valid. */
    private function date(array $row, array $map, string $column, int $line, bool $withTime = false): ?Carbon
    {
        if (!isset($map[$column])) {
            return null;
        }

        $value = $row[$map[$column]] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                if ((float) $value < 1) {
                    $this->warn("Row {$line}: {$column} only contains a time value ('{$value}'), saved as empty.");

                    return null;
                }
                $date = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } else {
                $date = Carbon::parse((string) $value);
            }
        } catch (\Throwable) {
            $this->warn("Row {$line}: {$column} '{$value}' is not a valid date, saved as empty.");

            return null;
        }

        return $withTime ? $date : $date->startOfDay();
    }

    private function warn(string $message): void
    {
        $this->warningCount++;
        if (count($this->warnings) < self::MaxWarnings) {
            $this->warnings[] = $message;
        }
    }

    private function mapImport(OgsImport $i, bool $withWarnings): object
    {
        $data = [
            'ImportId' => $i->import_id,
            'ImportType' => $i->import_type,
            'FileName' => $i->file_name,
            'TotalRows' => $i->total_rows,
            'InsertedRows' => $i->inserted_rows,
            'UpdatedRows' => $i->updated_rows,
            'SkippedRows' => $i->skipped_rows,
            'WarningCount' => $i->warning_count,
            'CreatedBy' => $i->created_by,
            'CreatedDate' => $i->created_at,
        ];
        if ($withWarnings) {
            $data['Warnings'] = $i->warnings ?? [];
        }

        return (object) $data;
    }
}
