<?php

namespace App\Repositories;

use App\Models\ItemPrice;
use App\Models\NewPrice;
use App\Models\Supplier;
use App\Support\DBResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Set New Price: lembar kerja harga baru per item + supplier untuk satu periode harga baru.
 *
 * - Current price = harga di price master yang Time Control-nya berlaku di AWAL periode (tanggal "from").
 *   Kalau tumpang tindih: TC From paling baru, lalu update OGS paling baru.
 * - Satu baris new_prices = item + supplier + periode; current price disalin saat disimpan.
 * - Item + supplier yang sama tidak boleh punya new price di periode lain yang tumpang tindih.
 */
class NewPriceRepo
{
    private const MaxErrors = 20;

    /** @return object[] */
    public function worksheet(string $from, string $to, ?int $supplierId, ?string $category): array
    {
        $valid = $this->validPrices($from, $supplierId, $category);
        $saved = NewPrice::query()
            ->with('supplier:supplier_id,supplier_code,supplier_name')
            ->where('new_tc_from', $from)->where('new_tc_to', $to)
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->get()
            ->keyBy(fn (NewPrice $n) => $n->item_code . '|' . $n->supplier_id);
        $categories = DB::table('items')->whereNotNull('category')->pluck('category', 'item_code')->all();

        $rows = [];
        foreach ($valid as $key => $p) {
            $rows[$key] = $this->row($p, $saved[$key] ?? null, $categories[$p->item_code] ?? null);
        }
        // New price tersimpan yang current price-nya sudah tidak Valid lagi (mis. price master di-upload ulang):
        // tetap ditampilkan supaya bisa dicek / dihapus.
        foreach ($saved as $key => $n) {
            $rows[$key] ??= $this->row(null, $n, $categories[$n->item_code] ?? null);
        }
        if ($category !== null && $category !== '') {
            $rows = array_filter($rows, fn ($r) => $r->Category === $category);
        }
        ksort($rows);

        return array_values($rows);
    }

    /**
     * Simpan perubahan lembar kerja (semua atau tidak sama sekali).
     * Baris dengan NewPrice kosong = hapus new price tersimpan untuk periode ini.
     *
     * @param array $rows [{ItemCode, SupplierId, NewPrice, Remark}]
     */
    public function save(string $from, string $to, array $rows, string $loginUser): DBResult
    {
        if ($error = $this->validatePeriod($from, $to)) {
            return new DBResult(false, $error);
        }
        if ($rows === []) {
            return new DBResult(false, 'No changes to save.');
        }

        $valid = $this->validPrices($from, null, null);
        $errors = [];
        $saved = $deleted = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                $code = trim((string) ($r['ItemCode'] ?? ''));
                $supplierId = (int) ($r['SupplierId'] ?? 0);
                $result = $this->apply($from, $to, $code, $supplierId, $r['NewPrice'] ?? null, $r['Remark'] ?? null, $valid, $loginUser);
                if (is_string($result)) {
                    $errors[] = "{$code}: {$result}";
                } elseif ($result === 1) {
                    $saved++;
                } elseif ($result === -1) {
                    $deleted++;
                }
            }
            if ($errors !== []) {
                DB::rollBack();

                return new DBResult(false, "Nothing was saved. Please fix:\n" . implode("\n", array_slice($errors, 0, self::MaxErrors))
                    . (count($errors) > self::MaxErrors ? "\n... and " . (count($errors) - self::MaxErrors) . ' more.' : ''));
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return new DBResult(true, "{$saved} new price(s) saved" . ($deleted ? ", {$deleted} cleared" : '') . '.');
    }

