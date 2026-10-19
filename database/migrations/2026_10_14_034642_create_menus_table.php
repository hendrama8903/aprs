<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id('menu_id');
            $table->foreignId('parent_menu_id')->nullable()->references('menu_id')->on('menus')->nullOnDelete();
            $table->enum('menu_type', ['MENU', 'SCREEN'])->default('SCREEN');
            $table->string('menu_name', 100);
            $table->string('menu_url', 200)->nullable()->unique();
            $table->string('menu_icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
