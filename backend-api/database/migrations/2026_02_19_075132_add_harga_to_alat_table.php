<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            // Menggunakan decimal untuk harga agar lebih akurat (15 digit, 2 di belakang koma)
            $table->decimal('harga', 15, 2)->nullable()->after('kondisi');
        });
    }

    public function down(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            $table->dropColumn('harga');
        });
    }
};