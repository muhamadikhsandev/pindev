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
            // Menambahkan kolom kelas_id, posisinya setelah jurusan_id
            $table->unsignedBigInteger('kelas_id')->nullable()->after('jurusan_id');
            
            // Membuat relasi foreign key ke tabel kelas (Opsional tapi sangat disarankan)
            // Jika tabel kelas dihapus, maka user di kelas tersebut set kelas_id nya jadi null
            $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key dan kolomnya jika di-rollback
            $table->dropForeign(['kelas_id']);
            $table->dropColumn('kelas_id');
        });
    }
};