<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class ExportAuth
{
    /**
     * Middleware khusus untuk mengizinkan akses download file via token di URL.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Cek apakah ada token di query string (?token=...)
        $tokenStr = $request->query('token');

        if ($tokenStr) {
            // 2. Cari token di database Sanctum
            $token = PersonalAccessToken::findToken($tokenStr);

            if ($token && $token->tokenable) {
                // 3. Login-kan user secara manual untuk request ini saja
                Auth::login($token->tokenable);
            } else {
                return response()->json(['message' => 'Token export tidak valid atau kadaluarsa.'], 401);
            }
        }

        // 4. Jika user tetap tidak terautentikasi (tidak ada header & tidak ada token URL valid)
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized. Silakan login kembali.'], 401);
        }

        return $next($request);
    }
}