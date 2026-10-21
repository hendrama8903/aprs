<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Counter nomor dokumen bisa diatur di menu Document Numbering: catat siapa yang terakhir mengubah.
    public function up(): void
    {
        Schema::table('doc_counters', function (Blueprint $table) {
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('doc_counters', function (Blueprint $table) {
            $table->dropColumn(['updated_by', 'updated_at']);
        });
    }
};
