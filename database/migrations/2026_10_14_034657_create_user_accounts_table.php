<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 50)->unique();
            $table->string('full_name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('employee_no', 20)->nullable();
            $table->foreignId('department_id')->nullable()->references('department_id')->on('departments')->nullOnDelete();
            $table->foreignId('role_id')->references('role_id')->on('roles')->restrictOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->references('user_id')->on('user_accounts')->nullOnDelete();
            $table->boolean('is_ad_user')->default(false);
            $table->string('password_hash', 256)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
