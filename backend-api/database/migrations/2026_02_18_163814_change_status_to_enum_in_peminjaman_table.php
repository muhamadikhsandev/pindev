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
        Schema::table('peminjaman', function (Blueprint $table) {
            // Kita gunakan raw query agar lebih aman saat mengubah tipe data kolom yang sudah ada
            $enumList = "'Menunggu Petugas', 'Menunggu Admin', 'Disetujui', 'Dipinjam', 'Menunggu Pengecekan', 'Bermasalah', 'Dikembalikan', 'Ditolak'";
            
            DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM($enumList) DEFAULT 'Menunggu Petugas'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            // Kembalikan ke string jika di-rollback
            $table->string('status')->change();
        });
    }
};