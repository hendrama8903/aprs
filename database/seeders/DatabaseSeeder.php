<?php

namespace Database\Seeders;

use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserAccount;
use App\Support\PasswordHasher;
use Illuminate\Database\Seeder;

/**
 * Data awal APR: role, menu, permission, department, user, dan urutan approval.
 * Setiap menu baru: tambahkan menu + permission di sini.
 *
 * Login awal: admin / Admin@123 (WAJIB diganti setelah login pertama).
 * User DUMMY untuk development/demo (password sama: DummyPass@123), hapus atau nonaktifkan di production:
 *   pud.staff  - Staff PUD (membuat APR, import OGS)
 *   pud.dh     - Department Head      (approval tahap 1)
 *   pud.adv    - Advisor              (approval tahap 2)
 *   pud.sadv   - Senior Advisor       (approval tahap 3)
 *   pud.div    - Division Head        (approval tahap 4)
 *   director   - Director             (approval tahap 5)
 *   fa.viewer  - Finance & Accounting (lihat dokumen saja)
 */
class DatabaseSeeder extends Seeder
{
    private const DummyPassword = 'DummyPass@123';

    public function run(): void
    {
        $by = 'system';

        // ---------- Role ----------
        $roles = [];
        foreach ([
            ['ADMIN', 'Administrator', 'Full access to all menus and settings', true],
            ['PUD_STAFF', 'PUD Staff', 'Create APR documents, import OGS data, issue Price Confirmation letters', false],
            ['APPROVER', 'Approver', 'Approve or reject APR documents assigned in Approval Setting', false],
            ['VIEWER', 'Viewer', 'View APR documents and letters only', false],
        ] as [$code, $name, $description, $isSystem]) {
            $roles[$code] = Role::firstOrCreate(['role_code' => $code], [
                'role_name' => $name, 'description' => $description, 'is_system' => $isSystem, 'created_by' => $by,
            ]);
        }

        // ---------- Menu ----------
        $menu = fn (string $url, ?Menu $parent, string $name, string $icon, int $sort) => Menu::firstOrCreate(['menu_url' => $url], [
            'parent_menu_id' => $parent?->menu_id, 'menu_type' => 'SCREEN', 'menu_name' => $name, 'menu_icon' => $icon, 'sort_order' => $sort, 'created_by' => $by,
        ]);
        $group = fn (string $name, string $icon, int $sort) => Menu::firstOrCreate(['parent_menu_id' => null, 'menu_name' => $name], [
            'menu_type' => 'MENU', 'menu_icon' => $icon, 'sort_order' => $sort, 'created_by' => $by,
        ]);

        $dashboard = $menu('/Home/Index', null, 'Dashboard', 'fa-dashboard', 10);

        $priceConfirmation = $group('Price Confirmation', 'fa-check-square-o', 20);
        $aprList = $menu('/Apr/Index', $priceConfirmation, 'APR List', 'fa-file-text-o', 10);
        $approvalInbox = $menu('/Approval/Index', $priceConfirmation, 'Approval Inbox', 'fa-inbox', 20);

        $ogsData = $group('OGS Data', 'fa-cloud-download', 30);
        $ogsImport = $menu('/OgsImport/Index', $ogsData, 'Import OGS', 'fa-upload', 10);
        $itemMenu = $menu('/Item/Index', $ogsData, 'Item', 'fa-cube', 20);
        $supplierMenu = $menu('/Supplier/Index', $ogsData, 'Supplier', 'fa-truck', 30);
        $itemPriceMenu = $menu('/ItemPrice/Index', $ogsData, 'Item Price', 'fa-tags', 40);

        $masterData = $group('Master Data', 'fa-database', 40);
        $department = $menu('/Department/Index', $masterData, 'Department', 'fa-building', 10);

        $administration = $group('Administration', 'fa-cogs', 90);
        $user = $menu('/User/Index', $administration, 'User', 'fa-user', 10);
        $role = $menu('/Role/Index', $administration, 'Role & Access', 'fa-key', 20);
        $menuMaster = $menu('/Menu/Index', $administration, 'Menu Master', 'fa-sitemap', 30);
        $approvalSetting = $menu('/ApprovalSetting/Index', $administration, 'Approval Setting', 'fa-sort-amount-asc', 40);

        // ---------- Permission ----------
        $permissions = [
            [$aprList, 'APR-ADD', 'Add'],
            [$aprList, 'APR-EDIT', 'Edit & Submit'],
            [$aprList, 'APR-DELETE', 'Delete Draft'],
            [$aprList, 'APR-ISSUE', 'Issue Letter'],
            [$ogsImport, 'OGSIMPORT-UPLOAD', 'Upload'],
            [$supplierMenu, 'SUPPLIER-EDIT', 'Edit'],
            [$department, 'DEPARTMENT-ADD', 'Add'],
            [$department, 'DEPARTMENT-EDIT', 'Edit'],
            [$department, 'DEPARTMENT-DELETE', 'Delete'],
            [$user, 'USER-ADD', 'Add'],
            [$user, 'USER-EDIT', 'Edit'],
            [$user, 'USER-DELETE', 'Delete'],
            [$user, 'USER-RESETPASSWORD', 'Reset Password'],
            [$role, 'ROLE-ADD', 'Add'],
            [$role, 'ROLE-EDIT', 'Edit'],
            [$role, 'ROLE-DELETE', 'Delete'],
            [$role, 'ROLE-ACCESS', 'Set Access'],
            [$menuMaster, 'MENU-ADD', 'Add'],
            [$menuMaster, 'MENU-EDIT', 'Edit'],
            [$menuMaster, 'MENU-DELETE', 'Delete'],
            [$approvalSetting, 'APPROVALSETTING-EDIT', 'Edit'],
        ];
        $permissionIds = [];
        foreach ($permissions as [$m, $code, $name]) {
            $permissionIds[$code] = Permission::firstOrCreate(['permission_code' => $code], [
                'menu_id' => $m->menu_id, 'permission_name' => $name, 'created_by' => $by,
            ])->permission_id;
        }

        // ---------- Hak akses per role ----------
        $grant = function (Role $r, array $menuIds, array $permissionCodes) use ($by, $permissionIds) {
            $pivot = ['created_by' => $by, 'created_at' => now()];
            $r->menus()->syncWithoutDetaching(array_fill_keys($menuIds, $pivot));
            $ids = array_map(fn ($code) => $permissionIds[$code], $permissionCodes);
            $r->permissions()->syncWithoutDetaching(array_fill_keys($ids, $pivot));
        };

        $grant($roles['ADMIN'], Menu::query()->pluck('menu_id')->all(), array_keys($permissionIds));
        $grant($roles['PUD_STAFF'], [
            $dashboard->menu_id, $priceConfirmation->menu_id, $aprList->menu_id,
            $ogsData->menu_id, $ogsImport->menu_id, $itemMenu->menu_id, $supplierMenu->menu_id, $itemPriceMenu->menu_id,
        ], ['APR-ADD', 'APR-EDIT', 'APR-DELETE', 'APR-ISSUE', 'OGSIMPORT-UPLOAD', 'SUPPLIER-EDIT']);
        $grant($roles['APPROVER'], [
            $dashboard->menu_id, $priceConfirmation->menu_id, $aprList->menu_id, $approvalInbox->menu_id,
            $ogsData->menu_id, $itemPriceMenu->menu_id,
        ], []);
        $grant($roles['VIEWER'], [$dashboard->menu_id, $priceConfirmation->menu_id, $aprList->menu_id], []);

        // ---------- Department ----------
        foreach ([
            ['PUD', 'Purchasing'],
            ['FIN', 'Finance & Accounting'],
            ['MGT', 'Management'],
            ['IT', 'Information Technology'],
        ] as [$code, $name]) {
            Department::firstOrCreate(['department_code' => $code], ['department_name' => $name, 'created_by' => $by]);
        }
        $dept = Department::query()->pluck('department_id', 'department_code');

        // ---------- User ----------
        UserAccount::firstOrCreate(['username' => 'admin'], [
            'full_name' => 'System Administrator',
            'department_id' => $dept['IT'],
            'role_id' => $roles['ADMIN']->role_id,
            'is_ad_user' => false,
            'password_hash' => PasswordHasher::hash('Admin@123'),
            'created_by' => $by,
        ]);

        $users = [];
        foreach ([
            ['pud.staff', 'Dummy Staff PUD', 'PUD', 'PUD_STAFF'],
            ['pud.dh', 'Dummy Department Head', 'PUD', 'APPROVER'],
            ['pud.adv', 'Dummy Advisor', 'PUD', 'APPROVER'],
            ['pud.sadv', 'Dummy Senior Advisor', 'PUD', 'APPROVER'],
            ['pud.div', 'Dummy Division Head', 'MGT', 'APPROVER'],
            ['director', 'Dummy Director', 'MGT', 'APPROVER'],
            ['fa.viewer', 'Dummy Finance Viewer', 'FIN', 'VIEWER'],
        ] as [$username, $fullName, $deptCode, $roleCode]) {
            $users[$username] = UserAccount::firstOrCreate(['username' => $username], [
                'full_name' => $fullName,
                'department_id' => $dept[$deptCode],
                'role_id' => $roles[$roleCode]->role_id,
                'is_ad_user' => false,
                'password_hash' => PasswordHasher::hash(self::DummyPassword),
                'created_by' => $by,
            ]);
        }

        // ---------- Urutan approval (sesuai kolom tanda tangan form APR) ----------
        foreach ([
            [1, 'DH', 'Department Head', 'CHECKED', 'pud.dh'],
            [2, 'ADV', 'Advisor', 'CHECKED', 'pud.adv'],
            [3, 'SADV', 'Senior Advisor', 'CHECKED', 'pud.sadv'],
            [4, 'DIV', 'Division Head', 'APPROVED', 'pud.div'],
            [5, 'DIR', 'Director', 'APPROVED', 'director'],
        ] as [$no, $code, $name, $group, $username]) {
            ApprovalStep::firstOrCreate(['step_code' => $code], [
                'step_no' => $no,
                'step_name' => $name,
                'sign_group' => $group,
                'approver_user_id' => $users[$username]->user_id,
                'created_by' => $by,
            ]);
        }
    }
}
