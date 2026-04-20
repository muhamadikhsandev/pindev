<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
   public function handle(Request $request, Closure $next, ...$roles): Response
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
    }

    // AUTO-DETECT KOLOM:
    // Kita coba ambil dari kolom 'role', kalau nggak ada ambil dari 'status_peminjam'
    $rawRole = $user->role ?? $user->status_peminjam ?? '';
    $userRole = strtolower(trim($rawRole));

    $allowedRoles = array_map(fn($r) => strtolower(trim($r)), $roles);

    if (!in_array($userRole, $allowedRoles)) {
        return response()->json([
            'success' => false,
            'message' => "Forbidden: Role '{$userRole}' tidak diizinkan.",
            'debug' => [
                'detected_role' => $userRole,
                'required' => $allowedRoles
            ]
        ], 403);
    }

    return $next($request);
}
}