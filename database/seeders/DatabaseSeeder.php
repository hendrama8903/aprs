<?php

namespace Database\Seeders;

use App\Models\ApprovalStep;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserAccount;
use App\Support\PasswordHasher;
use Illuminate\Database\Seeder;

/**
 * Data awal APR: role, menu, permission, user, dan urutan approval.
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
        $newPriceMenu = $menu('/NewPrice/Index', $priceConfirmation, 'Set New Price', 'fa-pencil-square-o', 5);
        $aprList = $menu('/Apr/Index', $priceConfirmation, 'APR List', 'fa-file-text-o', 10);
        $approvalInbox = $menu('/Approval/Index', $priceConfirmation, 'Approval Inbox', 'fa-inbox', 20);

        // Data dari OGS (bisa juga diinput manual). Upload file export OGS ada di menu masing-masing.
        $masterData = $group('Master Data', 'fa-database', 30);
        $itemMenu = $menu('/Item/Index', $masterData, 'Item', 'fa-cube', 20);
        $supplierMenu = $menu('/Supplier/Index', $masterData, 'Supplier', 'fa-truck', 30);
        $itemPriceMenu = $menu('/ItemPrice/Index', $masterData, 'Item Price', 'fa-tags', 40);
        $poMenu = $menu('/PoTransaction/Index', $masterData, 'PO Transaction', 'fa-shopping-cart', 50);
        $exchangeRateMenu = $menu('/ExchangeRate/Index', $masterData, 'Exchange Rate', 'fa-exchange', 60);

        $administration = $group('Administration', 'fa-cogs', 90);
        $user = $menu('/User/Index', $administration, 'User', 'fa-user', 10);
        $role = $menu('/Role/Index', $administration, 'Role & Access', 'fa-key', 20);
        $menuMaster = $menu('/Menu/Index', $administration, 'Menu Master', 'fa-sitemap', 30);
        $approvalSetting = $menu('/ApprovalSetting/Index', $administration, 'Approval Setting', 'fa-sort-amount-asc', 40);
        $docNumbering = $menu('/DocNumbering/Index', $administration, 'Document Numbering', 'fa-sort-numeric-asc', 50);
        $menu('/AuditTrail/Index', $administration, 'Audit Trail', 'fa-history', 60);

        // ---------- Permission ----------
        $permissions = [
            [$newPriceMenu, 'NEWPRICE-EDIT', 'Edit'],
            [$aprList, 'APR-ADD', 'Add'],
            [$aprList, 'APR-EDIT', 'Edit & Submit'],
            [$aprList, 'APR-DELETE', 'Delete Draft'],
            [$aprList, 'APR-ISSUE', 'Issue Letter'],
            [$itemMenu, 'ITEM-ADD', 'Add'],
            [$itemMenu, 'ITEM-EDIT', 'Edit'],
            [$itemMenu, 'ITEM-DELETE', 'Delete'],
            [$itemMenu, 'ITEM-UPLOAD', 'Upload'],
            [$itemPriceMenu, 'ITEMPRICE-ADD', 'Add'],
            [$itemPriceMenu, 'ITEMPRICE-EDIT', 'Edit'],
            [$itemPriceMenu, 'ITEMPRICE-DELETE', 'Delete'],
            [$itemPriceMenu, 'ITEMPRICE-UPLOAD', 'Upload'],
            [$poMenu, 'POTRANSACTION-UPLOAD', 'Upload'],
            [$supplierMenu, 'SUPPLIER-ADD', 'Add'],
            [$supplierMenu, 'SUPPLIER-EDIT', 'Edit'],
            [$supplierMenu, 'SUPPLIER-DELETE', 'Delete'],
            [$exchangeRateMenu, 'EXCHANGERATE-ADD', 'Add'],
            [$exchangeRateMenu, 'EXCHANGERATE-EDIT', 'Edit'],
            [$exchangeRateMenu, 'EXCHANGERATE-DELETE', 'Delete'],
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
            [$docNumbering, 'DOCNUMBERING-EDIT', 'Edit'],
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
            $dashboard->menu_id, $priceConfirmation->menu_id, $newPriceMenu->menu_id, $aprList->menu_id,
            $masterData->menu_id, $itemMenu->menu_id, $supplierMenu->menu_id, $itemPriceMenu->menu_id, $poMenu->menu_id, $exchangeRateMenu->menu_id,
        ], ['NEWPRICE-EDIT', 'APR-ADD', 'APR-EDIT', 'APR-DELETE', 'APR-ISSUE', 'SUPPLIER-ADD', 'SUPPLIER-EDIT', 'SUPPLIER-DELETE', 'ITEM-ADD', 'ITEM-EDIT', 'ITEM-DELETE', 'ITEM-UPLOAD', 'ITEMPRICE-ADD', 'ITEMPRICE-EDIT', 'ITEMPRICE-DELETE', 'ITEMPRICE-UPLOAD', 'POTRANSACTION-UPLOAD', 'EXCHANGERATE-ADD', 'EXCHANGERATE-EDIT', 'EXCHANGERATE-DELETE']);
        $grant($roles['APPROVER'], [
            $dashboard->menu_id, $priceConfirmation->menu_id, $aprList->menu_id, $approvalInbox->menu_id,
            $masterData->menu_id, $itemPriceMenu->menu_id, $exchangeRateMenu->menu_id,
        ], []);
        $grant($roles['VIEWER'], [$dashboard->menu_id, $priceConfirmation->menu_id, $aprList->menu_id], []);

        // ---------- User ----------
        UserAccount::firstOrCreate(['username' => 'admin'], [
            'full_name' => 'System Administrator',
            'role_id' => $roles['ADMIN']->role_id,
            'is_ad_user' => false,
            'password_hash' => PasswordHasher::hash('Admin@123'),
            'created_by' => $by,
        ]);

        $users = [];
        foreach ([
            ['pud.staff', 'Dummy Staff PUD', 'PUD_STAFF'],
            ['pud.dh', 'Dummy Department Head', 'APPROVER'],
            ['pud.adv', 'Dummy Advisor', 'APPROVER'],
            ['pud.sadv', 'Dummy Senior Advisor', 'APPROVER'],
            ['pud.div', 'Dummy Division Head', 'APPROVER'],
            ['director', 'Dummy Director', 'APPROVER'],
            ['fa.viewer', 'Dummy Finance Viewer', 'VIEWER'],
        ] as [$username, $fullName, $roleCode]) {
            $users[$username] = UserAccount::firstOrCreate(['username' => $username], [
                'full_name' => $fullName,
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
