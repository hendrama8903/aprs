<?php

namespace App\Repositories;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Port dari Models/Repo/MenuRepo.cs. Validasi di sini meniru stored procedure asli
 * (SQL/40_Menu_Master.sql: usp_Menu_Validate/Insert/Update/Delete, usp_Permission_*).
 *
 * NodeId Menu/Screen = "M<menu_id>", Button = "B<permission_id>"; ParentNodeId Button
 * selalu "M<menu_id induk>" - konvensi ini dipakai persis oleh resources/views/menu/_scripts.blade.php
 * (findNode("M" + id), dsb), jangan diubah.
 */
class MenuRepo
{
    public const TYPE_MENU = 'MENU';
    public const TYPE_SCREEN = 'SCREEN';
    public const TYPE_BUTTON = 'BUTTON';

    /** Screen inti yang tidak boleh dihapus/dipindah URL/dinonaktifkan, supaya admin tidak terkunci. */
    private const CORE_SCREEN_URLS = ['/Menu/Index', '/Role/Index'];

    /** @return object[] */
    public function getRoleMenuAccess(int $roleId): array
    {
        $role = Role::query()->findOrFail($roleId);
        $grantedIds = $role->menus()->pluck('menus.menu_id')->all();

        return Menu::query()->where('is_active', true)->orderBy('sort_order')->orderBy('menu_name')->get()
            ->map(fn (Menu $m) => (object) [
                'MenuId' => $m->menu_id,
                'ParentMenuId' => $m->parent_menu_id,
                'MenuName' => $m->menu_name,
                'MenuUrl' => $m->menu_url,
                'MenuIcon' => $m->menu_icon,
                'SortOrder' => $m->sort_order,
                'IsGranted' => in_array($m->menu_id, $grantedIds, true),
            ])
            ->values()->all();
    }

    /** @return object[] */
    public function getRolePermissionAccess(int $roleId): array
    {
        $role = Role::query()->findOrFail($roleId);
        $grantedIds = $role->permissions()->pluck('permissions.permission_id')->all();

        return Permission::query()->whereHas('menu', fn ($q) => $q->where('is_active', true))
            ->orderBy('menu_id')->orderBy('permission_id')->get()
            ->map(fn (Permission $p) => (object) [
                'PermissionId' => $p->permission_id,
                'MenuId' => $p->menu_id,
                'PermissionCode' => $p->permission_code,
                'PermissionName' => $p->permission_name,
                'IsGranted' => in_array($p->permission_id, $grantedIds, true),
            ])
            ->values()->all();
    }

