<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Denda extends Model
{
    use HasFactory;

    protected $table = 'denda';

    const STATUS_BELUM_BAYAR = 'belum_bayar';
    const STATUS_LUNAS       = 'lunas';

    protected $fillable = [
        'pengembalian_id',
        'kategori_denda_id',
        'alat_id',
        'jumlah_denda',
        'keterangan',
        'status'
    ];

    public function pengembalian()
    {
        return $this->belongsTo(Pengembalian::class, 'pengembalian_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriDenda::class, 'kategori_denda_id');
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }
}