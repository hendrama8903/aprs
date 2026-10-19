<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Keep me signed in" di halaman login: kalau cookie KEEP_COOKIE ada, umur session diperpanjang
 * (session biasa memakai SESSION_LIFETIME). Harus jalan sebelum StartSession, karena StartSession
 * memakai session.lifetime untuk masa berlaku cookie session dan pengecekan kedaluwarsa.
 * Cookie hanya memperpanjang session yang memang sudah login; tanpa login cookie ini tidak berarti apa-apa.
 */
class KeepSignedIn
{
    public const KEEP_COOKIE = 'apr_keep_signed_in';
    public const KEEP_MINUTES = 60 * 24 * 7;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookies->has(self::KEEP_COOKIE)) {
            config(['session.lifetime' => self::KEEP_MINUTES]);
        }

        return $next($request);
    }
}