    /** @return object[] */
    public function search(): array
    {
        $menus = Menu::query()->get()->keyBy('menu_id');
        $roleMenuCounts = DB::table('role_menu')->selectRaw('menu_id, count(*) as c')->groupBy('menu_id')->pluck('c', 'menu_id');
        $rolePermissionCounts = DB::table('role_permission')->selectRaw('permission_id, count(*) as c')->groupBy('permission_id')->pluck('c', 'permission_id');

        $nodes = [];

        foreach ($menus as $m) {
            $parent = $m->parent_menu_id !== null ? $menus->get($m->parent_menu_id) : null;
            $nodes[] = (object) [
                'NodeId' => 'M' . $m->menu_id,
                'ParentNodeId' => $m->parent_menu_id !== null ? 'M' . $m->parent_menu_id : null,
                'NodeType' => $m->menu_type,
                'RecordId' => $m->menu_id,
                'ParentMenuId' => $m->parent_menu_id,
                'ParentName' => $parent?->menu_name,
                'NodeName' => $m->menu_name,
                'MenuUrl' => $m->menu_url,
                'MenuIcon' => $m->menu_icon,
                'PermissionCode' => null,
                'SortOrder' => $m->sort_order,
                'IsActive' => $m->is_active,
                'RoleCount' => (int) ($roleMenuCounts[$m->menu_id] ?? 0),
                'CreatedBy' => $m->created_by,
                'CreatedDate' => $m->created_at,
                'ModifiedBy' => $m->updated_by,
                'ModifiedDate' => $m->updated_at,
            ];
        }

        $buttonSort = [];
        foreach (Permission::query()->orderBy('menu_id')->orderBy('permission_id')->get() as $p) {
            $buttonSort[$p->menu_id] = ($buttonSort[$p->menu_id] ?? 0) + 1;
            $screen = $menus->get($p->menu_id);
            $nodes[] = (object) [
                'NodeId' => 'B' . $p->permission_id,
                'ParentNodeId' => 'M' . $p->menu_id,
                'NodeType' => self::TYPE_BUTTON,
                'RecordId' => $p->permission_id,
                'ParentMenuId' => $p->menu_id,
                'ParentName' => $screen?->menu_name,
                'NodeName' => $p->permission_name,
                'MenuUrl' => null,
                'MenuIcon' => null,
                'PermissionCode' => $p->permission_code,
                'SortOrder' => $buttonSort[$p->menu_id],
                'IsActive' => $screen?->is_active ?? true,
                'RoleCount' => (int) ($rolePermissionCounts[$p->permission_id] ?? 0),
                'CreatedBy' => $p->created_by,
                'CreatedDate' => $p->created_at,
                'ModifiedBy' => $p->updated_by,
                'ModifiedDate' => $p->updated_at,
            ];
        }

        usort($nodes, fn ($a, $b) => [$a->SortOrder, $a->NodeName] <=> [$b->SortOrder, $b->NodeName]);

        return $nodes;
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        if ($data['NodeType'] === self::TYPE_BUTTON) {
            return $this->insertButton($data, $loginUser);
        }

        return $this->insertMenu($data, $loginUser);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        if ($data['NodeType'] === self::TYPE_BUTTON) {
            return $this->updateButton($data, $loginUser);
        }

        return $this->updateMenu($data, $loginUser);
    }

    public function delete(string $nodeType, int $recordId, string $loginUser): DBResult
    {
        return $nodeType === self::TYPE_BUTTON
            ? $this->deleteButton($recordId)
            : $this->deleteMenu($recordId);
    }

    // ---------- Menu / Screen ----------

    private function insertMenu(array $data, string $loginUser): DBResult
    {
        $menuType = $data['NodeType'];
        $parentMenuId = $data['ParentMenuId'] ?? null;
        $menuName = trim((string) $data['NodeName']);
        $menuUrl = $menuType === self::TYPE_SCREEN ? $this->nullIfBlank($data['MenuUrl'] ?? null) : null;
        $menuIcon = $this->nullIfBlank($data['MenuIcon'] ?? null);

        $error = $this->validateMenu(null, $menuType, $parentMenuId, $menuName, $menuUrl);
        if ($error !== null) {
            return new DBResult(false, $error);
        }

        $menu = DB::transaction(function () use ($menuType, $parentMenuId, $menuName, $menuUrl, $menuIcon, $data, $loginUser) {
            $menu = Menu::create([
                'parent_menu_id' => $parentMenuId,
                'menu_type' => $menuType,
                'menu_name' => $menuName,
                'menu_url' => $menuUrl,
                'menu_icon' => $menuIcon,
                'sort_order' => (int) ($data['SortOrder'] ?? 0),
                'is_active' => $data['IsActive'] ?? true,
                'created_by' => $loginUser,
            ]);

            // Role sistem (ADMIN) otomatis mendapat menu baru.
            $this->grantToSystemRoles('menus', $menu->menu_id, $loginUser);

            return $menu;
        });

        return new DBResult(true, 'Data saved successfully.', $menu->menu_id);
    }

