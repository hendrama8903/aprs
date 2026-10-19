# HMA — Master Template Admin (Laravel 12)

Template admin panel untuk aplikasi **data master** (CRUD), diturunkan dari proyek SIP.
Sudah berisi: login, layout admin + sidebar dinamis, hak akses menu & permission per role,
modul User, Role & Access, Menu Master, notifikasi, export Excel, dan **Department** sebagai contoh master standar.

> Template ini khusus master data. Pola transaksi (header-detail, post/cancel) sengaja tidak disertakan.

## Setup

```bash
composer install
cp .env.example .env          # sesuaikan APP_NAME, APP_DESCRIPTION, DB_*
php artisan key:generate
# buat database MySQL sesuai DB_DATABASE, lalu:
php artisan migrate --seed
php artisan serve
```

Login awal: **admin / Admin@123** (wajib diganti setelah login pertama).

Nama aplikasi (judul tab, sidebar, breadcrumb, halaman login) diambil dari `APP_NAME` dan `APP_DESCRIPTION` di `.env`.

## Struktur

| Lapisan | Lokasi | Keterangan |
|---|---|---|
| Route | `routes/web.php` | Path PascalCase `/{Master}/{Action}`, harus sama dengan `menus.menu_url` |
| Controller | `app/Http/Controllers` | Extend `Controller` (helper `crudPermissions`, `saveByMode`, `deleteMany`, `jsonData`, `exportExcel`) |
| Repository | `app/Repositories` | Query + validasi bisnis, mengembalikan object PascalCase / `DBResult` |
| Model | `app/Models` | Eloquent, primary key `<nama>_id`, kolom audit `created_by/updated_by` |
| View | `resources/views/<master>` | `index`, `_search-criteria`, `_popup-form`, `_scripts` |
| JS helper | `public/js/hma.js` | `HMA.crudPage`, `HMA.createGrid`, `HMA.col.*`, `HMA.post`, `HMA.select2` |
| Hak akses | `app/Http/Middleware/MenuAccess.php`, `app/Support/PermissionCodes.php` | Akses menu + permission `<PREFIX>-ADD/EDIT/DELETE` |

## Membuat master baru (contoh: Supplier)

Salin pola **Department** — semua file contoh diberi tanda `CONTOH MASTER STANDAR`.

1. **Migration** — `php artisan make:migration create_suppliers_table`
   Ikuti `create_departments_table`: `id('supplier_id')`, kode unik, nama, `is_active`, kolom audit.
2. **Model** — salin `app/Models/Department.php` → `Supplier.php` (ganti `$table`, `$primaryKey`).
3. **Repository** — salin `app/Repositories/DepartmentRepo.php` → `SupplierRepo.php`
   (`search`, `getByKey`, `insert`, `update`, `delete`, `mapXxx`).
4. **Controller** — salin `DepartmentController.php` → `SupplierController.php`
   (ganti view, prefix permission `'SUPPLIER'`, field `$request->only([...])`, kolom export).
5. **PermissionCodes** — tambah `SupplierAdd/Edit/Delete` di `app/Support/PermissionCodes.php`.
6. **Route** — salin blok group Department di `routes/web.php`, ganti `/Department/` → `/Supplier/`.
7. **View** — salin folder `resources/views/department` → `supplier`:
   - `_search-criteria`: label tombol Add + filter tambahan.
   - `_popup-form`: field form.
   - `_scripts`: konfigurasi `HMA.crudPage` (entity, key, urls, columns, `resetForm`/`fillForm`/`collectForm`).
8. **Menu & permission** — tambahkan di `DatabaseSeeder` (lihat blok Department) lalu `php artisan db:seed`,
   **atau** lewat aplikasi: *Administration → Menu Master* (tambah menu + permission), lalu
   *Role & Access → Set Access* untuk role yang boleh mengakses.
9. *(Opsional)* Kalau master dipakai sebagai dropdown, tambahkan endpoint di `LookupController` + route `/Lookup/Supplier`,
   lalu panggil `HMA.select2("#field", "{{ url('/Lookup/Supplier') }}", "Select supplier", "#modalAddEdit")`.

User harus login ulang setelah hak akses role diubah (menu & permission disimpan di session saat login).
