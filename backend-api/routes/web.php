<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

// ==========================================
// 🌍 RUTE DASAR
// ==========================================
Route::get('/', function () {
    return response()->json(['message' => 'Pindev Backend API is Running 🚀']);
});

// ==========================================
// 🔐 PUBLIC AUTH ROUTES (Belum Login)
// ==========================================
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/google', [AuthController::class, 'googleLogin']);
Route::post('/lupa-sandi', [AuthController::class, 'forgotPassword']);
Route::get('/reset-sandi/{token}', fn($token) => response()->json(['message' => 'Silakan reset melalui frontend.']))->name('password.reset');

// ==========================================
// 📧 VERIFIKASI EMAIL
// ==========================================
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);
    $validHash = sha1($user->getEmailForVerification());
    
    if (!hash_equals((string) $hash, $validHash)) {
        return response()->json(['success' => false, 'message' => 'Link tidak valid atau hash salah.'], 403);
    }
    
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }
    return response()->json(['success' => true, 'message' => 'Email berhasil diverifikasi!']);
})->name('verification.verify');

Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verify'])->name('api.verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resend'])->name('verification.send');

// ==========================================
// 🚪 PROTECTED AUTH ROUTES (Harus Login)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});