<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. DATA CLEANUP (Mencegah error jika ada data lama)
        // Peminjaman: 'Bermasalah' dipindahkan ke status alur 'Dikembalikan'
        DB::table('peminjaman')
            ->where('status', 'Bermasalah')
            ->update(['status' => 'Dikembalikan']);

        // Pengembalian: 'Belum Dicek' dianggap 'Baik' secara default sebelum dibersihkan
        DB::table('pengembalian')
            ->where('kondisi_kembali', 'Belum Dicek')
            ->update(['kondisi_kembali' => 'Baik']);


        // 2. UPDATE TABEL PEMINJAMAN
        Schema::table('peminjaman', function (Blueprint $table) {
            // Kita ubah kolom status untuk menghapus opsi 'Bermasalah'
            // Gunakan string agar lebih fleksibel dibanding enum
            $table->string('status')
                ->comment('Menunggu Petugas, Menunggu Admin, Disetujui, Dipinjam, Menunggu Pengecekan, Dikembalikan, Ditolak')
                ->change();
        });


        // 3. UPDATE TABEL PENGEMBALIAN
        Schema::table('pengembalian', function (Blueprint $table) {
            // Kita ubah enum kondisi_kembali untuk menghapus 'Belum Dicek'
            // Sekarang pilihannya hanya Baik atau Bermasalah
            $table->enum('kondisi_kembali', ['Baik', 'Bermasalah'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });

        Schema::table('pengembalian', function (Blueprint $table) {
            $table->string('kondisi_kembali')->nullable()->change();
        });
    }
};