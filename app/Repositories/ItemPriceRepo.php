<?php

namespace App\Repositories;

use App\Models\ItemPrice;

/**
 * Price master hasil import OGS (read-only). Sumber "harga current" saat membuat dokumen APR.
 */
class ItemPriceRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?int $supplierId, ?bool $isActive): array
    {
        return ItemPrice::query()
            ->with('supplier:supplier_id,supplier_code,supplier_name')
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('item_code', 'like', "%{$keyword}%")
                ->orWhere('item_name', 'like', "%{$keyword}%")))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('item_code')
            ->orderByDesc('is_active')
            ->get()
            ->map(fn (ItemPrice $p) => $this->mapPrice($p))
            ->all();
    }

    private function mapPrice(ItemPrice $p): object
    {
        return (object) [
            'ItemPriceId' => $p->item_price_id,
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
            'IsActive' => $p->is_active,
        ];
    }
}
