<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kode & nama dari OGS price master; alamat dan attention diisi di APR untuk surat Price Confirmation.
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('supplier_code', 10)->unique();
            $table->string('supplier_name', 100);
            $table->string('address', 255)->nullable();
            $table->string('attention_name', 100)->nullable();
            $table->string('attention_title', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
