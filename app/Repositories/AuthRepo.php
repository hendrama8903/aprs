<?php

namespace App\Repositories;

use App\Models\Menu;
use App\Models\Role;
use App\Models\UserAccount;
use App\Support\DBResult;

/**
 * Port dari Models/Repo/AuthRepo.cs (dulu memanggil stored procedure SQL Server),
 * sekarang query Eloquent langsung ke MySQL. Kontrak return (nama properti PascalCase,
 * bentuk DBResult) dijaga sama persis supaya AuthController/SessionAuth tidak perlu berubah.
 */
class AuthRepo
{
    public function getUserByUsername(string $username): ?object
    {
        $user = UserAccount::query()->with(['role', 'department'])->where('username', $username)->first();
        if ($user === null) {
            return null;
        }

        return (object) [
            'UserId' => $user->user_id,
            'Username' => $user->username,
            'FullName' => $user->full_name,
            'Email' => $user->email,
            'DepartmentId' => $user->department_id,
            'DepartmentCode' => $user->department?->department_code,
            'DepartmentName' => $user->department?->department_name,
            'RoleId' => $user->role_id,
            'RoleCode' => $user->role->role_code,
            'RoleName' => $user->role->role_name,
            'IsAdUser' => $user->is_ad_user,
            'PasswordHash' => $user->password_hash,
            // User dianggap tidak aktif kalau role-nya dinonaktifkan.
            'IsActive' => $user->is_active && $user->role->is_active,
        ];
    }

    /**
     * Menu yang diberikan ke role + semua parent-nya (supaya grup sidebar ikut tampil).
     *
     * @return object[]
     */
    public function getMenuByRole(int $roleId): array
    {
        $allMenus = Menu::query()->where('is_active', true)->get()->keyBy('menu_id');
        $grantedIds = Role::query()->findOrFail($roleId)->menus()->where('is_active', true)->pluck('menus.menu_id');

        $resultIds = [];
        foreach ($grantedIds as $menuId) {
            $current = $allMenus->get($menuId);
            while ($current !== null && !isset($resultIds[$current->menu_id])) {
                $resultIds[$current->menu_id] = true;
                $current = $current->parent_menu_id !== null ? $allMenus->get($current->parent_menu_id) : null;
            }
        }

        $menus = $allMenus->only(array_keys($resultIds))->values();

        return $menus
            ->sortBy([['sort_order', 'asc'], ['menu_name', 'asc']])
            ->map(fn (Menu $m) => (object) [
                'MenuId' => $m->menu_id,
                'ParentMenuId' => $m->parent_menu_id,
                'MenuName' => $m->menu_name,
                'MenuUrl' => $m->menu_url,
                'MenuIcon' => $m->menu_icon,
                'SortOrder' => $m->sort_order,
            ])
            ->values()
            ->all();
    }

    /** @return object[] */
    public function getPermissionByRole(int $roleId): array
    {
        $role = Role::query()->findOrFail($roleId);
        $grantedMenuIds = $role->menus()->pluck('menus.menu_id')->all();

        return $role->permissions()
            ->whereIn('menu_id', $grantedMenuIds)
            ->get()
            ->map(fn ($p) => (object) [
                'PermissionId' => $p->permission_id,
                'MenuId' => $p->menu_id,
                'PermissionCode' => $p->permission_code,
                'PermissionName' => $p->permission_name,
            ])
            ->values()
            ->all();
    }

    public function updateLastLogin(int $userId, string $loginUser): void
    {
        UserAccount::query()->where('user_id', $userId)->update([
            'last_login_at' => now(),
            'updated_by' => $loginUser,
        ]);
    }

    public function changePassword(int $userId, string $passwordHash, string $loginUser): DBResult
    {
        $user = UserAccount::query()->find($userId);
        if ($user === null || $user->is_ad_user) {
            return new DBResult(false, 'Password can only be changed for local (non-AD) users.');
        }

        $user->update([
            'password_hash' => $passwordHash,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Password changed successfully.');
    }
}
