<?php

namespace App\Http\Controllers;

use App\Repositories\ItemRepo;
use Illuminate\Http\Request;

/**
 * Item master OGS (read-only). Data diubah lewat Import OGS.
 */
class ItemController extends Controller
{
    public function __construct(private ItemRepo $itemRepo)
    {
    }

    public function index()
    {
        return view('item.index', ['categories' => $this->itemRepo->categories()]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->itemRepo->search(
            $request->input('keyword'),
            $request->input('category'),
        ));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Item',
            'ITEM',
            ['Item Code', 'Item Name', 'Unit', 'Category', 'Minimum Order', 'Tool Number', 'Drawing Number', 'TC From', 'TC To', 'Remark', 'Active Price'],
            $this->itemRepo->search(null, $request->input('category')),
            fn (object $i) => [
                $i->ItemCode, $i->ItemName, $i->Unit, $i->Category, $i->MinimumOrder, $i->ToolNumber, $i->DrawingNumber,
                $i->TcFrom?->format('d-m-Y'), $i->TcTo?->format('d-m-Y'), $i->Remark, $i->ActivePriceCount,
            ],
        );
    }
}
