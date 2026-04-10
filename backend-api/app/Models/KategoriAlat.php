<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriAlat extends Model
{
    use HasFactory;

    // Nama tabel sesuai migration
    protected $table = 'kategori_alat';

    // Kolom yang boleh diisi mass assignment
    protected $fillable = [
        'nama_kategori',
    ];

    // =========================
    // RELATIONSHIPS (opsional)
    // =========================

    // Kalau nanti Kategori punya banyak Alat
    // public function alats()
    // {
    //     return $this->hasMany(Alat::class, 'kategori_id');
    // }
}
