<?php

namespace App\Providers;

use App\Models\Alat;
use App\Models\User;
use App\Models\AlatUnit; // Import model AlatUnit
use App\Models\Peminjaman;
use App\Observers\GlobalActionObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 1. Registrasi Observer untuk Master Alat
        Alat::observe(GlobalActionObserver::class);

        // 2. Registrasi Observer untuk Unit Alat (Penting untuk track kondisi fisik)
        AlatUnit::observe(GlobalActionObserver::class);

        // 3. Registrasi Observer untuk User/Pengguna
        User::observe(GlobalActionObserver::class);
        
        // 4. Registrasi Observer untuk Transaksi Peminjaman (Opsional: Cek jika class ada)
        if (class_exists(Peminjaman::class)) {
            Peminjaman::observe(GlobalActionObserver::class);
        }
    }
}