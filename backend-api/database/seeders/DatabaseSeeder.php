<?php

namespace Database\Seeders;

use App\Models\KategoriDenda;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat data Kategori Denda (Data Master)
        KategoriDenda::factory(5)->create();

        // 2. Buat 10 data Pengembalian
        // Tips: Menggunakan factory(10)->create() di sini otomatis akan 
        // membuat 10 data Peminjaman baru juga karena relasi di Factory-nya.
        Pengembalian::factory(10)->create();
    }
}