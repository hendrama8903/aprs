<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Urutan approval APR. Satu approver tetap per tahap, bisa diganti lewat menu Approval Setting.
        // sign_group menentukan kolom tanda tangan di form: CHECKED atau APPROVED.
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id('step_id');
            $table->integer('step_no')->unique();
            $table->string('step_code', 10)->unique();
            $table->string('step_name', 50);
            $table->enum('sign_group', ['CHECKED', 'APPROVED']);
            $table->foreignId('approver_user_id')->nullable()->references('user_id')->on('user_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