    private function updateMenu(array $data, string $loginUser): DBResult
    {
        $menu = Menu::query()->find($data['RecordId']);
        if ($menu === null) {
            return new DBResult(false, 'Menu not found.');
        }

        $menuType = $menu->menu_type; // Tipe tidak bisa diubah setelah dibuat.
        $parentMenuId = $data['ParentMenuId'] ?? null;
        $menuName = trim((string) $data['NodeName']);
        $menuUrl = $menuType === self::TYPE_SCREEN ? $this->nullIfBlank($data['MenuUrl'] ?? null) : null;
        $menuIcon = $this->nullIfBlank($data['MenuIcon'] ?? null);
        $isActive = $data['IsActive'] ?? true;

        $error = $this->validateMenu($menu->menu_id, $menuType, $parentMenuId, $menuName, $menuUrl);
        if ($error !== null) {
            return new DBResult(false, $error);
        }

        // Screen inti tidak boleh diubah URL-nya / dinonaktifkan.
        if (in_array($menu->menu_url, self::CORE_SCREEN_URLS, true) && ($menuUrl !== $menu->menu_url || !$isActive)) {
            return new DBResult(false, "Screen {$menu->menu_url} is required by the system; its URL cannot be changed and it cannot be deactivated.");
        }

        if (!$isActive && $menuType === self::TYPE_MENU && $this->descendantsContainCoreScreen($menu->menu_id)) {
            return new DBResult(false, 'This menu contains a system screen (Menu Master / Role & Access) and cannot be deactivated.');
        }

        $menu->update([
            'parent_menu_id' => $parentMenuId,
            'menu_name' => $menuName,
            'menu_url' => $menuUrl,
            'menu_icon' => $menuIcon,
            'sort_order' => (int) ($data['SortOrder'] ?? 0),
            'is_active' => $isActive,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data saved successfully.');
    }

    private function deleteMenu(int $menuId): DBResult
    {
        $menu = Menu::query()->find($menuId);
        if ($menu === null) {
            return new DBResult(false, 'Menu not found.');
        }

        if (in_array($menu->menu_url, [...self::CORE_SCREEN_URLS, '/Home/Index'], true)) {
            return new DBResult(false, "Screen {$menu->menu_name} is required by the system and cannot be deleted.");
        }

        if (Menu::query()->where('parent_menu_id', $menuId)->exists()) {
            return new DBResult(false, "Menu {$menu->menu_name} still has sub menus / screens. Delete or move them first.");
        }

        if (Permission::query()->where('menu_id', $menuId)->exists()) {
            return new DBResult(false, "Screen {$menu->menu_name} still has buttons. Delete the buttons first.");
        }

        DB::transaction(function () use ($menu) {
            DB::table('role_menu')->where('menu_id', $menu->menu_id)->delete();
            $menu->delete();
        });

        return new DBResult(true, 'Data deleted successfully.');
    }

    /** Validasi bersama insert/update menu. Null = valid. */
    private function validateMenu(?int $menuId, string $menuType, ?int $parentMenuId, string $menuName, ?string $menuUrl): ?string
    {
        if (!in_array($menuType, [self::TYPE_MENU, self::TYPE_SCREEN], true)) {
            return 'Menu type must be MENU or SCREEN.';
        }

        if ($menuName === '') {
            return 'Menu name is required.';
        }

        if ($menuType === self::TYPE_SCREEN) {
            if ($menuUrl === null || $menuUrl === '') {
                return 'Screen URL is required.';
            }
            if (!str_starts_with($menuUrl, '/') || str_contains($menuUrl, ' ')) {
                return 'Screen URL must start with "/" and must not contain spaces, e.g. /Item/Index.';
            }
            if (Menu::query()->where('menu_url', $menuUrl)->when($menuId, fn ($q) => $q->where('menu_id', '<>', $menuId))->exists()) {
                return "Screen URL {$menuUrl} is already used by another screen.";
            }
        }

        if ($parentMenuId !== null) {
            $parent = Menu::query()->find($parentMenuId);
            if ($parent === null || $parent->menu_type !== self::TYPE_MENU) {
                return 'Parent must be a menu (not a screen).';
            }

            // Cegah siklus: parent tidak boleh dirinya sendiri atau turunannya.
            if ($menuId !== null && in_array($parentMenuId, $this->descendantIds($menuId), true)) {
                return 'A menu cannot be moved under itself or its own sub menu.';
            }
        }

        $duplicate = Menu::query()->where('menu_name', $menuName)
            ->where(fn ($q) => $parentMenuId === null ? $q->whereNull('parent_menu_id') : $q->where('parent_menu_id', $parentMenuId))
            ->when($menuId, fn ($q) => $q->where('menu_id', '<>', $menuId))
            ->exists();
        if ($duplicate) {
            return "Menu name {$menuName} already exists under the same parent.";
        }

        return null;
    }

    /** @return int[] termasuk $menuId sendiri */
    private function descendantIds(int $menuId): array
    {
        $ids = [$menuId];
        $queue = [$menuId];
        while ($queue !== []) {
            $children = Menu::query()->where('parent_menu_id', array_shift($queue))->pluck('menu_id')->all();
            foreach ($children as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    private function descendantsContainCoreScreen(int $menuId): bool
    {
        $urls = Menu::query()->whereIn('menu_id', $this->descendantIds($menuId))->pluck('menu_url')->all();

        return array_intersect($urls, self::CORE_SCREEN_URLS) !== [];
    }

    // ---------- Button (Permission) ----------

    private function insertButton(array $data, string $loginUser): DBResult
    {
        $menuId = $data['ParentMenuId'] ?? null;
        $permissionCode = mb_strtoupper(trim((string) ($data['PermissionCode'] ?? '')));
        $permissionName = trim((string) $data['NodeName']);

        $screen = $menuId !== null ? Menu::query()->find($menuId) : null;
        if ($screen === null || $screen->menu_type !== self::TYPE_SCREEN) {
            return new DBResult(false, 'A button must belong to a screen.');
        }

        if ($permissionCode === '' || $permissionName === '') {
            return new DBResult(false, 'Button code and button name are required.');
        }

        if (!preg_match('/^[A-Z0-9_-]+$/', $permissionCode)) {
            return new DBResult(false, 'Button code may only contain letters, numbers, "-" and "_", e.g. ITEM-ADD.');
        }

        if (Permission::query()->where('permission_code', $permissionCode)->exists()) {
            return new DBResult(false, "Button code {$permissionCode} already exists.");
        }

        $permission = DB::transaction(function () use ($menuId, $permissionCode, $permissionName, $loginUser) {
            $permission = Permission::create([
                'menu_id' => $menuId,
                'permission_code' => $permissionCode,
                'permission_name' => $permissionName,
                'created_by' => $loginUser,
            ]);

            $this->grantToSystemRoles('permissions', $permission->permission_id, $loginUser);

            return $permission;
        });

        return new DBResult(true, 'Data saved successfully.', $permission->permission_id);
    }

    // Kode button dipakai di program (PermissionCodes.php), jadi hanya nama yang bisa diubah.
    private function updateButton(array $data, string $loginUser): DBResult
    {
        $permission = Permission::query()->find($data['RecordId']);
        if ($permission === null) {
            return new DBResult(false, 'Button not found.');
        }

        $permissionName = trim((string) $data['NodeName']);
        if ($permissionName === '') {
            return new DBResult(false, 'Button name is required.');
        }

        $permission->update(['permission_name' => $permissionName, 'updated_by' => $loginUser]);

        return new DBResult(true, 'Data saved successfully.');
    }

    private function deleteButton(int $permissionId): DBResult
    {
        $permission = Permission::query()->with('menu')->find($permissionId);
        if ($permission === null) {
            return new DBResult(false, 'Button not found.');
        }

        if (in_array($permission->menu->menu_url, self::CORE_SCREEN_URLS, true)) {
            return new DBResult(false, "Button {$permission->permission_code} is required by the system and cannot be deleted.");
        }

        DB::transaction(function () use ($permission) {
            DB::table('role_permission')->where('permission_id', $permission->permission_id)->delete();
            $permission->delete();
        });

        return new DBResult(true, 'Data deleted successfully.');
    }

    private function grantToSystemRoles(string $pivotTable, int $recordId, string $loginUser): void
    {
        $column = $pivotTable === 'menus' ? 'menu_id' : 'permission_id';
        $pivot = $pivotTable === 'menus' ? 'role_menu' : 'role_permission';
        $now = now();

        foreach (Role::query()->where('is_system', true)->pluck('role_id') as $roleId) {
            DB::table($pivot)->insertOrIgnore([
                'role_id' => $roleId,
                $column => $recordId,
                'created_by' => $loginUser,
                'created_at' => $now,
            ]);
        }
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
