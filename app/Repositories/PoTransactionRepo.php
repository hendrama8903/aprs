<?php

namespace App\Repositories;

use App\Models\PoTransaction;

/**
 * Transaksi PO hasil upload file export OGS (read-only, upsert per PO NO + PO LINE).
 * Dipakai untuk qty pembelian dan statistik harga PO di cost study APR.
 */
class PoTransactionRepo
{
    /** @return object[] */
    public function search(?int $supplierId, ?string $dateFrom, ?string $dateTo): array
    {
        return PoTransaction::query()
            ->with(['supplier:supplier_id,supplier_code,supplier_name', 'item:item_id,item_name,unit'])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($dateFrom, fn ($q) => $q->where('po_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('po_date', '<=', $dateTo))
            ->orderByDesc('po_date')
            ->orderBy('po_no')
            ->orderBy('po_line')
            ->get()
            ->map(fn (PoTransaction $p) => (object) [
                'PoTransactionId' => $p->po_transaction_id,
                'PoNo' => $p->po_no,
                'PoLine' => $p->po_line,
                'PoDate' => $p->po_date,
                'ItemCode' => $p->item_code,
                'ItemName' => $p->item?->item_name,
                'Unit' => $p->item?->unit,
                'InItemMaster' => $p->item_id !== null,
                'SupplierCode' => $p->supplier?->supplier_code,
                'SupplierName' => $p->supplier?->supplier_name,
                'Currency' => $p->currency,
                'Qty' => $p->qty,
                'UnitPrice' => $p->unit_price,
                'Amount' => $p->amount,
            ])
            ->all();
    }
}
