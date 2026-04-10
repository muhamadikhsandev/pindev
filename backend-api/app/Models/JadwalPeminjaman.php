<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPeminjaman extends Model
{
    protected $table = 'jadwal_peminjaman';
    protected $fillable = ['alat_id','tanggal','jam_mulai','jam_selesai'];

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }
}

