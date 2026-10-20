<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - Master Department dihapus total (menu, permission, tabel, dan kolom department di user).
     * - Grup menu "OGS Data" (Item, Supplier, Item Price, PO Transaction) diganti nama jadi "Master Data";
     *   grup "Master Data" lama yang hanya berisi Department ikut dihapus.
     * Hak akses role ke menu/permission yang dihapus ikut terhapus (FK cascade di role_menu / role_permission).
     */
    public function up(): void
    {
        $departmentMenuId = DB::table('menus')->where('menu_url', '/Department/Index')->value('menu_id');
        if ($departmentMenuId !== null) {
            DB::table('permissions')->where('menu_id', $departmentMenuId)->delete();
            DB::table('menus')->where('menu_id', $departmentMenuId)->delete();
        }

        $oldMasterId = DB::table('menus')->whereNull('parent_menu_id')->where('menu_name', 'Master Data')->value('menu_id');
        if ($oldMasterId !== null && !DB::table('menus')->where('parent_menu_id', $oldMasterId)->exists()) {
            DB::table('menus')->where('menu_id', $oldMasterId)->delete();
        }

        DB::table('menus')->whereNull('parent_menu_id')->where('menu_name', 'OGS Data')
            ->update(['menu_name' => 'Master Data', 'menu_icon' => 'fa-database', 'updated_at' => now()]);

        Schema::table('user_accounts', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
        Schema::dropIfExists('departments');
    }

    public function down(): void
    {
        // Tidak dikembalikan: menu, controller, view, dan model Department sudah dihapus dari kode.
    }
};
