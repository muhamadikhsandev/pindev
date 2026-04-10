<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Matikan strict mode sementara agar MySQL tidak protes data kotor
        DB::statement('SET SESSION sql_mode = ""');

        // 1. Bersihkan semua data lama secara total
        // Paksa semua baris (termasuk row 9 yang error) jadi 'BAIK'
        DB::table('alat')->update(['kondisi' => 'BAIK']);

        // 2. Ubah struktur kolom menjadi ENUM
        Schema::table('alat', function (Blueprint $table) {
            $table->enum('kondisi', [
                'BAIK', 
                'RUSAK_RINGAN', 
                'RUSAK_BERAT', 
                'HILANG', 
                'PERBAIKAN'
            ])->default('BAIK')->change();
        });
    }

    public function down(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            // Balikin ke string kalau di-rollback
            $table->string('kondisi')->default('Baik')->change();
        });
    }
};