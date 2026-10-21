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
    public const ItemAdd = 'ITEM-ADD';
    public const ItemEdit = 'ITEM-EDIT';
    public const ItemDelete = 'ITEM-DELETE';
    public const ItemUpload = 'ITEM-UPLOAD';
    public const ItemPriceAdd = 'ITEMPRICE-ADD';
    public const ItemPriceEdit = 'ITEMPRICE-EDIT';
    public const ItemPriceDelete = 'ITEMPRICE-DELETE';
    public const ItemPriceUpload = 'ITEMPRICE-UPLOAD';
    public const PoTransactionUpload = 'POTRANSACTION-UPLOAD';
    public const SupplierAdd = 'SUPPLIER-ADD';
    public const SupplierEdit = 'SUPPLIER-EDIT';
    public const SupplierDelete = 'SUPPLIER-DELETE';
    public const ExchangeRateAdd = 'EXCHANGERATE-ADD';
    public const ExchangeRateEdit = 'EXCHANGERATE-EDIT';
    public const ExchangeRateDelete = 'EXCHANGERATE-DELETE';

    // ---------- Price Confirmation ----------
    public const NewPriceEdit = 'NEWPRICE-EDIT';
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

    public const DocNumberingEdit = 'DOCNUMBERING-EDIT';
}
