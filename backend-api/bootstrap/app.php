<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '', // Sesuai keinginan kamu: Tanpa prefix
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        // 🔥 FIX: Kecualikan semua route dari CSRF karena ini murni API untuk Next.js
        $middleware->validateCsrfTokens(except: [
            '*', 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();