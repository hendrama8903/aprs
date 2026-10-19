<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalSettingController;
use App\Http\Controllers\AprController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemPriceController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OgsImportController;
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

Route::middleware('menu.access:/Department/Index')->group(function () {
    Route::get('/Department/Index', [DepartmentController::class, 'index']);
    Route::post('/Department/Search', [DepartmentController::class, 'search']);
    Route::post('/Department/GetByKey', [DepartmentController::class, 'getByKey']);
    Route::post('/Department/AddEdit', [DepartmentController::class, 'addEdit']);
    Route::post('/Department/DeleteMultiple', [DepartmentController::class, 'deleteMultiple']);
    Route::get('/Department/DownloadExcel', [DepartmentController::class, 'downloadExcel']);
});

// ---------- OGS Data ----------

Route::middleware('menu.access:/OgsImport/Index')->group(function () {
    Route::get('/OgsImport/Index', [OgsImportController::class, 'index']);
    Route::post('/OgsImport/Search', [OgsImportController::class, 'search']);
    Route::post('/OgsImport/GetByKey', [OgsImportController::class, 'getByKey']);
    Route::post('/OgsImport/Upload', [OgsImportController::class, 'upload']);
    Route::get('/OgsImport/DownloadTemplate', [OgsImportController::class, 'downloadTemplate']);
});

Route::middleware('menu.access:/Item/Index')->group(function () {
    Route::get('/Item/Index', [ItemController::class, 'index']);
    Route::post('/Item/Search', [ItemController::class, 'search']);
    Route::get('/Item/DownloadExcel', [ItemController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/Supplier/Index')->group(function () {
    Route::get('/Supplier/Index', [SupplierController::class, 'index']);
    Route::post('/Supplier/Search', [SupplierController::class, 'search']);
    Route::post('/Supplier/GetByKey', [SupplierController::class, 'getByKey']);
    Route::post('/Supplier/AddEdit', [SupplierController::class, 'addEdit']);
    Route::get('/Supplier/DownloadExcel', [SupplierController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/ItemPrice/Index')->group(function () {
    Route::get('/ItemPrice/Index', [ItemPriceController::class, 'index']);
    Route::post('/ItemPrice/Search', [ItemPriceController::class, 'search']);
    Route::get('/ItemPrice/DownloadExcel', [ItemPriceController::class, 'downloadExcel']);
});

// ---------- Price Confirmation ----------

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
    Route::get('/Lookup/Department', [LookupController::class, 'department']);
    Route::get('/Lookup/Role', [LookupController::class, 'role']);
    Route::get('/Lookup/Supplier', [LookupController::class, 'supplier']);
    Route::get('/Lookup/UserList', [LookupController::class, 'userList']);

    Route::post('/Notification/List', [NotificationController::class, 'list']);
    Route::post('/Notification/MarkRead', [NotificationController::class, 'markRead']);
});
