<?php

namespace App\Http\Middleware;

use App\Support\SessionAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Port dari Common/Filters/MenuAccessAttribute.cs.
 * - Belum login        : halaman -> redirect ke Login, AJAX -> 401 JSON.
 * - Tidak punya menu    : halaman -> view errors.403, AJAX -> 403 JSON.
 * - Tanpa $menuUrl      : hanya mengecek login.
 *
 * Dipasang di route lewat ->middleware('menu.access:/Role/Index') agar berlaku untuk SEMUA
 * action/endpoint AJAX controller tersebut, supaya endpoint tidak bisa dipanggil langsung oleh
 * user yang tidak punya menunya.
 */
class MenuAccess
{
    public function handle(Request $request, Closure $next, ?string $menuUrl = null): Response
    {
        $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';

        if (!SessionAuth::isLoggedIn()) {
            if ($isAjax) {
                return response()->json([
                    'status' => false,
                    'sessionExpired' => true,
                    'message' => 'Your session has expired. Please login again.',
                ], 401);
            }

            return redirect()->guest(route('auth.login', ['returnUrl' => $request->getRequestUri()]));
        }

        if ($menuUrl !== null && !SessionAuth::hasMenu($menuUrl)) {
            if ($isAjax) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this menu.',
                ], 403);
            }

            return response()->view('errors.403', [], 403);
        }

        return $next($request);
    }
}
