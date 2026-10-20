<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga baru yang disiapkan di menu Set New Price, per item + supplier + periode berlaku harga baru.
        // Current price disalin dari item_prices (harga Valid di awal periode) saat disimpan, supaya tetap ada
        // walaupun price master di-upload ulang. status disiapkan untuk tahap berikutnya (dipakai di APR).
        Schema::create('new_prices', function (Blueprint $table) {
            $table->id('new_price_id');
            $table->string('item_code', 20);
            $table->foreignId('item_id')->nullable()->references('item_id')->on('items')->nullOnDelete();
            $table->string('item_name', 150);
            $table->string('unit', 20)->nullable();
            $table->foreignId('supplier_id')->references('supplier_id')->on('suppliers')->restrictOnDelete();
            $table->string('currency', 3);
            $table->foreignId('item_price_id')->nullable()->references('item_price_id')->on('item_prices')->nullOnDelete();
            $table->decimal('current_price', 18, 4);
            $table->date('current_tc_from');
            $table->date('current_tc_to');
            $table->string('current_quotation', 50)->nullable();
            $table->decimal('new_price', 18, 4);
            $table->date('new_tc_from');
            $table->date('new_tc_to');
            $table->string('remark', 255)->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['item_code', 'supplier_id', 'new_tc_from']);
            $table->index(['new_tc_from', 'new_tc_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_prices');
    }
};
