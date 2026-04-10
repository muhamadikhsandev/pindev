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
    Schema::create('kategori_denda', function (Blueprint $table) {
        $table->id();
        $table->enum('nama_kategori', ['TELAT', 'RUSAK_RINGAN', 'RUSAK_BERAT', 'HILANG']);
        $table->enum('metode_denda', ['PER_HARI', 'SEKALI_BAYAR', 'PERSENTASE']);
        $table->decimal('nilai_denda', 10, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_denda');
    }
};
