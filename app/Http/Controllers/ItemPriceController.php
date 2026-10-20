<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesOgsUpload;
use App\Models\OgsImport;
use App\Repositories\ItemPriceRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * Price master: add/edit/delete manual, upload file export OGS, dan export Excel.
 * Upload mengganti SELURUH data harga, termasuk yang diinput manual.
 */
class ItemPriceController extends Controller
{
    use HandlesOgsUpload;

    public function __construct(private ItemPriceRepo $itemPriceRepo)
    {
    }

    public function index()
    {
        return view('item-price.index', $this->crudPermissions('ITEMPRICE') + [
            'upload' => $this->hasPermission(PermissionCodes::ItemPriceUpload),
            'lastImport' => $this->lastOgsImport(OgsImport::TypePrice),
            'currencies' => ItemPriceRepo::Currencies,
        ]);
    }

    public function downloadTemplate()
    {
        return $this->ogsTemplate(OgsImport::TypePrice);
    }

    public function upload(Request $request)
    {
        return $this->ogsUpload($request, OgsImport::TypePrice, PermissionCodes::ItemPriceUpload);
    }

    public function importDetail(Request $request)
    {
        return $this->ogsImportDetail($request, OgsImport::TypePrice);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->itemPriceRepo->search(
            $request->input('keyword'),
            $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->itemPriceRepo->getByKey((int) $request->input('itemPriceId')),
            'Price not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only([
            'ItemPriceId', 'ItemId', 'SupplierId', 'SupplierPlantCode', 'Currency', 'Price', 'Quotation', 'TcFrom', 'TcTo',
        ]);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'ITEMPRICE',
            fn () => $this->itemPriceRepo->insert($data, $this->loginUsername()),
            fn () => $this->itemPriceRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ItemPriceDelete, $ids, fn (int $id) => $this->itemPriceRepo->delete($id, $this->loginUsername()));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Item Price',
            'ITEM-PRICE',
            ['Item Code', 'Item Name', 'Unit', 'Supplier Code', 'Supplier Name', 'Plant', 'Currency', 'Price', 'Quotation', 'TC From', 'TC To', 'Status Today', 'Source', 'OGS Update By', 'OGS Update Date', 'Modified By', 'Modified Date'],
            $this->itemPriceRepo->search(
                null,
                $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
            ),
            fn (object $p) => [
                $p->ItemCode, $p->ItemName, $p->Unit, $p->SupplierCode, $p->SupplierName, $p->SupplierPlantCode, $p->Currency, $p->Price,
                $p->Quotation, $p->TcFrom?->format('d-m-Y'), $p->TcTo?->format('d-m-Y'), $p->Status, $p->Source,
                $p->OgsUpdateBy, $p->OgsUpdateDate, $p->ModifiedBy, $p->ModifiedDate,
            ],
        );
    }
}
