<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kita gunakan enum agar data lebih konsisten sesuai daftar yang Anda berikan
            $table->enum('jurusan', [
                'Pengembangan Perangkat Lunak dan Gim',
                'Animasi',
                'Broadcasting dan Perfilman',
                'Teknik Otomotif',
                'Teknik Pengelasan dan Fabrikasi Logam'
            ])->nullable()->after('nis'); // diletakkan setelah kolom NIS
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jurusan');
        });
    }
};