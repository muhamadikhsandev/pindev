<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPeminjaman extends Model
{
    protected $table = 'detail_peminjaman';
    // Tambahkan alat_unit_id ke fillable
    protected $fillable = ['peminjaman_id', 'alat_id', 'alat_unit_id', 'jumlah'];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }

    // Tambahkan relasi ke AlatUnit
    public function unit()
    {
        return $this->belongsTo(AlatUnit::class, 'alat_unit_id');
    }
}