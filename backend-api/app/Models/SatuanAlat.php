<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuanAlat extends Model
{
    protected $table = 'satuan_alat';
    protected $fillable = ['nama_satuan'];

    public function alat()
    {
        return $this->hasMany(Alat::class, 'satuan_id');
    }
}

