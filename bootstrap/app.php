<?php

use App\Http\Middleware\KeepSignedIn;
use App\Http\Middleware\MenuAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'menu.access' => MenuAccess::class,
        ]);
        // Sebelum StartSession supaya umur session "Keep me signed in" ikut dipakai.
        $middleware->web(prepend: [KeepSignedIn::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
