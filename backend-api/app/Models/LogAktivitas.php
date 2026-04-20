<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LogAktivitas extends Model
{
    // Nama tabel sesuai migration
    protected $table = 'log_aktivitas';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'user_id', 
        'aksi', 
        'deskripsi', 
        'loggable_id', 
        'loggable_type', 
        'data_lama', 
        'data_baru', 
        'ip_address', 
        'user_agent'
    ];

    /**
     * Casting otomatis: JSON dari DB langsung jadi Array PHP
     * Ini penting biar di Frontend Next.js datanya rapih
     */
    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke User: Siapa yang melakukan aksi?
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi Polymorphic: Log ini milik data apa?
     * (Bisa milik Alat, Peminjaman, Pengembalian, dll)
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}