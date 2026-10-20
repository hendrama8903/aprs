<?php

namespace App\Repositories;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\Supplier;
use App\Support\DBResult;
use Carbon\CarbonInterface;

/**
 * Price master: hasil upload OGS + add/edit/delete manual. Sumber "current price" saat membuat dokumen APR.
 * Status (Valid / Expired / Not yet valid) dihitung dari Time Control terhadap hari ini.
 *
 * Catatan: upload price master mengganti SELURUH tabel, termasuk baris yang ditambah / diedit manual.
 */
class ItemPriceRepo
{
    /** Mata uang yang punya kurs di dokumen APR (sama dengan validasi upload). */
    public const Currencies = ['IDR', 'USD', 'JPY'];

    /** @return object[] */
    public function search(?string $keyword, ?int $supplierId): array
    {
        $today = now();

        return ItemPrice::query()
            ->with('supplier:supplier_id,supplier_code,supplier_name')
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('item_code', 'like', "%{$keyword}%")
                ->orWhere('item_name', 'like', "%{$keyword}%")))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->orderBy('item_code')
            ->orderByDesc('tc_from')
            ->get()
            ->map(fn (ItemPrice $p) => $this->mapPrice($p, $today))
            ->all();
    }

    public function getByKey(int $itemPriceId): ?object
    {
        $price = ItemPrice::query()->with('supplier:supplier_id,supplier_code,supplier_name')->find($itemPriceId);
        if ($price === null) {
            return null;
        }

        // Untuk <input type="date"> di form: string Y-m-d, bebas pergeseran zona waktu saat serialisasi JSON.
        $data = $this->mapPrice($price, now());
        $data->TcFromValue = $price->tc_from?->format('Y-m-d');
        $data->TcToValue = $price->tc_to?->format('Y-m-d');

        return $data;
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $item = Item::query()->find((int) ($data['ItemId'] ?? 0));
        if ($item === null) {
            return new DBResult(false, 'Please choose the item.');
        }
        $supplierId = (int) ($data['SupplierId'] ?? 0);
        if (!Supplier::query()->where('supplier_id', $supplierId)->exists()) {
            return new DBResult(false, 'Please choose the supplier.');
        }

        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $item->item_code, $supplierId, null)) {
            return new DBResult(false, $error);
        }

        // Nama & unit mengikuti item master saat ditambahkan (baris OGS menyimpan nama apa adanya dari file).
        $price = ItemPrice::create($fields + [
            'item_id' => $item->item_id,
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'unit' => $item->unit,
            'supplier_id' => $supplierId,
            'created_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data saved successfully.', $price->item_price_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $price = ItemPrice::query()->find((int) ($data['ItemPriceId'] ?? 0));
        if ($price === null) {
            return new DBResult(false, 'Price not found.');
        }

        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $price->item_code, $price->supplier_id, $price->item_price_id)) {
            return new DBResult(false, $error);
        }

        // Item & supplier tidak ikut di-update: identitas harga, dikunci di form saat EDIT.
        $price->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data updated successfully.');
    }

    public function delete(int $itemPriceId, string $loginUser): DBResult
    {
        $price = ItemPrice::query()->find($itemPriceId);
        if ($price === null) {
            return new DBResult(false, 'Price not found.');
        }

        // Dokumen APR menyimpan salinan harga sendiri, jadi menghapus baris price master tidak mengubah APR.
        $price->delete();

        return new DBResult(true, 'Data deleted successfully.');
    }

    /** Kolom yang boleh diubah dari form (tanpa item & supplier). */
    private function fields(array $data): array
    {
        $text = fn (string $key, int $max) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return [
            'supplier_plant_code' => ($p = $text('SupplierPlantCode', 5)) === null ? null : mb_strtoupper($p),
            'currency' => mb_strtoupper((string) $text('Currency', 3)),
            'price' => is_numeric($data['Price'] ?? null) ? (float) $data['Price'] : null,
            'quotation' => $text('Quotation', 50),
            'tc_from' => $text('TcFrom', 10),
            'tc_to' => $text('TcTo', 10),
        ];
    }

    /** Aturan sama dengan upload price master, plus periode tidak boleh tumpang tindih (upload hanya memberi warning). */
    private function validate(array $fields, string $itemCode, int $supplierId, ?int $exceptId): ?string
    {
        if (!in_array($fields['currency'], self::Currencies, true)) {
            return 'Please choose the currency (' . implode(', ', self::Currencies) . ').';
        }
        if ($fields['price'] === null || $fields['price'] <= 0) {
            return 'Price must be greater than zero.';
        }
        foreach (['tc_from' => 'Time control from', 'tc_to' => 'Time control to'] as $key => $label) {
            if ($fields[$key] === null || \DateTime::createFromFormat('!Y-m-d', $fields[$key]) === false) {
                return "{$label} is required.";
            }
        }
        if ($fields['tc_from'] > $fields['tc_to']) {
            return 'Time control from must not be later than time control to.';
        }

        $overlap = ItemPrice::query()
            ->where('item_code', $itemCode)
            ->where('supplier_id', $supplierId)
            ->when($exceptId, fn ($q) => $q->where('item_price_id', '!=', $exceptId))
            ->where('tc_from', '<=', $fields['tc_to'])
            ->where('tc_to', '>=', $fields['tc_from'])
            ->orderBy('tc_from')
            ->first();
        if ($overlap !== null) {
            return sprintf(
                'Time control overlaps with another price of this item and supplier (%s - %s, price %s). Adjust the period or edit that price instead.',
                $overlap->tc_from->format('d-m-Y'), $overlap->tc_to->format('d-m-Y'), rtrim(rtrim(number_format($overlap->price, 4, '.', ','), '0'), '.'),
            );
        }

        return null;
    }

    private function mapPrice(ItemPrice $p, CarbonInterface $today): object
    {
        $status = ItemPrice::statusOn($p->tc_from, $p->tc_to, $today);

        return (object) [
            'ItemPriceId' => $p->item_price_id,
            'ItemId' => $p->item_id,
            'ItemCode' => $p->item_code,
            'ItemName' => $p->item_name,
            'Unit' => $p->unit,
            'InItemMaster' => $p->item_id !== null,
            'SupplierId' => $p->supplier_id,
            'SupplierCode' => $p->supplier?->supplier_code,
            'SupplierName' => $p->supplier?->supplier_name,
            'SupplierPlantCode' => $p->supplier_plant_code,
            'Currency' => $p->currency,
            'Price' => $p->price,
            'Quotation' => $p->quotation,
            'TcFrom' => $p->tc_from,
            'TcTo' => $p->tc_to,
            'OgsInputBy' => $p->ogs_input_by,
            'OgsInputDate' => $p->ogs_input_date,
            'OgsUpdateBy' => $p->ogs_update_by,
            'OgsUpdateDate' => $p->ogs_update_date,
            'Status' => $status === null ? null : ItemPrice::StatusLabels[$status],
            'Source' => $p->import_id !== null ? 'OGS' : 'Manual',
            'CreatedBy' => $p->created_by,
            'CreatedDate' => $p->created_at,
            'ModifiedBy' => $p->updated_by,
            'ModifiedDate' => $p->updated_at,
        ];
    }
}
