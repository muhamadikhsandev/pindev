<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // FIX: Tambahkan Eloquent di sini
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'jurusan_id'
    ];

    /**
     * Relasi Balik: Kelas dimiliki oleh satu Jurusan
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    /**
     * Relasi: Satu kelas punya banyak Siswa (User)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_id');
    }
}