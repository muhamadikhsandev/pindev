<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

/**
 * Extends BaseModel supaya otomatis dapet fitur Loggable & HasFactory
 */
class Alat extends BaseModel
{
    protected $table = 'alat';

    protected $fillable = [
        'kategori_id',
        'satuan_id',
        'nama_alat',
        'foto_path',
        'harga'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->foto_path ? asset('storage/' . $this->foto_path) : null;
    }

    // ================= EVENT BOOT =================
    protected static function boot()
    {
        parent::boot();

        // Event ini dipanggil sebelum data Alat dihapus
        static::deleting(function ($alat) {
            // 1. Hapus semua fisik unit yang terkait dengan alat ini
            $alat->units()->delete();
            
            // 2. Bersihkan file foto dari storage
            if ($alat->foto_path && Storage::disk('public')->exists($alat->foto_path)) {
                Storage::disk('public')->delete($alat->foto_path);
            }
        });
    }

    // ================= RELASI =================
    public function units()
    {
        return $this->hasMany(AlatUnit::class, 'alat_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriAlat::class, 'kategori_id');
    }

    public function satuan()
    {
        return $this->belongsTo(SatuanAlat::class, 'satuan_id');
    }

   
}