<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit trail: siapa mengubah apa & kapan. old_values / new_values = field yang berubah (JSON).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('audit_id');
            $table->timestamp('log_date')->useCurrent();
            $table->string('username', 50)->nullable();
            $table->string('action', 20);
            $table->string('module', 50);
            $table->string('record_key', 50)->nullable();
            $table->string('record_label', 150)->nullable();
            $table->string('description', 500)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->index('log_date');
            $table->index(['module', 'record_key']);
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
