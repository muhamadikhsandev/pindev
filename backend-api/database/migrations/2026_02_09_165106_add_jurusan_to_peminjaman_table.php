<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            // Menggunakan Enum dengan Nama Lengkap sesuai permintaan
            $table->enum('jurusan', [
                'Pengembangan Perangkat Lunak dan Gim',
                'Animasi',
                'Broadcasting dan Perfilman',
                'Teknik Otomotif',
                'Teknik Pengelasan dan Fabrikasi Logam'
            ])->after('user_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn('jurusan');
        });
    }
};