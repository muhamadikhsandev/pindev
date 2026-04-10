<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKondisiAlat extends Model
{
    protected $table = 'riwayat_kondisi_alat';
    protected $fillable = ['alat_id','kondisi','keterangan','tanggal'];

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }
}
