<?php

namespace App\Models;



class KategoriDenda extends BaseModel
{
    

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