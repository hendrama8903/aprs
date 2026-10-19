<?php

namespace App\Providers;

use App\Support\SessionAuth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Port dari Common/Filters/DynamicTitleFilter.cs: judul & breadcrumb halaman diambil dari
        // menu (session) berdasarkan path URL aktif, jadi rename menu di database otomatis ikut
        // ke judul halaman tanpa perlu diset manual di tiap controller.
        View::composer('layouts.admin', function ($view) {
            $path = '/' . trim(request()->path(), '/');
            // Rute akar "/" (link logo sidebar) secara kanonik adalah Home/Index.
            if ($path === '/') {
                $path = '/Home/Index';
            }
            $menus = SessionAuth::getMenus();
            $menu = null;
            foreach ($menus as $m) {
                if (strcasecmp($m->MenuUrl ?? '', $path) === 0) {
                    $menu = $m;
                    break;
                }
            }

            $parentTitle = null;
            if ($menu !== null) {
                foreach ($menus as $m) {
                    if (($m->MenuId ?? null) === $menu->ParentMenuId) {
                        $parentTitle = $m->MenuName;
                        break;
                    }
                }
            }

            $view->with('title', $menu->MenuName ?? $view->getData()['title'] ?? '');
            $view->with('parentTitle', $parentTitle);
        });
    }
}
