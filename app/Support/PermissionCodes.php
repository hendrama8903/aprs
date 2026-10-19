<?php

namespace App\Support;

/**
 * Harus sama dengan permissions.permission_code di database (lihat DatabaseSeeder).
 * Konvensi: <PREFIX>-ADD / <PREFIX>-EDIT / <PREFIX>-DELETE, dipakai Controller::crudPermissions().
 * Tambahkan konstanta baru di sini setiap membuat master baru.
 */
final class PermissionCodes
{
    // ---------- Master Data ----------
    public const DepartmentAdd = 'DEPARTMENT-ADD';
    public const DepartmentEdit = 'DEPARTMENT-EDIT';
    public const DepartmentDelete = 'DEPARTMENT-DELETE';

    // ---------- OGS Data ----------
    public const OgsImportUpload = 'OGSIMPORT-UPLOAD';
    public const SupplierEdit = 'SUPPLIER-EDIT';

    // ---------- Price Confirmation ----------
    public const AprAdd = 'APR-ADD';
    public const AprEdit = 'APR-EDIT';
    public const AprDelete = 'APR-DELETE';
    public const AprIssue = 'APR-ISSUE';
    public const ApprovalSettingEdit = 'APPROVALSETTING-EDIT';

    // ---------- Administration ----------
    public const UserAdd = 'USER-ADD';
    public const UserEdit = 'USER-EDIT';
    public const UserDelete = 'USER-DELETE';
    public const UserResetPassword = 'USER-RESETPASSWORD';

    public const RoleAdd = 'ROLE-ADD';
    public const RoleEdit = 'ROLE-EDIT';
    public const RoleDelete = 'ROLE-DELETE';
    public const RoleAccess = 'ROLE-ACCESS';

    public const MenuAdd = 'MENU-ADD';
    public const MenuEdit = 'MENU-EDIT';
    public const MenuDelete = 'MENU-DELETE';
}
