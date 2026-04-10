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
    Schema::table('users', function (Blueprint $table) {
        // Kita timpa kolom status yang lama dengan opsi yang baru
        $table->enum('status', ['aktif', 'nonaktif', 'lulus', 'resign'])
              ->default('aktif')
              ->change();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Balikin ke awal kalau di-rollback
        $table->enum('status', ['aktif', 'nonaktif'])
              ->default('aktif')
              ->change();
    });
}
};
