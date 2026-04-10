<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <--- WAJIB ADA

return new class extends Migration
{
    public function up(): void
    {
        // Kita tambahin 'Disetujui' dan 'Ditolak'
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('Menunggu', 'Disetujui', 'Dipinjam', 'Dikembalikan', 'Ditolak') DEFAULT 'Menunggu'");
    }

    public function down(): void
    {
        // Balikin ke kondisi awal lo (via tinker tadi)
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('Menunggu', 'Dipinjam', 'Dikembalikan') DEFAULT 'Menunggu'");
    }
};