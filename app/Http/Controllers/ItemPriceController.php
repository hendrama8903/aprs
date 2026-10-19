<?php

namespace App\Http\Controllers;

use App\Repositories\ItemPriceRepo;
use Illuminate\Http\Request;

/**
 * Price master OGS (read-only). Data diganti lewat Import OGS.
 */
class ItemPriceController extends Controller
{
    public function __construct(private ItemPriceRepo $itemPriceRepo)
    {
    }

    public function index()
    {
        return view('item-price.index');
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->itemPriceRepo->search(
            $request->input('keyword'),
            $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
            $request->filled('isActive') ? $request->boolean('isActive') : null,
        ));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Item Price',
            'ITEM-PRICE',
            ['Item Code', 'Item Name', 'Unit', 'Supplier Code', 'Supplier Name', 'Plant', 'Currency', 'Price', 'Quotation', 'TC From', 'TC To', 'OGS Update By', 'OGS Update Date', 'Active'],
            $this->itemPriceRepo->search(
                null,
                $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
                $request->filled('isActive') ? $request->boolean('isActive') : null,
            ),
            fn (object $p) => [
                $p->ItemCode, $p->ItemName, $p->Unit, $p->SupplierCode, $p->SupplierName, $p->SupplierPlantCode, $p->Currency, $p->Price,
                $p->Quotation, $p->TcFrom?->format('d-m-Y'), $p->TcTo?->format('d-m-Y'), $p->OgsUpdateBy, $p->OgsUpdateDate, $p->IsActive ? 'Yes' : 'No',
            ],
        );
    }
}
