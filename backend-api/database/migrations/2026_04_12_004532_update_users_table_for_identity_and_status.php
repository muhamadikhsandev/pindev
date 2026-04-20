<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Tambah nomor_identitas kalau belum ada
            if (!Schema::hasColumn('users', 'nomor_identitas')) {
                $table->string('nomor_identitas')->nullable();
            }

            // Tambah status_peminjam kalau belum ada
            if (!Schema::hasColumn('users', 'status_peminjam')) {
                $table->enum('status_peminjam', ['GURU', 'SISWA'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'status_peminjam')) {
                $table->dropColumn('status_peminjam');
            }

            if (Schema::hasColumn('users', 'nomor_identitas')) {
                $table->dropColumn('nomor_identitas');
            }
        });
    }
};