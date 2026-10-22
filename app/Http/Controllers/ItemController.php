<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesOgsUpload;
use App\Models\OgsImport;
use App\Repositories\ItemRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * Item master: add/edit/delete manual, upload file export OGS (item master), dan export Excel.
 */
class ItemController extends Controller
{
    use HandlesOgsUpload;

    public function __construct(private ItemRepo $itemRepo)
    {
    }

    public function index()
    {
        return view('item.index', $this->crudPermissions('ITEM') + [
            'upload' => $this->hasPermission(PermissionCodes::ItemUpload),
            'categories' => $this->itemRepo->categories(),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->itemRepo->search(
            $request->input('keyword'),
            $request->input('category'),
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->itemRepo->getByKey((int) $request->input('itemId')),
            'Item not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only([
            'ItemId', 'ItemCode', 'ItemName', 'Unit', 'Category', 'ToolNumber', 'DrawingNumber', 'TcFrom', 'TcTo', 'Remark',
        ]);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'ITEM',
            fn () => $this->itemRepo->insert($data, $this->loginUsername()),
            fn () => $this->itemRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ItemDelete, $ids, fn (int $id) => $this->itemRepo->delete($id, $this->loginUsername()));
    }

    public function downloadTemplate()
    {
        return $this->ogsTemplate(OgsImport::TypeItem);
    }

    public function upload(Request $request)
    {
        return $this->ogsUpload($request, OgsImport::TypeItem, PermissionCodes::ItemUpload);
    }

    /** Detail (warning) satu log upload item, ditampilkan setelah upload selesai. */
    public function importDetail(Request $request)
    {
        return $this->ogsImportDetail($request, OgsImport::TypeItem);
    }

    public function downloadExcel(Request $request)
    {
        // Header sama dengan template upload, jadi file export bisa diedit lalu di-upload kembali.
        return $this->exportExcel(
            'Item',
            'ITEM',
            ['Item Code', 'Item Name', 'Unit', 'Category', 'Tool Number', 'Drawing Number', 'TC From', 'TC To', 'Remark', 'Valid Price'],
            $this->itemRepo->search(null, $request->input('category')),
            fn (object $i) => [
                $i->ItemCode, $i->ItemName, $i->Unit, $i->Category, $i->ToolNumber, $i->DrawingNumber,
                $i->TcFrom?->format('Y-m-d'), $i->TcTo?->format('Y-m-d'), $i->Remark, $i->ValidPriceCount,
            ],
        );
    }
}
