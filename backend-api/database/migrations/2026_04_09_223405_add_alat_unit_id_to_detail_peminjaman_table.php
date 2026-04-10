<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('detail_peminjaman', function (Blueprint $table) {
            // Kita jadikan nullable dulu untuk mencegah error jika ada data lama yang belum punya unit
            $table->unsignedBigInteger('alat_unit_id')->nullable()->after('alat_id');

            // Tambahkan foreign key
            $table->foreign('alat_unit_id')
                  ->references('id')
                  ->on('alat_units')
                  ->onDelete('set null'); // Jika unit dihapus, historinya tetap ada tapi nilainya null
        });
    }

    public function down()
    {
        Schema::table('detail_peminjaman', function (Blueprint $table) {
            $table->dropForeign(['alat_unit_id']);
            $table->dropColumn('alat_unit_id');
        });
    }
};