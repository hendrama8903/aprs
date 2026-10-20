<?php

namespace App\Http\Controllers;

use App\Repositories\SupplierRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * Supplier master: add/edit/delete manual dan export Excel.
 * Kode & nama juga diperbarui otomatis dari upload price master / PO OGS.
 */
class SupplierController extends Controller
{
    public function __construct(private SupplierRepo $supplierRepo)
    {
    }

    public function index()
    {
        return view('supplier.index', $this->crudPermissions('SUPPLIER'));
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->supplierRepo->search(
            $request->input('keyword'),
            $request->filled('isActive') ? $request->boolean('isActive') : null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->supplierRepo->getByKey((int) $request->input('supplierId')),
            'Supplier not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['SupplierId', 'SupplierCode', 'SupplierName']);
        $data['IsActive'] = $request->boolean('IsActive');
        // Daftar alamat dikirim sebagai JSON (array of object).
        $addresses = json_decode((string) $request->input('Addresses', '[]'), true);
        $addresses = is_array($addresses) ? $addresses : [];

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'SUPPLIER',
            fn () => $this->supplierRepo->insert($data, $addresses, $this->loginUsername()),
            fn () => $this->supplierRepo->update($data, $addresses, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::SupplierDelete, $ids, fn (int $id) => $this->supplierRepo->delete($id, $this->loginUsername()));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Supplier',
            'SUPPLIER',
            ['Supplier Code', 'Supplier Name', 'Default Address', 'Attention', 'Attention Title', 'Email', 'Total Address', 'Price Rows', 'Valid Price', 'Status'],
            $this->supplierRepo->search(null, null),
            fn (object $s) => [
                $s->SupplierCode, $s->SupplierName, $s->DefaultAddress, $s->AttentionName, $s->AttentionTitle, $s->Email,
                $s->AddressCount, $s->PriceCount, $s->ValidPriceCount, $s->IsActive ? 'Active' : 'Inactive',
            ],
        );
    }
}
