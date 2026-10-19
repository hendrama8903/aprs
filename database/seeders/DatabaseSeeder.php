<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserAccount;
use App\Support\PasswordHasher;
use Illuminate\Database\Seeder;

/**
 * Data awal template: role, menu (Dashboard, Master Data, Administration), permission, department, user admin.
 * Setiap master baru: tambahkan menu + permission ADD/EDIT/DELETE di sini (lihat blok Department).
 * Login awal: admin / Admin@123 (WAJIB diganti setelah login pertama).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $by = 'system';

        $admin = Role::firstOrCreate(['role_code' => 'ADMIN'], [
            'role_name' => 'Administrator',
            'description' => 'Full access to all menus and settings',
            'is_system' => true,
            'created_by' => $by,
        ]);
        $staff = Role::firstOrCreate(['role_code' => 'STAFF'], [
            'role_name' => 'Staff',
            'description' => 'Manage master data',
            'created_by' => $by,
        ]);
        $basicUser = Role::firstOrCreate(['role_code' => 'USER'], [
            'role_name' => 'User',
            'description' => 'Dashboard only',
            'created_by' => $by,
        ]);

        $dashboard = Menu::firstOrCreate(['menu_url' => '/Home/Index'], [
            'menu_type' => 'SCREEN', 'menu_name' => 'Dashboard', 'menu_icon' => 'fa-dashboard', 'sort_order' => 10, 'created_by' => $by,
        ]);
        $masterData = Menu::firstOrCreate(['parent_menu_id' => null, 'menu_name' => 'Master Data'], [
            'menu_type' => 'MENU', 'menu_icon' => 'fa-database', 'sort_order' => 20, 'created_by' => $by,
        ]);
        $administration = Menu::firstOrCreate(['parent_menu_id' => null, 'menu_name' => 'Administration'], [
            'menu_type' => 'MENU', 'menu_icon' => 'fa-cogs', 'sort_order' => 90, 'created_by' => $by,
        ]);

        $department = Menu::firstOrCreate(['menu_url' => '/Department/Index'], [
            'parent_menu_id' => $masterData->menu_id, 'menu_type' => 'SCREEN', 'menu_name' => 'Department', 'menu_icon' => 'fa-building', 'sort_order' => 10, 'created_by' => $by,
        ]);
        $user = Menu::firstOrCreate(['menu_url' => '/User/Index'], [
            'parent_menu_id' => $administration->menu_id, 'menu_type' => 'SCREEN', 'menu_name' => 'User', 'menu_icon' => 'fa-user', 'sort_order' => 10, 'created_by' => $by,
        ]);
        $role = Menu::firstOrCreate(['menu_url' => '/Role/Index'], [
            'parent_menu_id' => $administration->menu_id, 'menu_type' => 'SCREEN', 'menu_name' => 'Role & Access', 'menu_icon' => 'fa-key', 'sort_order' => 20, 'created_by' => $by,
        ]);
        $menuMaster = Menu::firstOrCreate(['menu_url' => '/Menu/Index'], [
            'parent_menu_id' => $administration->menu_id, 'menu_type' => 'SCREEN', 'menu_name' => 'Menu Master', 'menu_icon' => 'fa-sitemap', 'sort_order' => 30, 'created_by' => $by,
        ]);

        $permissions = [
            ['menu' => $department, 'code' => 'DEPARTMENT-ADD', 'name' => 'Add'],
            ['menu' => $department, 'code' => 'DEPARTMENT-EDIT', 'name' => 'Edit'],
            ['menu' => $department, 'code' => 'DEPARTMENT-DELETE', 'name' => 'Delete'],
            ['menu' => $user, 'code' => 'USER-ADD', 'name' => 'Add'],
            ['menu' => $user, 'code' => 'USER-EDIT', 'name' => 'Edit'],
            ['menu' => $user, 'code' => 'USER-DELETE', 'name' => 'Delete'],
            ['menu' => $user, 'code' => 'USER-RESETPASSWORD', 'name' => 'Reset Password'],
            ['menu' => $role, 'code' => 'ROLE-ADD', 'name' => 'Add'],
            ['menu' => $role, 'code' => 'ROLE-EDIT', 'name' => 'Edit'],
            ['menu' => $role, 'code' => 'ROLE-DELETE', 'name' => 'Delete'],
            ['menu' => $role, 'code' => 'ROLE-ACCESS', 'name' => 'Set Access'],
            ['menu' => $menuMaster, 'code' => 'MENU-ADD', 'name' => 'Add'],
            ['menu' => $menuMaster, 'code' => 'MENU-EDIT', 'name' => 'Edit'],
            ['menu' => $menuMaster, 'code' => 'MENU-DELETE', 'name' => 'Delete'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['code']] = Permission::firstOrCreate(['permission_code' => $p['code']], [
                'menu_id' => $p['menu']->menu_id, 'permission_name' => $p['name'], 'created_by' => $by,
            ]);
        }

        // ADMIN: semua menu & permission.
        $allMenus = Menu::query()->pluck('menu_id');
        $allPermissions = Permission::query()->pluck('permission_id');
        $admin->menus()->syncWithoutDetaching($allMenus->mapWithKeys(fn ($id) => [$id => ['created_by' => $by, 'created_at' => now()]]));
        $admin->permissions()->syncWithoutDetaching($allPermissions->mapWithKeys(fn ($id) => [$id => ['created_by' => $by, 'created_at' => now()]]));

        // STAFF: Dashboard + Master Data.
        $staffMenuIds = [$dashboard->menu_id, $masterData->menu_id, $department->menu_id];
        $staff->menus()->syncWithoutDetaching(array_fill_keys($staffMenuIds, ['created_by' => $by, 'created_at' => now()]));
        $staffPermissionIds = [
            $permissionModels['DEPARTMENT-ADD']->permission_id,
            $permissionModels['DEPARTMENT-EDIT']->permission_id,
            $permissionModels['DEPARTMENT-DELETE']->permission_id,
        ];
        $staff->permissions()->syncWithoutDetaching(array_fill_keys($staffPermissionIds, ['created_by' => $by, 'created_at' => now()]));

        // USER: Dashboard.
        $basicUser->menus()->syncWithoutDetaching([$dashboard->menu_id => ['created_by' => $by, 'created_at' => now()]]);

        foreach ([
            ['code' => 'GA', 'name' => 'General Affairs'],
            ['code' => 'HRD', 'name' => 'Human Resources'],
            ['code' => 'FIN', 'name' => 'Finance & Accounting'],
            ['code' => 'IT', 'name' => 'Information Technology'],
        ] as $d) {
            Department::firstOrCreate(['department_code' => $d['code']], ['department_name' => $d['name'], 'created_by' => $by]);
        }

        $itDepartment = Department::where('department_code', 'IT')->first();
        UserAccount::firstOrCreate(['username' => 'admin'], [
            'full_name' => 'System Administrator',
            'department_id' => $itDepartment->department_id,
            'role_id' => $admin->role_id,
            'is_ad_user' => false,
            'password_hash' => PasswordHasher::hash('Admin@123'),
            'created_by' => $by,
        ]);
    }
}
