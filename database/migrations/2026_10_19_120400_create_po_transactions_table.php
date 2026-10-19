<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Baris PO dari export OGS. Di-upsert per (po_no, po_line), jadi export per periode boleh tumpang tindih.
        // Dipakai untuk qty pembelian dan statistik harga (rata-rata, min, max, terakhir) di cost study APR.
        Schema::create('po_transactions', function (Blueprint $table) {
            $table->id('po_transaction_id');
            $table->string('po_no', 30);
            $table->integer('po_line');
            $table->date('po_date');
            $table->string('item_code', 20);
            $table->foreignId('item_id')->nullable()->references('item_id')->on('items')->nullOnDelete();
            $table->foreignId('supplier_id')->references('supplier_id')->on('suppliers')->restrictOnDelete();
            $table->string('currency', 3);
            $table->decimal('qty', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('amount', 20, 4);
            $table->foreignId('import_id')->references('import_id')->on('ogs_imports')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['po_no', 'po_line']);
            $table->index(['item_code', 'supplier_id', 'po_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_transactions');
    }
};
