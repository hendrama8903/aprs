<?php

namespace App\Repositories;

use App\Models\Item;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Item master. Bisa dikelola manual (add/edit/delete) atau lewat upload file export OGS
 * (upload ditangani OgsImportRepo dengan tipe ITEM, upsert per item_code).
 */
class ItemRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?string $category): array
    {
        return Item::query()
            ->withCount(['prices as valid_price_count' => fn ($q) => $q->validOn(now()->toDateString())])
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('item_code', 'like', "%{$keyword}%")
                ->orWhere('item_name', 'like', "%{$keyword}%")))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('item_code')
            ->get()
            ->map(fn (Item $i) => $this->mapItem($i))
            ->all();
    }

    public function getByKey(int $itemId): ?object
    {
        $item = Item::query()
            ->withCount(['prices as valid_price_count' => fn ($q) => $q->validOn(now()->toDateString())])
            ->find($itemId);

        if ($item === null) {
            return null;
        }

        // Untuk <input type="date"> di form: string Y-m-d, bebas pergeseran zona waktu saat serialisasi JSON.
        $data = $this->mapItem($item);
        $data->TcFromValue = $item->tc_from?->format('Y-m-d');
        $data->TcToValue = $item->tc_to?->format('Y-m-d');

        return $data;
    }

    /** @return string[] */
    public function categories(): array
    {
        return Item::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $code = mb_strtoupper(trim((string) ($data['ItemCode'] ?? '')));
        $fields = $this->fields($data);
        if ($code === '' || $fields['item_name'] === '') {
            return new DBResult(false, 'Item code and item name are required.');
        }
        if ($error = $this->validateDates($fields)) {
            return new DBResult(false, $error);
        }
        if (Item::query()->where('item_code', $code)->exists()) {
            return new DBResult(false, "Item code {$code} already exists.");
        }

        $item = Item::create($fields + [
            'item_code' => mb_substr($code, 0, 20),
            'created_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data saved successfully.', $item->item_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($fields['item_name'] === '') {
            return new DBResult(false, 'Item name is required.');
        }
        if ($error = $this->validateDates($fields)) {
            return new DBResult(false, $error);
        }

        $item = Item::query()->find($data['ItemId'] ?? null);
        if ($item === null) {
            return new DBResult(false, 'Item not found.');
        }

        // Kode tidak ikut di-update: kode adalah kunci upsert upload OGS dan dikunci di form saat EDIT.
        $item->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data updated successfully.');
    }

    public function delete(int $itemId, string $loginUser): DBResult
    {
        $item = Item::query()->find($itemId);
        if ($item === null) {
            return new DBResult(false, 'Item not found.');
        }

        if ($item->prices()->exists()) {
            return new DBResult(false, "Item {$item->item_code} still has price data and cannot be deleted.");
        }
        if (DB::table('apr_details')->where('item_id', $itemId)->exists()) {
            return new DBResult(false, "Item {$item->item_code} is used in APR documents and cannot be deleted.");
        }

        $item->delete();

        return new DBResult(true, 'Data deleted successfully.');
    }

    /** Kolom yang boleh diubah dari form (tanpa item_code). */
    private function fields(array $data): array
    {
        $text = fn (string $key, int $max) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return [
            'item_name' => mb_substr(trim((string) ($data['ItemName'] ?? '')), 0, 150),
            'unit' => $text('Unit', 20),
            'category' => ($c = $text('Category', 50)) === null ? null : mb_strtoupper($c),
            'tool_number' => $text('ToolNumber', 100),
            'drawing_number' => $text('DrawingNumber', 100),
            'tc_from' => $text('TcFrom', 10),
            'tc_to' => $text('TcTo', 10),
            'remark' => $text('Remark', 255),
        ];
    }

    private function validateDates(array $fields): ?string
    {
        foreach (['tc_from' => 'TC From', 'tc_to' => 'TC To'] as $key => $label) {
            if ($fields[$key] !== null && \DateTime::createFromFormat('!Y-m-d', $fields[$key]) === false) {
                return "{$label} is not a valid date.";
            }
        }
        if ($fields['tc_from'] !== null && $fields['tc_to'] !== null && $fields['tc_from'] > $fields['tc_to']) {
            return 'TC From must not be later than TC To.';
        }

        return null;
    }

    private function mapItem(Item $i): object
    {
        return (object) [
            'ItemId' => $i->item_id,
            'ItemCode' => $i->item_code,
            'ItemName' => $i->item_name,
            'Unit' => $i->unit,
            'Category' => $i->category,
            'ToolNumber' => $i->tool_number,
            'DrawingNumber' => $i->drawing_number,
            'TcFrom' => $i->tc_from,
            'TcTo' => $i->tc_to,
            'Remark' => $i->remark,
            'ValidPriceCount' => $i->valid_price_count ?? 0,
            'CreatedBy' => $i->created_by,
            'CreatedDate' => $i->created_at,
            'ModifiedBy' => $i->updated_by,
            'ModifiedDate' => $i->updated_at,
        ];
    }
}
