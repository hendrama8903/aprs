<?php

namespace App\Http\Controllers;

use App\Repositories\DepartmentRepo;
use App\Repositories\RoleRepo;
use App\Repositories\SupplierRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Port dari Controllers/LookupController.cs — sumber data dropdown Select2.
 * Tambahkan endpoint lookup baru di sini untuk setiap master yang dipakai sebagai dropdown.
 * Format response: { status, items: [{ id, text }] }.
 */
class LookupController extends Controller
{
    public function __construct(
        private DepartmentRepo $departmentRepo,
        private RoleRepo $roleRepo,
        private SupplierRepo $supplierRepo,
        private UserRepo $userRepo,
    ) {
    }

    public function department(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($d) => ['id' => (string) $d->DepartmentId, 'text' => $d->DepartmentCode . ' - ' . $d->DepartmentName],
            $this->departmentRepo->search($request->input('q'), true),
        ));
    }

    public function supplier(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($s) => ['id' => (string) $s->SupplierId, 'text' => $s->SupplierCode . ' - ' . $s->SupplierName],
            $this->supplierRepo->search($request->input('q'), true),
        ));
    }

    public function role(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($r) => ['id' => (string) $r->RoleId, 'text' => $r->RoleName],
            $this->roleRepo->search($request->input('q'), true),
        ));
    }

    public function userList(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($u) => ['id' => (string) $u->UserId, 'text' => $u->FullName . ' (' . $u->Username . ')'],
            $this->userRepo->search($request->input('q'), null, null, true),
        ));
    }

    private function lookup(Request $request, callable $load): JsonResponse
    {
        try {
            $pageLimit = max(1, min(100, (int) $request->input('pageLimit', 10)));
            $page = max(1, (int) $request->input('page', 1));

            $items = array_slice($load(), ($page - 1) * $pageLimit, $pageLimit);

            return response()->json(['status' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }
}
