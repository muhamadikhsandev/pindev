<?php

use Illuminate\Database\Migrations\Migration;
// Baris Blueprint dihapus dari sini
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};