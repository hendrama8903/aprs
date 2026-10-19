<?php

namespace App\Repositories;

use App\Models\Item;

/**
 * Item master hasil import OGS. Read-only di APR: perubahan dilakukan di OGS lalu di-import ulang.
 */
class ItemRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?string $category): array
    {
        return Item::query()
            ->withCount(['prices as active_price_count' => fn ($q) => $q->where('is_active', true)])
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('item_code', 'like', "%{$keyword}%")
                ->orWhere('item_name', 'like', "%{$keyword}%")))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('item_code')
            ->get()
            ->map(fn (Item $i) => $this->mapItem($i))
            ->all();
    }

    /** @return string[] */
    public function categories(): array
    {
        return Item::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
    }

    private function mapItem(Item $i): object
    {
        return (object) [
            'ItemId' => $i->item_id,
            'ItemCode' => $i->item_code,
            'ItemName' => $i->item_name,
            'Unit' => $i->unit,
            'Category' => $i->category,
            'MinimumOrder' => $i->minimum_order,
            'ToolNumber' => $i->tool_number,
            'DrawingNumber' => $i->drawing_number,
            'TcFrom' => $i->tc_from,
            'TcTo' => $i->tc_to,
            'Remark' => $i->remark,
            'ActivePriceCount' => $i->active_price_count ?? 0,
            'CreatedBy' => $i->created_by,
            'CreatedDate' => $i->created_at,
            'ModifiedBy' => $i->updated_by,
            'ModifiedDate' => $i->updated_at,
        ];
    }
}
