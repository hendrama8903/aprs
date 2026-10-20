<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kurs ke IDR (rate = IDR per 1 unit mata uang) per periode berlaku. Periode satu mata uang tidak boleh tumpang tindih.
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id('exchange_rate_id');
            $table->string('currency', 3);
            $table->decimal('rate', 18, 4);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('remark', 255)->nullable();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['currency', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
