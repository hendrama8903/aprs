<?php

namespace App\Http\Controllers;

use App\Repositories\SupplierRepo;
use Illuminate\Http\Request;

/**
 * Supplier dari OGS. Tidak ada Add/Delete: supplier baru masuk lewat import price master.
 * Edit hanya untuk data surat (alamat, attention, email) dan status.
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
        $data = $request->only(['SupplierId', 'Address', 'AttentionName', 'AttentionTitle', 'Email']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            'EDIT',
            'SUPPLIER',
            fn () => null,
            fn () => $this->supplierRepo->update($data, $this->loginUsername()),
        );
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Supplier',
            'SUPPLIER',
            ['Supplier Code', 'Supplier Name', 'Address', 'Attention', 'Attention Title', 'Email', 'Price Rows', 'Active Price', 'Status'],
            $this->supplierRepo->search(null, null),
            fn (object $s) => [
                $s->SupplierCode, $s->SupplierName, $s->Address, $s->AttentionName, $s->AttentionTitle, $s->Email,
                $s->PriceCount, $s->ActivePriceCount, $s->IsActive ? 'Active' : 'Inactive',
            ],
        );
    }
}
