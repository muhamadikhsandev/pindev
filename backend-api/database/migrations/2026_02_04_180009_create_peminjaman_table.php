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
        Schema::create('peminjaman', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();     // peminjam
    $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();  // petugas
    $table->date('tanggal_pinjam');
    $table->date('tanggal_rencana_kembali');
    $table->enum('status', ['Menunggu', 'Dipinjam', 'Dikembalikan'])->default('Menunggu');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
