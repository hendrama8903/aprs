<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Export price master OGS tidak lagi punya kolom ACTIVE: harga current ditentukan dari
        // Time Control (tc_from - tc_to) terhadap tanggal dokumen APR, jadi tc wajib dan is_active dihapus.
        // Index baru dibuat dulu: FK supplier_id butuh index yang diawali supplier_id sebelum index lama dihapus.
        Schema::table('item_prices', function (Blueprint $table) {
            $table->index(['supplier_id', 'item_code', 'tc_from']);
        });
        Schema::table('item_prices', function (Blueprint $table) {
            $table->dropIndex(['supplier_id', 'is_active']);
            $table->dropColumn('is_active');
        });

        // Snapshot sumber current price di baris APR (periode & quotation), supaya status
        // Valid / Expired tetap bisa ditampilkan walaupun price master sudah diganti.
        Schema::table('apr_details', function (Blueprint $table) {
            $table->date('price_tc_from')->nullable()->after('current_price');
            $table->date('price_tc_to')->nullable()->after('price_tc_from');
            $table->string('price_quotation', 50)->nullable()->after('price_tc_to');
        });
    }

    public function down(): void
    {
        Schema::table('apr_details', function (Blueprint $table) {
            $table->dropColumn(['price_tc_from', 'price_tc_to', 'price_quotation']);
        });

        Schema::table('item_prices', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('ogs_update_date');
            $table->index(['supplier_id', 'is_active']);
        });
        Schema::table('item_prices', function (Blueprint $table) {
            $table->dropIndex(['supplier_id', 'item_code', 'tc_from']);
        });
    }
};
