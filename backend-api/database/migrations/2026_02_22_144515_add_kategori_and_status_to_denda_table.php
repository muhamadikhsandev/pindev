<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('denda', function (Blueprint $table) {
            // Tambah Foreign Key Kategori Denda
            $table->foreignId('kategori_denda_id')
                  ->after('pengembalian_id')
                  ->constrained('kategori_denda')
                  ->onDelete('cascade');

            // Tambah Enum Status (Hanya Belum Bayar & Lunas)
            $table->enum('status', ['belum_bayar', 'lunas'])
                  ->default('belum_bayar')
                  ->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('denda', function (Blueprint $table) {
            $table->dropForeign(['kategori_denda_id']);
            $table->dropColumn(['kategori_denda_id', 'status']);
        });
    }
};