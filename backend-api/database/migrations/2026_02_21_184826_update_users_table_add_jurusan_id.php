<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Hapus kolom 'jurusan' yang lama (string/enum)
            // Cek dulu apakah kolomnya ada, kalau ada kita hapus
            if (Schema::hasColumn('users', 'jurusan')) {
                $table->dropColumn('jurusan');
            }

            // 2. Tambah kolom 'jurusan_id' yang nyambung ke tabel 'jurusan'
            // Kita taruh setelah kolom 'nis' biar rapi
            $table->foreignId('jurusan_id')
                  ->nullable() // nullable kalau user boleh gak punya jurusan
                  ->after('nis') 
                  ->constrained('jurusan') // Merujuk ke tabel 'jurusan'
                  ->onDelete('set null');  // Kalau jurusannya dihapus, user.jurusan_id jadi NULL
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Balikin keadaan kalau di-rollback
            $table->dropForeign(['jurusan_id']);
            $table->dropColumn('jurusan_id');
            $table->string('jurusan')->nullable();
        });
    }
};