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
        Schema::dropIfExists('foto_alat');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('foto_alat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alat_id');
            $table->string('foto_path');
            $table->timestamps();

            // Opsional: Tambahkan foreign key lagi jika sebelumnya ada
            $table->foreign('alat_id')->references('id')->on('alats')->onDelete('cascade');
        });
    }
};