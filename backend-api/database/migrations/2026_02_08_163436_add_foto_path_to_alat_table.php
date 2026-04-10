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
    Schema::table('alat', function (Blueprint $table) {
        // Kita taruh setelah kolom 'kondisi' dan buat nullable agar tidak error jika data lama kosong
        $table->string('foto_path')->nullable()->after('kondisi');
    });
}

public function down(): void
{
    Schema::table('alat', function (Blueprint $table) {
        $table->dropColumn('foto_path');
    });
}
};
