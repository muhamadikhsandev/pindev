<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. Tambahkan ini
class Pengembalian extends Model
{
    use HasFactory;
    
    protected $table = 'pengembalian';
    protected $fillable = ['peminjaman_id', 'tanggal_kembali', 'kondisi_kembali', 'catatan'];

    // Konstanta ENUM agar mudah diingat dan dipanggil di Controller
    public const KONDISI_BELUM_DICEK = 'Belum Dicek';
    public const KONDISI_BAIK = 'Baik';
    public const KONDISI_BERMASALAH = 'Bermasalah';

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function denda()
    {
        return $this->hasOne(Denda::class, 'pengembalian_id');
    }
}