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
        Schema::create('riwayat_kondisi_alat', function (Blueprint $table) {
    $table->id();
    $table->foreignId('alat_id')->constrained('alat')->cascadeOnDelete();
    $table->enum('kondisi', ['Baik', 'Rusak']);
    $table->string('keterangan')->nullable();
    $table->date('tanggal');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_kondisi_alat');
    }
};
