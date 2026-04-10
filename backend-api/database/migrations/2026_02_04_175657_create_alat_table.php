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
        Schema::create('alat', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kategori_id')->constrained('kategori_alat')->cascadeOnDelete();
    $table->foreignId('satuan_id')->constrained('satuan_alat')->cascadeOnDelete();
    $table->string('nama_alat');
    $table->integer('stok');
    $table->enum('kondisi', ['Baik', 'Rusak'])->default('Baik');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alat');
    }
};
