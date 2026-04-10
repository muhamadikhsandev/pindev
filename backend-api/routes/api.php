<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\AlatUnitController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\PengembalianController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DendaController; 
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\KategoriAlatController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\PetugasDashboardController;
use App\Http\Controllers\Api\SatuanAlatController;
use App\Http\Controllers\Api\KategoriDendaController;
use App\Http\Controllers\Api\JurusanController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\AktivasiController;
use Illuminate\Http\Request;

// ==========================================
// --- 1. PUBLIC ROUTES (Tanpa Autentikasi) ---
// ==========================================

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = \App\Models\User::findOrFail($id);
    $validHash = sha1($user->getEmailForVerification());
    
    if (!hash_equals((string) $hash, $validHash)) {
        return response()->json(['success' => false, 'message' => 'Link tidak valid atau hash salah.'], 403);
    }
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));
    }
    return response()->json(['success' => true, 'message' => 'Email berhasil diverifikasi!']);
})->name('verification.verify');

Route::get('/login', fn() => response()->json(['message' => 'Unauthorized. Please login from the frontend.'], 401))->name('login');
Route::get('/ping', fn() => response()->json(['ok' => true]));

// Auth & Aktivasi
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/cek-nis', [AktivasiController::class, 'cekNis']);
Route::post('/proses-aktivasi', [AktivasiController::class, 'aktivasi']);
Route::post('/simpan-password', [AktivasiController::class, 'simpanPassword']);
Route::post('/login/google', [AuthController::class, 'googleLogin']);
// Public Katalog (FIXED: Kategori diarahkan ke KategoriAlatController)
Route::get('/alat', [AlatController::class, 'index']);
Route::get('/kategori-alat', [KategoriAlatController::class, 'index']); 
Route::get('/satuan-alat', [SatuanAlatController::class, 'index']); 

// Verification
Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verify'])->name('api.verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resend'])->name('verification.send');


// ==========================================
// --- 2. PROTECTED ROUTES (Harus Login) ---
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & User Profile
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user-profile', [ProfileController::class, 'show']);
    Route::put('/user-profile/update', [ProfileController::class, 'update']);
    Route::put('/user-profile/password', [ProfileController::class, 'updatePassword']);
    
    // Dashboards
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/petugas/dashboard', [PetugasDashboardController::class, 'index']);
    Route::get('/peminjam/dashboard', [DashboardController::class, 'index']);

Route::get('/user-profile', [App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::post('/user-profile/update', [App\Http\Controllers\Api\ProfileController::class, 'update']);


    // Manajemen Siswa/Peminjam
    Route::get('/jurusan-options', [UserController::class, 'getJurusanOptions']);
    Route::get('/users/jurusan', [UserController::class, 'getJurusanOptions']);
    Route::post('/users/bulk-destroy', [UserController::class, 'bulkDestroy']);
    Route::post('/users/import', [UserController::class, 'importExcel']);
    Route::apiResource('/users', UserController::class);

    Route::post('/peminjam/bulk-destroy', [UserController::class, 'bulkDestroy']);
    Route::post('/peminjam/import', [UserController::class, 'importExcel']);
    Route::apiResource('/peminjam', UserController::class); 

    // Manajemen Akademik
    Route::post('/kelas/bulk-delete', [KelasController::class, 'bulkDestroy']);
    Route::post('/kelas/import', [KelasController::class, 'import']);
    Route::apiResource('/kelas', KelasController::class);

    Route::post('/jurusan/bulk-destroy', [JurusanController::class, 'bulkDestroy']);
    Route::apiResource('/jurusan', JurusanController::class);

    // ==========================================
    // MANAJEMEN ALAT (Master, Unit, Kategori, Satuan)
    // ==========================================
    
    // Master Alat
    Route::get('/alat/kondisi-options', [AlatController::class, 'getKondisiOptions']);
    Route::post('/alat/import', [AlatController::class, 'import']);
    Route::post('/alat/bulk-destroy', [AlatController::class, 'bulkDestroy']);
    Route::post('/alat', [AlatController::class, 'store']);
    Route::get('/alat/{id}', [AlatController::class, 'show']);
    Route::put('/alat/{id}', [AlatController::class, 'update']);
    Route::delete('/alat/{id}', [AlatController::class, 'destroy']);
    
    // Alat Unit (Fisik)
    Route::apiResource('alat-unit', AlatUnitController::class);

    // Kategori Alat (Method index() sudah di public, jadi di-except disini)
    Route::post('/kategori-alat/bulk-destroy', [KategoriAlatController::class, 'bulkDestroy']);
    Route::apiResource('/kategori-alat', KategoriAlatController::class)->except(['index']);

    // Satuan Alat (Method index() sudah di public, jadi di-except disini)
    Route::post('/satuan-alat/bulk-destroy', [SatuanAlatController::class, 'bulkDestroy']);
    Route::apiResource('satuan-alat', SatuanAlatController::class)->except(['index']);


    // ==========================================
    // KERANJANG & TRANSAKSI
    // ==========================================
    Route::delete('/cart/clear/all', [CartItemController::class, 'clear']);
    Route::apiResource('/cart', CartItemController::class)->except(['show']);

    Route::post('/peminjaman/bulk-destroy', [PeminjamanController::class, 'bulkDestroy']);
    Route::post('/peminjaman/verify-scan', [PeminjamanController::class, 'verifyByCode']);
    Route::put('/peminjaman/{id}/status', [PeminjamanController::class, 'updateStatus']);
    Route::get('/riwayat', [PeminjamanController::class, 'riwayat']);
    // Route Export
    Route::get('peminjaman/export', [PeminjamanController::class, 'export']);
    Route::apiResource('/peminjaman', PeminjamanController::class);

    Route::get('/riwayat-pengembalian', [PengembalianController::class, 'riwayatPengembalianUser']);
    Route::get('/pengembalian/options', [PengembalianController::class, 'getPeminjamanOptions']);
    Route::get('/pengembalian-list', [PengembalianController::class, 'listSemua']);
    Route::post('/pengembalian/bulk-destroy', [PengembalianController::class, 'bulkDestroy']);

Route::get('pengembalian/export', [PengembalianController::class, 'export']);
// WAJIB: Letakkan Route /options DI ATAS apiResource
Route::get('pengembalian/options', [PengembalianController::class, 'getOptions']);
Route::apiResource('pengembalian', PengembalianController::class);

// ==========================================
    // DENDA & KEUANGAN
    // ==========================================
    Route::post('/kategori-denda/bulk-destroy', [KategoriDendaController::class, 'bulkDestroy']);
    Route::apiResource('/kategori-denda', KategoriDendaController::class);

    // FIX ROUTING DENDA (CUSTOM ROUTES HARUS DI ATAS API RESOURCE)
    Route::get('/denda/options', [\App\Http\Controllers\Api\DendaController::class, 'getOptions']);
    Route::post('/denda/bulk-destroy', [\App\Http\Controllers\Api\DendaController::class, 'bulkDestroy']);
    Route::get('/denda/saya', [\App\Http\Controllers\Api\DendaController::class, 'dendaSaya']); // Diubah sedikit URL-nya agar lebih rapi
    Route::post('/denda/{id}/lunas', [\App\Http\Controllers\Api\DendaController::class, 'updateStatus']); 
    // Letakkan DI ATAS Route::apiResource('denda', ...)
Route::get('denda/export', [DendaController::class, 'export']);

    // API Resource denda HARUS di bagian paling bawah kelompok denda
    Route::apiResource('/denda', \App\Http\Controllers\Api\DendaController::class);
});