<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga bisa ditambah / diedit manual di menu Item Price: baris manual tidak punya import_id,
        // dan jejak edit disimpan di created_by / updated_*. Upload price master tetap mengganti seluruh tabel.
        Schema::table('item_prices', function (Blueprint $table) {
            $table->foreignId('import_id')->nullable()->change();
            $table->string('created_by', 50)->nullable()->after('import_id');
            $table->string('updated_by', 50)->nullable()->after('created_at');
            $table->timestamp('updated_at')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('item_prices', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by', 'updated_at']);
        });
    }
};
