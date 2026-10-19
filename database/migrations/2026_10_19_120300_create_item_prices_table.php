<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salinan OGS price master. Diganti penuh setiap import PRICE (file OGS = snapshot lengkap).
        // item_code/item_name disimpan apa adanya karena ada item di price master yang tidak ada di item master.
        Schema::create('item_prices', function (Blueprint $table) {
            $table->id('item_price_id');
            $table->string('item_code', 20)->index();
            $table->string('item_name', 150);
            $table->string('unit', 20)->nullable();
            $table->foreignId('item_id')->nullable()->references('item_id')->on('items')->nullOnDelete();
            $table->foreignId('supplier_id')->references('supplier_id')->on('suppliers')->restrictOnDelete();
            $table->string('supplier_plant_code', 5)->nullable();
            $table->string('currency', 3);
            $table->decimal('price', 18, 4);
            $table->string('quotation', 50)->nullable();
            $table->date('tc_from')->nullable();
            $table->date('tc_to')->nullable();
            $table->string('ogs_input_by', 50)->nullable();
            $table->dateTime('ogs_input_date')->nullable();
            $table->string('ogs_update_by', 50)->nullable();
            $table->dateTime('ogs_update_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('import_id')->references('import_id')->on('ogs_imports')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['supplier_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_prices');
    }
};
