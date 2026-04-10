<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pakai raw statement karena doctrine DBAL kadang rewel kalau ngubah ENUM
        DB::statement("ALTER TABLE pengembalian MODIFY COLUMN kondisi_kembali ENUM('Belum Dicek', 'Baik', 'Bermasalah') DEFAULT 'Belum Dicek'");
        
        // Opsional: Kalau sebelumnya ada data 'Rusak', kita update otomatis jadi 'Bermasalah' biar datanya nggak error
        DB::table('pengembalian')
            ->where('kondisi_kembali', 'Rusak') // Nilai lama
            ->update(['kondisi_kembali' => 'Bermasalah']); // Nilai baru
    }

    public function down(): void
    {
        // Kembalikan ke semula kalau di-rollback
        DB::statement("ALTER TABLE pengembalian MODIFY COLUMN kondisi_kembali ENUM('Baik', 'Rusak') DEFAULT 'Baik'");
    }
};