# APR — Approval Price Confirmation

Aplikasi untuk membuat, meng-approve, dan menerbitkan **Price Confirmation** harga supplier.
Dibangun dari template [TemplateAdminHMA](https://github.com/hendrama8903/TemplateAdminHMA) (Laravel 12, DevExtreme, MySQL).

Alur singkat:

1. Admin/Staff PUD meng-upload export **OGS** (item master, price master, transaksi PO). Data hanya mengalir OGS → APR.
2. Staff PUD membuat dokumen **APR**: pilih supplier, pilih item. Harga current diambil dari price master; qty dan
   statistik harga PO (rata-rata, min, max, terakhir) dihitung dari transaksi PO pada *purchase period*.
3. Submit → approval berjenjang sesuai **Approval Setting**: Department Head → Advisor → Senior Advisor → Division Head → Director.
   Reject di tahap mana pun mengembalikan dokumen ke pembuat; setelah direvisi, approval dimulai lagi dari tahap pertama.
4. Setelah approved, Staff PUD **Issue Letter** → sistem memberi nomor surat dan membuat PDF surat Price Confirmation + lampiran.

Status dokumen: `DRAFT → IN_APPROVAL → APPROVED → ISSUED` (atau `REJECTED` → revisi → submit ulang).

## Setup

```bash
composer install
cp .env.example .env          # sesuaikan DB_*, APR_COMPANY_* (lihat config/apr.php)
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Data dummy

Repository **tidak** menyimpan data OGS asli. Untuk development/demo pakai file dummy di `database/dummy/`
(dibuat ulang dengan `php artisan apr:dummy-ogs`), lalu upload dengan urutan:
item master → price master → PO transaction, masing-masing lewat tombol **Upload** di menu
*Master Data → Item*, *Item Price*, dan *PO Transaction*.
Setelah itu isi alamat surat dummy supplier: `php artisan db:seed --class=DummySupplierAddressSeeder`.

Seeder membuat user `admin` (password di `DatabaseSeeder`, wajib diganti) dan user **dummy** untuk tiap peran
(staff PUD, 5 approver, viewer Finance) — daftar username & password ada di docblock `database/seeders/DatabaseSeeder.php`.
Hapus atau nonaktifkan user dummy di production.

Folder `docs/` (file asli dari user) di-ignore Git.

## Menu & role

| Menu | ADMIN | PUD_STAFF | APPROVER | VIEWER |
|---|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ | ✓ |
| Price Confirmation → Set New Price | ✓ | ✓ (edit/upload) | | |
| Price Confirmation → APR List | ✓ | ✓ (add/edit/delete draft/issue) | lihat | lihat |
| Price Confirmation → Approval Inbox | ✓ | | ✓ | |
| Master Data → Item / Item Price | ✓ | ✓ (add/edit/delete/upload) | Item Price (lihat) | |
| Master Data → Supplier | ✓ | ✓ (add/edit/delete, alamat surat) | | |
| Master Data → PO Transaction | ✓ | ✓ (upload) | | |
| Administration → User, Role, Menu, **Approval Setting** | ✓ | | | |

Siapa yang approve ditentukan oleh **Approval Setting** (satu approver tetap per tahap), bukan oleh role.
Role approver hanya perlu punya menu Approval Inbox.

**Set New Price**: pilih periode harga baru → muncul semua item + supplier yang punya harga **Valid di awal periode**
(current price). New Price & Remark diisi di grid, lewat *Adjust %* untuk baris terpilih, atau Export Excel → isi kolom
New Price → Upload. Disimpan per item + supplier + periode (current price ikut disalin); remark wajib kalau naik di atas
`APR_REMARK_THRESHOLD_PCT`, dan periode harga baru tidak boleh tumpang tindih untuk item + supplier yang sama.

## Konfigurasi (`config/apr.php` / `.env`)

| Key | Default | Keterangan |
|---|---|---|
| `APR_COMPANY_NAME`, `APR_COMPANY_ADDRESS`, `APR_COMPANY_CITY` | contoh | Kop form APR & surat |
| `APR_COMPANY_DEPARTMENT` | `PUD` | Dipakai di nomor dokumen |
| `APR_REMARK_THRESHOLD_PCT` | `5` | Kenaikan harga di atas % ini wajib remark |
| `APR_DOC_NO_INTERNAL` / `APR_DOC_NO_EXTERNAL` | `{no}/PC-INT/{dept}/{roman}/{yy}` | Format nomor; urut per tahun |
| `APP_TIMEZONE` | `Asia/Jakarta` | Tanggal OGS adalah waktu lokal |

## Format file OGS

Kolom dibaca berdasarkan **nama header** (urutan bebas):

- **Item master**: ITEM CODE, ITEM NAME, UNIT, CATEGORY (+ TOOL NUMBER, DRAWING NUMBER, TC FROM, TC TO, REMARK). Upsert per item code.
- **Price master**: ITEM CODE, ITEM NAME, SUPPLIER CODE, SUPPLIER NAME, CURRENCY, PRICE, TIME CONTROL FROM/TO (+ UNIT, SUPPLIER PLANT CODE, QUOTATION, INPUT/UPDATE BY/DATE). **Mengganti seluruh** data harga
  (dibatalkan kalau tidak ada satu pun baris valid). Tidak ada kolom ACTIVE: **current price** di APR = harga yang Time Control-nya
  mencakup tanggal dokumen; kalau tidak ada, harga terakhir yang sudah lewat dipakai dan ditandai *expired*.
  Baris dilewati kalau supplier code kosong, price bukan angka / ≤ 0, currency selain IDR/USD/JPY, atau Time Control kosong / From > To.
- **PO transaction** *(format usulan, sesuaikan saat export OGS asli tersedia)*: PO NO, PO LINE, PO DATE, ITEM CODE, SUPPLIER CODE, SUPPLIER NAME, CURRENCY, QTY, UNIT PRICE. Upsert per PO NO + PO LINE.

Temuan kualitas data (item tanpa nama, plant code tidak seragam, periode Time Control tumpang tindih, harga yang tidak berlaku hari ini, dll.) dicatat sebagai warning di riwayat import.

## Struktur kode

Pola template: Route PascalCase `/{Modul}/{Action}` → Controller → Repository (validasi & aturan bisnis, mengembalikan `DBResult`) → Model.

| Bagian | File utama |
|---|---|
| Import OGS | `app/Repositories/OgsImportRepo.php`, `app/Console/Commands/MakeDummyOgs.php` |
| Dokumen & workflow APR | `app/Repositories/AprRepo.php`, `app/Http/Controllers/AprController.php` |
| Approval Setting | `app/Repositories/ApprovalStepRepo.php` |
| Nomor dokumen | `app/Support/DocNumber.php` |
| Form & PDF | `resources/views/apr/` (`form`, `pdf-apr`, `pdf-letter`) |
