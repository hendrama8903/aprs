<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plant code OGS (item_prices.supplier_plant_code) per alamat: satu lokasi = satu plant.
        // Belum dipakai untuk mencari harga; disiapkan kalau nanti harga dibedakan per plant.
        Schema::table('supplier_addresses', function (Blueprint $table) {
            $table->string('plant_code', 5)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_addresses', function (Blueprint $table) {
            $table->dropColumn('plant_code');
        });
    }
};
