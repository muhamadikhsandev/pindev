<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alat_units', function (Blueprint $table) {

            // ✅ index satuan
            $table->index('alat_id');
            $table->index('status');
            $table->index('kondisi');

            // 🔥 index kombinasi (PALING NGARUH)
            $table->index(['alat_id', 'status', 'kondisi'], 'idx_alat_status_kondisi');
        });
    }

    public function down(): void
    {
        Schema::table('alat_units', function (Blueprint $table) {

            $table->dropIndex(['alat_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['kondisi']);

            $table->dropIndex('idx_alat_status_kondisi');
        });
    }
};