<?php

namespace App\Http\Controllers;

use App\Repositories\MenuRepo;
use App\Repositories\RoleRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Port dari Controllers/RoleController.cs. */
class RoleController extends Controller
{
    public function __construct(
        private RoleRepo $roleRepo,
        private MenuRepo $menuRepo,
    ) {
    }

    public function index()
    {
        return view('role.index', [
            ...$this->crudPermissions('ROLE'),
            'access' => $this->hasPermission(PermissionCodes::RoleAccess),
        ]);
    }

    public function search(Request $request)
    {
        try {
            $isActive = $request->filled('isActive') ? $request->boolean('isActive') : null;
            $data = $this->roleRepo->search($request->input('keyword'), $isActive);

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function getByKey(Request $request)
    {
        try {
            $data = $this->roleRepo->getByKey((int) $request->input('roleId'));

            return $data === null
                ? response()->json(['status' => false, 'message' => 'Role not found.'])
                : response()->json(['status' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['RoleId', 'RoleCode', 'RoleName', 'Description', 'IsActive']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'ROLE',
            fn () => $this->roleRepo->insert($data, $this->loginUsername()),
            fn () => $this->roleRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::RoleDelete, $ids, fn (int $id) => $this->roleRepo->delete($id, $this->loginUsername()));
    }

    public function getAccess(Request $request)
    {
        try {
            $roleId = (int) $request->input('roleId');

            return response()->json([
                'status' => true,
                'menus' => $this->menuRepo->getRoleMenuAccess($roleId),
                'permissions' => $this->menuRepo->getRolePermissionAccess($roleId),
            ]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function saveAccess(Request $request)
    {
        try {
            if (!$this->hasPermission(PermissionCodes::RoleAccess)) {
                return $this->permissionDenied();
            }

            $roleId = (int) $request->input('roleId');
            $menuIds = array_map('intval', (array) $request->input('menuIds', []));
            $permissionIds = array_map('intval', (array) $request->input('permissionIds', []));

            return response()->json($this->roleRepo->saveAccess($roleId, $menuIds, $permissionIds, $this->loginUsername()));
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Role',
            'ROLE',
            ['Role Code', 'Role Name', 'Description', 'Total User', 'Status', 'Created By', 'Created Date', 'Modified By', 'Modified Date'],
            $this->roleRepo->search(null, null),
            fn (object $r) => [
                $r->RoleCode, $r->RoleName, $r->Description, $r->UserCount,
                $r->IsActive ? 'Active' : 'Inactive', $r->CreatedBy, $r->CreatedDate, $r->ModifiedBy, $r->ModifiedDate,
            ],
        );
    }
}
