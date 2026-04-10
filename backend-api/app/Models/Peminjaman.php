<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Peminjaman extends Model
{

    use HasFactory;

    protected $table = 'peminjaman';

    // --- DAFTAR STATUS (ENUM-LIKE CONSTANTS) ---
    const STATUS_MENUNGGU_PETUGAS = 'Menunggu Petugas';
    const STATUS_MENUNGGU_ADMIN = 'Menunggu Admin';
    const STATUS_DISETUJUI = 'Disetujui';
    const STATUS_DIPINJAM = 'Dipinjam';
    const STATUS_MENUNGGU_PENGECEKAN = 'Menunggu Pengecekan';
    const STATUS_BERMASALAH = 'Bermasalah';
    const STATUS_DIKEMBALIKAN = 'Dikembalikan';
    const STATUS_DITOLAK = 'Ditolak';

    protected $fillable = [
        'kode_peminjaman',
        'user_id',
        'jurusan',
        'tujuan',
        'petugas_id',
        'tanggal_pinjam',
        'tanggal_rencana_kembali',
        'status',
        'catatan'
    ];

    /**
     * Helper untuk mendapatkan semua opsi status (Bisa digunakan untuk validasi/dropdown)
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_MENUNGGU_PETUGAS,
            self::STATUS_MENUNGGU_ADMIN,
            self::STATUS_DISETUJUI,
            self::STATUS_DIPINJAM,
            self::STATUS_MENUNGGU_PENGECEKAN,
            self::STATUS_BERMASALAH,
            self::STATUS_DIKEMBALIKAN,
            self::STATUS_DITOLAK,
        ];
    }

    /**
     * Otomatis generate kode transaksi saat data dibuat
     */
    protected static function booted()
    {
        static::creating(function ($peminjaman) {
            if (empty($peminjaman->kode_peminjaman)) {
                $today = date('Ymd');

                // Ambil transaksi terakhir yang dibuat HARI INI
                $lastTransaction = self::whereDate('created_at', date('Y-m-d'))
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastTransaction) {
                    // Ambil 3 digit terakhir dari kode_peminjaman
                    // Contoh: PMJ-20260218-001 -> diambil 001 -> jadi 2
                    $lastNumber = (int) substr($lastTransaction->kode_peminjaman, -3);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $peminjaman->kode_peminjaman = 'PMJ-' . $today . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public static function getJurusanOptions(): array
    {
        return [
            'Pengembangan Perangkat Lunak dan Gim' => 'Pengembangan Perangkat Lunak dan Gim',
            'Animasi' => 'Animasi',
            'Broadcasting dan Perfilman' => 'Broadcasting dan Perfilman',
            'Teknik Otomotif' => 'Teknik Otomotif',
            'Teknik Pengelasan dan Fabrikasi Logam' => 'Teknik Pengelasan dan Fabrikasi Logam',
        ];
    }

    // --- RELASI ---

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function peminjam()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function detail_peminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'peminjaman_id');
    }

    public function alat()
    {
        return $this->hasManyThrough(
            Alat::class,
            DetailPeminjaman::class,
            'peminjaman_id',
            'id',
            'id',
            'alat_id'
        );
    }

    public function pengembalian()
    {
        return $this->hasOne(Pengembalian::class, 'peminjaman_id');
    }
}
