<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. Tambahkan ini
use Illuminate\Database\Eloquent\Model;

class KategoriDenda extends Model
{
    use HasFactory; // 2. Tambahkan ini di dalam class

    protected $table = 'kategori_denda';

    protected $fillable = [
        'nama_kategori',
        'metode_denda',
        'nilai_denda',
    ];

    protected $casts = [
        'nilai_denda' => 'float',
    ];
}