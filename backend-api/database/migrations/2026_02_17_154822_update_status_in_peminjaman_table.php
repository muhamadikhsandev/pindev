<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah kolom status jadi TEXT dulu sementara supaya bisa nampung tulisan baru
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status TEXT");

        // 2. Sekarang kita bebas update datanya tanpa dilarang ENUM
        DB::table('peminjaman')
            ->where('status', 'Menunggu')
            ->update(['status' => 'Menunggu Admin']);

        // 3. Kembalikan ke ENUM dengan pilihan yang sudah diperbarui
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM(
            'Menunggu Admin', 
            'Menunggu Petugas', 
            'Disetujui', 
            'Dipinjam', 
            'Dikembalikan', 
            'Ditolak'
        ) NOT NULL DEFAULT 'Menunggu Admin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kebalikannya: jadikan TEXT dulu
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status TEXT");

        // Kembalikan datanya
        DB::table('peminjaman')
            ->whereIn('status', ['Menunggu Admin', 'Menunggu Petugas'])
            ->update(['status' => 'Menunggu']);

        // Balikin ke ENUM lama
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM(
            'Menunggu', 
            'Disetujui', 
            'Dipinjam', 
            'Dikembalikan', 
            'Ditolak'
        ) NOT NULL DEFAULT 'Menunggu'");
    }
};