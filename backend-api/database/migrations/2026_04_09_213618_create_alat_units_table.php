<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('alat_units', function (Blueprint $table) {
        $table->id();
        $table->foreignId('alat_id')->constrained('alat')->onDelete('cascade');
        $table->string('kode_unit')->unique(); // Contoh: HDMI-001
        $table->string('kondisi'); 
        $table->string('status'); // TERSEDIA, DIPINJAM, PERBAIKAN, HILANG
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alat_units');
    }
};