    /**
     * Upload file Excel (biasanya hasil Export Excel menu ini): kolom ITEM CODE, SUPPLIER CODE, NEW PRICE (+ REMARK).
     * Baris dengan NEW PRICE kosong dilewati (tidak menghapus). Baris yang tidak valid dilewati dan dilaporkan.
     */
    public function upload(string $from, string $to, string $filePath, string $loginUser): DBResult
    {
        if ($error = $this->validatePeriod($from, $to)) {
            return new DBResult(false, $error);
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($filePath)->getActiveSheet()->toArray(null, false, false, false);
        $header = array_map(fn ($h) => mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $h))), array_shift($sheet) ?? []);
        $col = array_flip(array_reverse($header, true));
        $missing = array_diff(['ITEM CODE', 'SUPPLIER CODE', 'NEW PRICE'], array_keys($col));
        if ($missing !== []) {
            return new DBResult(false, 'Wrong file format. Missing column(s): ' . implode(', ', $missing) . '. Use the file from Export Excel of this menu.');
        }

        $supplierIds = Supplier::query()->pluck('supplier_id', 'supplier_code')->all();
        $valid = $this->validPrices($from, null, null);
        $cell = fn (array $row, string $name) => isset($col[$name]) ? trim((string) ($row[$col[$name]] ?? '')) : '';
        $warnings = [];
        $saved = $skipped = 0;

        DB::transaction(function () use ($sheet, $cell, $supplierIds, $valid, $from, $to, $loginUser, &$warnings, &$saved, &$skipped) {
            foreach ($sheet as $i => $row) {
                $line = $i + 2;
                $code = $cell($row, 'ITEM CODE');
                $newPrice = $cell($row, 'NEW PRICE');
                if ($code === '' || $newPrice === '') {
                    continue;
                }
                $supplierCode = $cell($row, 'SUPPLIER CODE');
                $supplierId = $supplierIds[$supplierCode] ?? null;
                $result = $supplierId === null
                    ? "supplier {$supplierCode} not found"
                    : $this->apply($from, $to, $code, $supplierId, $newPrice, $cell($row, 'REMARK'), $valid, $loginUser);
                if (is_string($result)) {
                    $warnings[] = "Row {$line} ({$code} / {$supplierCode}): {$result}";
                    $skipped++;
                } elseif ($result === 1) {
                    $saved++;
                }
            }
        });

        $message = "Upload finished: {$saved} new price(s) saved, {$skipped} row(s) skipped.";
        if ($warnings !== []) {
            $message .= "\n" . implode("\n", array_slice($warnings, 0, self::MaxErrors))
                . (count($warnings) > self::MaxErrors ? "\n... and " . (count($warnings) - self::MaxErrors) . ' more.' : '');
        }

        return new DBResult($saved > 0 || $skipped === 0, $message);
    }

    /**
     * Simpan / hapus satu new price. Mengembalikan pesan error (string), 1 = disimpan, -1 = dihapus, 0 = tidak ada perubahan.
     *
     * @param array<string,ItemPrice> $valid
     */
    private function apply(string $from, string $to, string $code, int $supplierId, mixed $newPrice, ?string $remark, array $valid, string $loginUser): string|int
    {
        $key = $code . '|' . $supplierId;
        $existing = NewPrice::query()->where('item_code', $code)->where('supplier_id', $supplierId)->where('new_tc_from', $from)->first();

        if ($newPrice === null || $newPrice === '') {
            if ($existing === null) {
                return 0;
            }
            $existing->delete();

            return -1;
        }

        $price = $valid[$key] ?? null;
        if ($price === null) {
            return 'no valid current price on ' . Carbon::parse($from)->format('d-m-Y') . ' in the price master';
        }
        if (!is_numeric($newPrice) || (float) $newPrice <= 0) {
            return "new price '{$newPrice}' must be a number greater than zero";
        }
        $newPrice = (float) $newPrice;
        $remark = trim((string) $remark);
        $threshold = (float) config('apr.remark_threshold_pct');
        $pct = $price->price > 0 ? ($newPrice - $price->price) / $price->price * 100 : null;
        if ($pct !== null && $pct > $threshold && $remark === '') {
            return sprintf('price increases %.2f%% (above %s%%), remark is required', $pct, $threshold);
        }

        $overlap = NewPrice::query()
            ->where('item_code', $code)->where('supplier_id', $supplierId)
            ->where('new_tc_from', '!=', $from)
            ->where('new_tc_from', '<=', $to)->where('new_tc_to', '>=', $from)
            ->first();
        if ($overlap !== null) {
            return sprintf('already has a new price for period %s - %s', $overlap->new_tc_from->format('d-m-Y'), $overlap->new_tc_to->format('d-m-Y'));
        }

        $data = [
            'item_id' => $price->item_id,
            'item_name' => $price->item_name,
            'unit' => $price->unit,
            'currency' => $price->currency,
            'item_price_id' => $price->item_price_id,
            'current_price' => $price->price,
            'current_tc_from' => $price->tc_from->toDateString(),
            'current_tc_to' => $price->tc_to->toDateString(),
            'current_quotation' => $price->quotation,
            'new_price' => $newPrice,
            'new_tc_to' => $to,
            'remark' => $remark === '' ? null : mb_substr($remark, 0, 255),
        ];
        if ($existing !== null) {
            $existing->update($data + ['updated_by' => $loginUser]);
        } else {
            NewPrice::create($data + [
                'item_code' => $code,
                'supplier_id' => $supplierId,
                'new_tc_from' => $from,
                'status' => NewPrice::StatusDraft,
                'created_by' => $loginUser,
            ]);
        }

        return 1;
    }

    /**
     * Harga Valid di tanggal $date per item + supplier.
     *
     * @return array<string,ItemPrice> "item_code|supplier_id" -> harga
     */
    private function validPrices(string $date, ?int $supplierId, ?string $category): array
    {
        $prices = ItemPrice::query()
            ->with('supplier:supplier_id,supplier_code,supplier_name')
            ->validOn($date)
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($category, fn ($q) => $q->whereIn('item_code', DB::table('items')->where('category', $category)->select('item_code')))
            ->orderByDesc('tc_from')
            ->orderByDesc('ogs_update_date')
            ->orderByDesc('item_price_id')
            ->get();

        $result = [];
        foreach ($prices as $p) {
            $result[$p->item_code . '|' . $p->supplier_id] ??= $p;
        }

        return $result;
    }

    private function validatePeriod(string $from, string $to): ?string
    {
        foreach (['from' => $from, 'to' => $to] as $label => $value) {
            if (\DateTime::createFromFormat('!Y-m-d', $value) === false) {
                return "Period {$label} is required.";
            }
        }

        return $from > $to ? 'Period from must not be later than period to.' : null;
    }

    private function row(?ItemPrice $p, ?NewPrice $n, ?string $category): object
    {
        $current = $p?->price ?? $n?->current_price;
        $new = $n?->new_price;

        return (object) [
            'Key' => ($p?->item_code ?? $n->item_code) . '|' . ($p?->supplier_id ?? $n->supplier_id),
            'ItemCode' => $p?->item_code ?? $n->item_code,
            'ItemName' => $p?->item_name ?? $n->item_name,
            'Unit' => $p?->unit ?? $n->unit,
            'Category' => $category,
            'SupplierId' => $p?->supplier_id ?? $n->supplier_id,
            'SupplierCode' => ($p ?? $n)->supplier?->supplier_code,
            'SupplierName' => ($p ?? $n)->supplier?->supplier_name,
            'PlantCode' => $p?->supplier_plant_code,
            'Currency' => $p?->currency ?? $n->currency,
            'CurrentPrice' => $current,
            'CurrentTcFrom' => ($p?->tc_from ?? $n?->current_tc_from)?->toDateString(),
            'CurrentTcTo' => ($p?->tc_to ?? $n?->current_tc_to)?->toDateString(),
            'Quotation' => $p?->quotation ?? $n?->current_quotation,
            // Current price saat new price disimpan berbeda dengan price master sekarang / sudah tidak Valid.
            'SavedCurrentPrice' => $n?->current_price,
            'CurrentChanged' => $n !== null && ($p === null || abs($p->price - $n->current_price) > 0.00001),
            'NoValidPrice' => $p === null,
            'NewPrice' => $new,
            'Remark' => $n?->remark,
            'Status' => $n !== null ? 'Draft' : null,
            'ModifiedBy' => $n?->updated_by ?? $n?->created_by,
            'ModifiedDate' => $n?->updated_at ?? $n?->created_at,
        ];
    }
}
