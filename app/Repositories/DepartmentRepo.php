<?php

namespace App\Repositories;

use App\Models\Department;
use App\Support\DBResult;

/**
 * CONTOH MASTER STANDAR — salin file ini sebagai titik awal master baru.
 *
 * Pola:
 * - search/getByKey mengembalikan object PascalCase (dikonsumsi grid & form di view).
 * - insert/update/delete mengembalikan DBResult (status + message) untuk ditampilkan sebagai growl.
 * - Validasi bisnis (wajib isi, kode unik, masih dipakai data lain) dilakukan di sini, bukan di controller.
 */
class DepartmentRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return Department::query()
            ->withCount('users')
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('department_code', 'like', "%{$keyword}%")
                ->orWhere('department_name', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('department_name')
            ->get()
            ->map(fn (Department $d) => $this->mapDepartment($d))
            ->values()
            ->all();
    }

    public function getByKey(int $departmentId): ?object
    {
        $department = Department::query()->withCount('users')->find($departmentId);

        return $department === null ? null : $this->mapDepartment($department);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $code = mb_strtoupper(trim((string) ($data['DepartmentCode'] ?? '')));
        $name = trim((string) ($data['DepartmentName'] ?? ''));

        if ($code === '' || $name === '') {
            return new DBResult(false, 'Department code and name are required.');
        }

        if (Department::query()->where('department_code', $code)->exists()) {
            return new DBResult(false, "Department code {$code} already exists.");
        }

        $department = Department::create([
            'department_code' => $code,
            'department_name' => $name,
            'is_active' => $data['IsActive'] ?? true,
            'created_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data saved successfully.', $department->department_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $name = trim((string) ($data['DepartmentName'] ?? ''));
        if ($name === '') {
            return new DBResult(false, 'Department name is required.');
        }

        $department = Department::query()->find($data['DepartmentId']);
        if ($department === null) {
            return new DBResult(false, 'Department not found.');
        }

        // Kode tidak ikut di-update: kode adalah identitas bisnis dan dikunci di form saat EDIT.
        $department->update([
            'department_name' => $name,
            'is_active' => $data['IsActive'] ?? true,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data updated successfully.');
    }

    public function delete(int $departmentId, string $loginUser): DBResult
    {
        $department = Department::query()->find($departmentId);
        if ($department === null) {
            return new DBResult(false, 'Department not found.');
        }

        if ($department->users()->exists()) {
            return new DBResult(false, "Department {$department->department_name} is still used by users and cannot be deleted. Set it to Inactive instead.");
        }

        $department->delete();

        return new DBResult(true, 'Data deleted successfully.');
    }

    private function mapDepartment(Department $d): object
    {
        return (object) [
            'DepartmentId' => $d->department_id,
            'DepartmentCode' => $d->department_code,
            'DepartmentName' => $d->department_name,
            'UserCount' => $d->users_count ?? 0,
            'IsActive' => $d->is_active,
            'CreatedBy' => $d->created_by,
            'CreatedDate' => $d->created_at,
            'ModifiedBy' => $d->updated_by,
            'ModifiedDate' => $d->updated_at,
        ];
    }
}
