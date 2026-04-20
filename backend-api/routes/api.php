<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// 📦 IMPORTS SEMUA CONTROLLER
// ==========================================
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\AlatUnitController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\PengembalianController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DendaController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\KategoriAlatController;
use App\Http\Controllers\Api\SatuanAlatController;
use App\Http\Controllers\Api\KategoriDendaController;
use App\Http\Controllers\Api\JurusanController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\AktivasiController;
use App\Http\Controllers\Api\Peminjam\DashboardController as PeminjamDashboard;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Api\Petugas\DashboardController as PetugasDashboard;
use App\Http\Controllers\Api\Admin\LogAktivitasController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\Petugas\PengembalianController as PetugasPengembalian;

// ==========================================
// 🌍 1. PUBLIC ROUTES (Tanpa Autentikasi)
// ==========================================
Route::get('/ping', fn() => response()->json(['ok' => true]));

// Katalog Publik
Route::get('/alat', [AlatController::class, 'index']);
Route::get('/kategori-alat', [KategoriAlatController::class, 'index']);
Route::get('/satuan-alat', [SatuanAlatController::class, 'index']);
Route::get('/alat/kondisi-options', [AlatController::class, 'getKondisiOptions']);

// Aktivasi Akun
Route::post('/cek-identitas', [AktivasiController::class, 'cekIdentitas']);
Route::post('/proses-aktivasi', [AktivasiController::class, 'aktivasi']);
Route::post('/simpan-password', [AktivasiController::class, 'simpanPassword']);


// ==========================================
// 🔒 2. PROTECTED ROUTES (Harus Login)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // ------------------------------------------
    // 👤 AKSES UMUM (Semua Role Bisa Akses)
    // ------------------------------------------
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/user-profile', [ProfileController::class, 'show']);
    Route::post('/user-profile/update', [ProfileController::class, 'update']);
    Route::put('/user-profile/password', [ProfileController::class, 'updatePassword']);
    Route::get('/jurusan-options', [UserController::class, 'getJurusanOptions']);
    Route::get('/users/jurusan', [UserController::class, 'getJurusanOptions']);

    // ------------------------------------------
    // 👑 KHUSUS ADMIN
    // ------------------------------------------
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboard::class, 'index']);
        
        Route::post('alat/import', [AlatController::class, 'import']);
        // Log Aktivitas
        Route::get('/logs', [LogAktivitasController::class, 'index']);
        Route::get('/logs/{id}', [LogAktivitasController::class, 'show']);

        // Manajemen User & Staff
        Route::post('/users/bulk-destroy', [UserController::class, 'bulkDestroy']);
        Route::post('/users/import', [UserController::class, 'importExcel']);
        Route::apiResource('/users', UserController::class);

        Route::post('/user/staff/bulk-destroy', [StaffController::class, 'bulkDestroy']);
        Route::apiResource('/user/staff', StaffController::class);

        // 🔥 FIX: Ubah prefix dari /peminjam jadi /user/peminjam biar gak nabrak dashboard peminjam
        Route::post('/user/peminjam/bulk-destroy', [UserController::class, 'bulkDestroy']);
        Route::post('/user/peminjam/import', [UserController::class, 'importExcel']);
        Route::apiResource('/user/peminjam', UserController::class);

        // Manajemen Akademik
        Route::post('/kelas/bulk-delete', [KelasController::class, 'bulkDestroy']);
        Route::post('/kelas/import', [KelasController::class, 'import']);
        Route::apiResource('/kelas', KelasController::class);

        Route::post('/jurusan/bulk-destroy', [JurusanController::class, 'bulkDestroy']);
        Route::apiResource('/jurusan', JurusanController::class);
    });

    // ------------------------------------------
    // 🛠️ KHUSUS PETUGAS & ADMIN
    // ------------------------------------------
    Route::middleware('role:petugas,admin')->group(function () {
        Route::get('/petugas/dashboard', [PetugasDashboard::class, 'index']);

        // Manajemen Alat (Master, Unit, Kategori, Satuan)
        Route::post('/alat/import', [AlatController::class, 'import']);
        Route::post('/alat/bulk-destroy', [AlatController::class, 'bulkDestroy']);
        Route::post('/alat', [AlatController::class, 'store']);
        Route::get('/alat/{id}', [AlatController::class, 'show']);
        Route::put('/alat/{id}', [AlatController::class, 'update']);
        Route::delete('/alat/{id}', [AlatController::class, 'destroy']);
        
        Route::apiResource('alat-unit', AlatUnitController::class);
        
        Route::post('/kategori-alat/bulk-destroy', [KategoriAlatController::class, 'bulkDestroy']);
        Route::apiResource('/kategori-alat', KategoriAlatController::class)->except(['index']);
        
        Route::post('/satuan-alat/bulk-destroy', [SatuanAlatController::class, 'bulkDestroy']);
        Route::apiResource('satuan-alat', SatuanAlatController::class)->except(['index']);

        // Manajemen Peminjaman (Admin & Petugas view)
        Route::post('/peminjaman/bulk-destroy', [PeminjamanController::class, 'bulkDestroy']);
        Route::post('/peminjaman/verify-scan', [PeminjamanController::class, 'verifyByCode']);
        Route::put('/peminjaman/{id}/status', [PeminjamanController::class, 'updateStatus']);
        Route::get('peminjaman/export', [PeminjamanController::class, 'export']);
        Route::apiResource('/peminjaman', PeminjamanController::class)->except(['store']); // Store khusus peminjam

        // Manajemen Pengembalian
        Route::get('/pengembalian-list', [PengembalianController::class, 'listSemua']);
        Route::post('/pengembalian/bulk-destroy', [PengembalianController::class, 'bulkDestroy']);
        Route::get('pengembalian/export', [PengembalianController::class, 'export']);
        Route::get('pengembalian/options', [PengembalianController::class, 'getOptions']);
        Route::apiResource('pengembalian', PengembalianController::class);

        // Pengembalian Khusus Petugas Prefix
        Route::prefix('petugas')->group(function () {
            Route::get('/pengembalian', [PetugasPengembalian::class, 'index']);
            Route::post('/pengembalian', [PetugasPengembalian::class, 'store']);
        });

        // Manajemen Denda & Keuangan
        Route::post('/kategori-denda/bulk-destroy', [KategoriDendaController::class, 'bulkDestroy']);
        Route::apiResource('/kategori-denda', KategoriDendaController::class);

        Route::get('/denda/options', [DendaController::class, 'getOptions']);
        Route::post('/denda/bulk-destroy', [DendaController::class, 'bulkDestroy']);
        Route::post('/denda/{id}/lunas', [DendaController::class, 'updateStatus']);
        Route::get('denda/export', [DendaController::class, 'export']);
        Route::apiResource('/denda', DendaController::class);
    });

    // ------------------------------------------
    // 🎒 KHUSUS PEMINJAM (Siswa/Mahasiswa)
    // ------------------------------------------
    Route::middleware('role:peminjam')->group(function () {
        Route::get('/peminjam/dashboard', [PeminjamDashboard::class, 'index']);

        // Keranjang
        Route::delete('/cart/clear/all', [CartItemController::class, 'clear']);
        Route::apiResource('/cart', CartItemController::class)->except(['show']);

        // Transaksi Mandiri
        Route::post('/peminjaman', [PeminjamanController::class, 'store']); // Create peminjaman
        Route::get('/riwayat', [PeminjamanController::class, 'riwayat']);
        Route::get('/riwayat-pengembalian', [PengembalianController::class, 'riwayatPengembalianUser']);
        Route::get('/denda-saya', [DendaController::class, 'dendaSaya']);
    });
});