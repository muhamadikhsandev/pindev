<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// WAJIB TAMBAHKAN IMPORT DI BAWAH INI:
use Illuminate\Database\Eloquent\Relations\HasMany; 

class Jurusan extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara manual
    protected $table = 'jurusan';

    // Kolom yang boleh diisi mass-assignment
    protected $fillable = [
        'nama_jurusan',
        'kode_jurusan',
    ];

    /**
     * Relasi ke model User
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'jurusan_id');
    }

    /**
     * Relasi ke model Kelas
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'jurusan_id');
    }
}