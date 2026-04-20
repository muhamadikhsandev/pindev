<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. REGISTRASI MIDDLEWARE ALIAS
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // 2. CEGAH REDIRECT LOGIN UNTUK API
        $middleware->redirectTo(
            guests: fn (Request $request) => $request->is('api/*') ? null : '/login'
        );

        // 3. FIX CSRF: Izinkan rute-rute auth di web.php tanpa token CSRF (Penting buat Next.js)
        $middleware->validateCsrfTokens(except: [
            '/login',
            '/register',
            '/logout',
            '/lupa-sandi',
            '/login/google',
            '/email/*',          // Wildcard untuk semua verifikasi email
            '/proses-*',         // Wildcard untuk aktivasi akun
            '/simpan-*',
            '/cek-identitas',
        ]);

        // 4. AKTIFKAN SANCTUM STATEFUL
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // 5. PAKSA SEMUA REQUEST API BALIKIN JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*') || $request->is('login') || $request->is('logout')) {
                return true;
            }
            return $request->expectsJson();
        });

        // 6. HANDLING: DATABASE MATI
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') || $request->is('login')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database Error: Gagal terhubung ke database. Cek Laragon!',
                ], 500);
            }
        });

        // 7. HANDLING: BELUM LOGIN (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated: Sesi berakhir atau Anda belum login.',
                ], 401);
            }
        });

        // 8. HANDLING: ROLE TIDAK SESUAI (403)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: Role Anda tidak diizinkan mengakses resource ini.',
                ], 403);
            }
        });

        // 9. HANDLING: URL TIDAK ADA (404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not Found: Endpoint API tidak ditemukan.',
                ], 404);
            }
        });

        // 10. HANDLING: ERROR UMUM / CSRF MISMATCH (500)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->is('login')) {
                $isCsrf = str_contains($e->getMessage(), 'CSRF');
                
                return response()->json([
                    'success' => false,
                    'message' => $isCsrf ? 'Security Error: Token CSRF Mismatch.' : 'Internal Server Error.',
                    'debug'   => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        });

    })->create();