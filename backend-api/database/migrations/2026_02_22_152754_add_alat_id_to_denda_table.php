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
        Schema::table('denda', function (Blueprint $table) {
            // Ditambahkan nullable() karena tidak semua denda (misal: telat) terikat ke 1 alat spesifik
            $table->foreignId('alat_id')->nullable()->constrained('alat')->nullOnDelete()->after('kategori_denda_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('denda', function (Blueprint $table) {
            $table->dropForeign(['alat_id']);
            $table->dropColumn('alat_id');
        });
    }
};