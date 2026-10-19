<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu', function (Blueprint $table) {
            $table->foreignId('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
            $table->foreignId('menu_id')->references('menu_id')->on('menus')->cascadeOnDelete();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['role_id', 'menu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu');
    }
};
