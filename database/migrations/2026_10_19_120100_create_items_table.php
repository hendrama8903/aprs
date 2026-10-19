<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salinan OGS item master, di-upsert per item_code saat import.
        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('item_code', 20)->unique();
            $table->string('item_name', 150);
            $table->string('unit', 20)->nullable();
            $table->string('category', 50)->nullable()->index();
            $table->string('minimum_order', 20)->nullable();
            $table->string('tool_number', 100)->nullable();
            $table->string('drawing_number', 100)->nullable();
            $table->date('tc_from')->nullable();
            $table->date('tc_to')->nullable();
            $table->string('remark', 255)->nullable();
            $table->foreignId('last_import_id')->nullable()->references('import_id')->on('ogs_imports')->nullOnDelete();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
