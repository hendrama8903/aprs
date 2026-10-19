<?php

namespace App\Http\Controllers;

use App\Repositories\DepartmentRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * CONTOH MASTER STANDAR — salin file ini sebagai titik awal master baru.
 * Endpoint: Index, Search, GetByKey, AddEdit, DeleteMultiple, DownloadExcel
 * (pasangannya di view memakai HMA.crudPage, lihat resources/views/department/_scripts.blade.php).
 */
class DepartmentController extends Controller
{
    public function __construct(private DepartmentRepo $departmentRepo)
    {
    }

    public function index()
    {
        return view('department.index', $this->crudPermissions('DEPARTMENT'));
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->departmentRepo->search(
            $request->input('keyword'),
            $request->filled('isActive') ? $request->boolean('isActive') : null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->departmentRepo->getByKey((int) $request->input('departmentId')),
            'Department not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['DepartmentId', 'DepartmentCode', 'DepartmentName']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'DEPARTMENT',
            fn () => $this->departmentRepo->insert($data, $this->loginUsername()),
            fn () => $this->departmentRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::DepartmentDelete, $ids, fn (int $id) => $this->departmentRepo->delete($id, $this->loginUsername()));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Department',
            'DEPARTMENT',
            ['Department Code', 'Department Name', 'Total User', 'Status', 'Created By', 'Created Date', 'Modified By', 'Modified Date'],
            $this->departmentRepo->search(null, null),
            fn (object $d) => [
                $d->DepartmentCode, $d->DepartmentName, $d->UserCount,
                $d->IsActive ? 'Active' : 'Inactive', $d->CreatedBy, $d->CreatedDate, $d->ModifiedBy, $d->ModifiedDate,
            ],
        );
    }
}
