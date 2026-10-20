<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalSettingController;
use App\Http\Controllers\AprController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemPriceController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NewPriceController;
use App\Http\Controllers\PoTransactionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Path ditulis PascalCase "/{Controller}/{Action}" dan HARUS sama persis dengan
| menus.menu_url (dipakai middleware MenuAccess, judul halaman, dan link sidebar).
| Satu master = satu group middleware('menu.access:/<Master>/Index') supaya semua
| endpoint AJAX-nya ikut terlindungi hak akses menu.
|
*/

Route::get('/', [HomeController::class, 'index'])->middleware('menu.access');
Route::get('/Home/Index', [HomeController::class, 'index'])->middleware('menu.access');

Route::get('/Auth/Login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/Auth/Validate', [AuthController::class, 'validateLogin']);
Route::post('/Auth/ChangePassword', [AuthController::class, 'changePassword'])->middleware('menu.access');
Route::get('/Auth/Logout', [AuthController::class, 'logout']);

// ---------- Master Data ----------

// Upload file export OGS ada di menu masing-masing (Item, Item Price, PO Transaction).

Route::middleware('menu.access:/Item/Index')->group(function () {
    Route::get('/Item/Index', [ItemController::class, 'index']);
    Route::post('/Item/Search', [ItemController::class, 'search']);
    Route::post('/Item/GetByKey', [ItemController::class, 'getByKey']);
    Route::post('/Item/AddEdit', [ItemController::class, 'addEdit']);
    Route::post('/Item/DeleteMultiple', [ItemController::class, 'deleteMultiple']);
    Route::post('/Item/Upload', [ItemController::class, 'upload']);
    Route::post('/Item/ImportDetail', [ItemController::class, 'importDetail']);
    Route::get('/Item/DownloadTemplate', [ItemController::class, 'downloadTemplate']);
    Route::get('/Item/DownloadExcel', [ItemController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/Supplier/Index')->group(function () {
    Route::get('/Supplier/Index', [SupplierController::class, 'index']);
    Route::post('/Supplier/Search', [SupplierController::class, 'search']);
    Route::post('/Supplier/GetByKey', [SupplierController::class, 'getByKey']);
    Route::post('/Supplier/AddEdit', [SupplierController::class, 'addEdit']);
    Route::post('/Supplier/DeleteMultiple', [SupplierController::class, 'deleteMultiple']);
    Route::get('/Supplier/DownloadExcel', [SupplierController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/ItemPrice/Index')->group(function () {
    Route::get('/ItemPrice/Index', [ItemPriceController::class, 'index']);
    Route::post('/ItemPrice/Search', [ItemPriceController::class, 'search']);
    Route::post('/ItemPrice/GetByKey', [ItemPriceController::class, 'getByKey']);
    Route::post('/ItemPrice/AddEdit', [ItemPriceController::class, 'addEdit']);
    Route::post('/ItemPrice/DeleteMultiple', [ItemPriceController::class, 'deleteMultiple']);
    Route::post('/ItemPrice/Upload', [ItemPriceController::class, 'upload']);
    Route::post('/ItemPrice/ImportDetail', [ItemPriceController::class, 'importDetail']);
    Route::get('/ItemPrice/DownloadTemplate', [ItemPriceController::class, 'downloadTemplate']);
    Route::get('/ItemPrice/DownloadExcel', [ItemPriceController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/PoTransaction/Index')->group(function () {
    Route::get('/PoTransaction/Index', [PoTransactionController::class, 'index']);
    Route::post('/PoTransaction/Search', [PoTransactionController::class, 'search']);
    Route::post('/PoTransaction/Upload', [PoTransactionController::class, 'upload']);
    Route::post('/PoTransaction/ImportDetail', [PoTransactionController::class, 'importDetail']);
    Route::get('/PoTransaction/DownloadTemplate', [PoTransactionController::class, 'downloadTemplate']);
    Route::get('/PoTransaction/DownloadExcel', [PoTransactionController::class, 'downloadExcel']);
});

// ---------- Price Confirmation ----------

Route::middleware('menu.access:/NewPrice/Index')->group(function () {
    Route::get('/NewPrice/Index', [NewPriceController::class, 'index']);
    Route::post('/NewPrice/Search', [NewPriceController::class, 'search']);
    Route::post('/NewPrice/Save', [NewPriceController::class, 'save']);
    Route::post('/NewPrice/Upload', [NewPriceController::class, 'upload']);
    Route::get('/NewPrice/DownloadExcel', [NewPriceController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/Apr/Index')->group(function () {
    Route::get('/Apr/Index', [AprController::class, 'index']);
    Route::post('/Apr/Search', [AprController::class, 'search']);
    Route::post('/Apr/CandidateItems', [AprController::class, 'candidateItems']);
    Route::post('/Apr/Save', [AprController::class, 'save']);
    Route::post('/Apr/Delete', [AprController::class, 'delete']);
    Route::post('/Apr/Submit', [AprController::class, 'submit']);
    Route::post('/Apr/Issue', [AprController::class, 'issue']);
});

// Dibuka dari APR List maupun Approval Inbox; akses menu dicek di AprController::canView().
Route::middleware('menu.access')->group(function () {
    Route::get('/Apr/Form', [AprController::class, 'form']);
    Route::post('/Apr/GetByKey', [AprController::class, 'getByKey']);
    Route::get('/Apr/PrintApr', [AprController::class, 'printApr']);
    Route::get('/Apr/PrintLetter', [AprController::class, 'printLetter']);
});

Route::middleware('menu.access:/Approval/Index')->group(function () {
    Route::get('/Approval/Index', [ApprovalController::class, 'index']);
    Route::post('/Approval/Search', [ApprovalController::class, 'search']);
    Route::post('/Approval/History', [ApprovalController::class, 'history']);
    Route::post('/Approval/Approve', [AprController::class, 'approve']);
    Route::post('/Approval/Reject', [AprController::class, 'reject']);
});

Route::middleware('menu.access:/ApprovalSetting/Index')->group(function () {
    Route::get('/ApprovalSetting/Index', [ApprovalSettingController::class, 'index']);
    Route::post('/ApprovalSetting/Search', [ApprovalSettingController::class, 'search']);
    Route::post('/ApprovalSetting/GetByKey', [ApprovalSettingController::class, 'getByKey']);
    Route::post('/ApprovalSetting/AddEdit', [ApprovalSettingController::class, 'addEdit']);
});

// ---------- Administration ----------

Route::middleware('menu.access:/Role/Index')->group(function () {
    Route::get('/Role/Index', [RoleController::class, 'index']);
    Route::post('/Role/Search', [RoleController::class, 'search']);
    Route::post('/Role/GetByKey', [RoleController::class, 'getByKey']);
    Route::post('/Role/AddEdit', [RoleController::class, 'addEdit']);
    Route::post('/Role/DeleteMultiple', [RoleController::class, 'deleteMultiple']);
    Route::post('/Role/GetAccess', [RoleController::class, 'getAccess']);
    Route::post('/Role/SaveAccess', [RoleController::class, 'saveAccess']);
    Route::get('/Role/DownloadExcel', [RoleController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/User/Index')->group(function () {
    Route::get('/User/Index', [UserController::class, 'index']);
    Route::post('/User/Search', [UserController::class, 'search']);
    Route::post('/User/GetByKey', [UserController::class, 'getByKey']);
    Route::post('/User/AddEdit', [UserController::class, 'addEdit']);
    Route::post('/User/ResetPassword', [UserController::class, 'resetPassword']);
    Route::post('/User/DeleteMultiple', [UserController::class, 'deleteMultiple']);
    Route::get('/User/DownloadExcel', [UserController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/Menu/Index')->group(function () {
    Route::get('/Menu/Index', [MenuController::class, 'index']);
    Route::post('/Menu/Search', [MenuController::class, 'search']);
    Route::post('/Menu/AddEdit', [MenuController::class, 'addEdit']);
    Route::post('/Menu/Delete', [MenuController::class, 'delete']);
    Route::get('/Menu/DownloadExcel', [MenuController::class, 'downloadExcel']);
});

// ---------- Shared (cukup login) ----------

Route::middleware('menu.access')->group(function () {
    Route::get('/Lookup/Role', [LookupController::class, 'role']);
    Route::get('/Lookup/Item', [LookupController::class, 'item']);
    Route::get('/Lookup/Supplier', [LookupController::class, 'supplier']);
    Route::get('/Lookup/SupplierAddress', [LookupController::class, 'supplierAddress']);
    Route::get('/Lookup/UserList', [LookupController::class, 'userList']);

    Route::post('/Notification/List', [NotificationController::class, 'list']);
    Route::post('/Notification/MarkRead', [NotificationController::class, 'markRead']);
});
