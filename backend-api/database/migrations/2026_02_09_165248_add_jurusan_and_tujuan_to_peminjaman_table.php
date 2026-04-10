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
    Schema::table('peminjaman', function (Blueprint $table) {
        // Hapus baris jurusan karena sudah ada di database
        // Cukup tambahkan tujuan saja
        if (!Schema::hasColumn('peminjaman', 'tujuan')) {
            $table->text('tujuan')->after('user_id')->nullable(); 
        }
    });
}

public function down(): void
{
    Schema::table('peminjaman', function (Blueprint $table) {
        $table->dropColumn('tujuan');
    });
}
};
