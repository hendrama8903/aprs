<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menu "Import OGS" dihapus: upload file export OGS sekarang ada di menu masing-masing
     * (Item, Item Price, PO Transaction - menu & permission barunya dibuat DatabaseSeeder).
     * Hak akses role ke menu/permission lama ikut terhapus (FK cascade di role_menu / role_permission).
     */
    public function up(): void
    {
        $menuId = DB::table('menus')->where('menu_url', '/OgsImport/Index')->value('menu_id');
        if ($menuId !== null) {
            DB::table('permissions')->where('menu_id', $menuId)->delete();
            DB::table('menus')->where('menu_id', $menuId)->delete();
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: menu, controller, dan view Import OGS sudah dihapus dari kode.
    }
};
