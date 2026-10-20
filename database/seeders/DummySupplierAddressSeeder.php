<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Alamat surat dummy untuk supplier dummy hasil upload file OGS dummy (php artisan apr:dummy-ogs).
 * Dicocokkan per supplier_code; supplier yang belum ada dilewati. Aman dijalankan berulang.
 *
 *   php artisan db:seed --class=DummySupplierAddressSeeder
 */
class DummySupplierAddressSeeder extends Seeder
{
    /** supplier_code => [[label, address, attention, title, email], ...]; baris pertama = default. */
    private const Addresses = [
        '1001' => [
            ['Head Office', "Jl. Raya Bekasi Km. 21 No. 8\nPulogadung, Jakarta Timur 13920", 'Mr. Budi Santoso', 'Marketing Manager', 'sales@abadisentosa-dummy.co.id'],
            ['Plant Cikarang', "Kawasan Industri Jababeka II Blok C-12\nCikarang Utara, Bekasi 17530", 'Mr. Agus Wibowo', 'Plant Manager', 'plant@abadisentosa-dummy.co.id'],
        ],
        '1002' => [
            ['Head Office', "Jl. Industri Selatan 5 Blok GG No. 3\nKawasan Jababeka, Cikarang 17550", 'Mrs. Siti Rahayu', 'Director', 'info@cahayateknik-dummy.co.id'],
        ],
        '1003' => [
            ['Head Office', "Jl. Gatot Subroto Kav. 12\nKuningan, Jakarta Selatan 12930", 'Mr. Iwan Suriana', 'Director', 'iwan@mandiriprima-dummy.co.id'],
            ['Plant Karawang', "Kawasan Industri KIIC Lot B-7\nTelukjambe Timur, Karawang 41361", 'Mr. Dedi Kurniawan', 'Sales Supervisor', 'karawang@mandiriprima-dummy.co.id'],
            ['Warehouse Cibitung', "Kawasan MM2100 Blok J-3\nCibitung, Bekasi 17520", 'Mr. Rudi Hartono', 'Warehouse Head', null],
        ],
        '1004' => [
            ['Head Office', "Jl. Daan Mogot Km. 14 No. 30\nCengkareng, Jakarta Barat 11730", 'Mrs. Linda Wijaya', 'Sales Manager', 'linda@sejahterautama-dummy.co.id'],
        ],
        '1005' => [
            ['Head Office', "Jl. Raya Serang Km. 18\nCikupa, Tangerang 15710", 'Mr. Hendro Saputra', 'Marketing Director', 'marketing@jayamakmur-dummy.co.id'],
            ['Branch Surabaya', "Jl. Rungkut Industri III No. 45\nRungkut, Surabaya 60293", 'Mr. Yusuf Hidayat', 'Branch Manager', 'surabaya@jayamakmur-dummy.co.id'],
        ],
        '1006' => [
            ['Main', "Jl. Pahlawan No. 77\nKarawang Barat, Karawang 41311", 'Mr. Slamet Riyadi', 'Owner', null],
        ],
        '1007' => [
            ['Head Office', "Jl. Pulo Lentut No. 10\nKawasan Industri Pulogadung, Jakarta 13920", 'Mrs. Dewi Lestari', 'Account Manager', 'dewi@sinarabadi-dummy.co.id'],
        ],
        '1008' => [
            ['Main', "Jl. Raya Narogong Km. 9 No. 15\nBantargebang, Bekasi 17151", 'Mr. Fajar Nugroho', 'Director', 'karyamandiri.dummy@example.com'],
        ],
        '2001' => [
            ['Head Office', "2-3-1 Nihonbashi, Chuo-ku\nTokyo 103-0027, Japan", 'Mr. Takeshi Yamamoto', 'Sales Manager', 'yamamoto@nippon-dummy.example.jp'],
            ['Jakarta Representative', "Menara Dummy Lt. 15, Jl. Jend. Sudirman Kav. 1\nJakarta Pusat 10220", 'Mr. Kenji Sato', 'Chief Representative', 'jakarta@nippon-dummy.example.jp'],
        ],
        '2002' => [
            ['Head Office', "10 Anson Road #20-01\nSingapore 079903", 'Ms. Grace Tan', 'Regional Sales Director', 'grace.tan@globaldummy.example.sg'],
        ],
    ];

    public function run(): void
    {
        $by = 'seeder';
        $suppliers = Supplier::query()->whereIn('supplier_code', array_keys(self::Addresses))->pluck('supplier_id', 'supplier_code');

        foreach (self::Addresses as $code => $rows) {
            $supplierId = $suppliers[$code] ?? null;
            if ($supplierId === null) {
                continue;
            }
            // Supplier yang sudah punya default (diisi manual) tidak diberi default baru.
            $hasDefault = SupplierAddress::query()->where('supplier_id', $supplierId)->where('is_default', true)->exists();

            foreach ($rows as $i => [$label, $address, $attention, $title, $email]) {
                SupplierAddress::firstOrCreate(['supplier_id' => $supplierId, 'label' => $label], [
                    'address' => $address,
                    'attention_name' => $attention,
                    'attention_title' => $title,
                    'email' => $email,
                    'is_default' => $i === 0 && !$hasDefault,
                    'is_active' => true,
                    'created_by' => $by,
                ]);
            }
        }

        // Price master dummy hanya memakai plant 01, jadi plant 01 = alamat default.
        SupplierAddress::query()
            ->whereIn('supplier_id', $suppliers->values())
            ->where('is_default', true)
            ->whereNull('plant_code')
            ->whereNotExists(fn ($q) => $q->from('supplier_addresses as other')
                ->whereColumn('other.supplier_id', 'supplier_addresses.supplier_id')
                ->where('other.plant_code', '01'))
            ->update(['plant_code' => '01']);

        // Dokumen APR dummy yang belum punya alamat surat diberi snapshot alamat default supplier-nya.
        foreach (DB::table('aprs')->whereNull('letter_address')->get(['apr_id', 'supplier_id']) as $apr) {
            $default = SupplierAddress::query()->where('supplier_id', $apr->supplier_id)->where('is_default', true)->first();
            if ($default === null) {
                continue;
            }
            DB::table('aprs')->where('apr_id', $apr->apr_id)->update([
                'supplier_address_id' => $default->address_id,
                'letter_address_label' => $default->label,
                'letter_address' => $default->address,
                'letter_attention_name' => $default->attention_name,
                'letter_attention_title' => $default->attention_title,
            ]);
        }
    }
}
