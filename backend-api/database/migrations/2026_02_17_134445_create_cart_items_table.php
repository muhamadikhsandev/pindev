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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            
            // Menghubungkan ke tabel users (Laravel otomatis cari tabel 'users')
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Menghubungkan ke tabel 'alat' (Tanpa 's' sesuai struktur tabelmu)
            $table->foreignId('alat_id')->constrained('alat')->cascadeOnDelete();
            
            $table->integer('qty')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};