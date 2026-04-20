<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk MENGHAPUS tabel.
     */
    public function up(): void
    {
        Schema::dropIfExists('jadwal_peminjaman');
    }

    /**
     * Jalankan migrasi untuk MEMBATALKAN penghapusan (Opsional).
     * Ini buat jaga-jaga kalau lo mau rollback.
     */
    public function down(): void
    {
        Schema::create('jadwal_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alat_id');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();
        });
    }
};