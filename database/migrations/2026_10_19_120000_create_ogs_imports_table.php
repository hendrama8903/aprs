<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log setiap upload file export OGS (data hanya mengalir OGS -> APR).
        Schema::create('ogs_imports', function (Blueprint $table) {
            $table->id('import_id');
            $table->enum('import_type', ['ITEM', 'PRICE', 'PO']);
            $table->string('file_name', 200);
            $table->integer('total_rows')->default(0);
            $table->integer('inserted_rows')->default(0);
            $table->integer('updated_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('warning_count')->default(0);
            $table->longText('warnings')->nullable();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ogs_imports');
    }
};
