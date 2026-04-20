<?php

namespace App\Models;



class AlatUnit extends BaseModel
{
    protected $table = 'alat_units';

    // Konstanta Kondisi
    const KONDISI_BAIK = 'BAIK';
    const KONDISI_RUSAK_RINGAN = 'RUSAK_RINGAN';
    const KONDISI_RUSAK_BERAT = 'RUSAK_BERAT';
    const KONDISI_HILANG = 'HILANG';
    const KONDISI_PERBAIKAN = 'PERBAIKAN';

    // Konstanta Status Ketersediaan
    const STATUS_TERSEDIA = 'TERSEDIA';
    const STATUS_DIPINJAM = 'DIPINJAM';
    const STATUS_PERBAIKAN = 'PERBAIKAN';
    const STATUS_HILANG = 'HILANG';

    protected $fillable = [
        'alat_id',
        'kode_unit',
        'kondisi',
        'status'
    ];

    /**
     * MENGAMBIL DAFTAR KONDISI (Realtime)
     */
    public static function getKondisiList()
    {
        return [
            self::KONDISI_BAIK,
            self::KONDISI_RUSAK_RINGAN,
            self::KONDISI_RUSAK_BERAT,
            self::KONDISI_HILANG,
            self::KONDISI_PERBAIKAN,
        ];
    }

    // ================= RELASI =================
    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }

    // Relasi transaksi dipindahkan ke level unit
    public function detailPeminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'alat_unit_id');
    }

    public function riwayatKondisi()
    {
        return $this->hasMany(RiwayatKondisiAlat::class, 'alat_unit_id');
    }
}