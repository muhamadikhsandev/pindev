<?php

namespace App\Traits;

use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

trait Loggable
{
    /**
     * Relasi Polymorphic: Menghubungkan Model ke LogAktivitas
     */
    public function logs()
    {
        return $this->morphMany(LogAktivitas::class, 'loggable');
    }

    /**
     * Fungsi Helper untuk mencatat log aktivitas
     * * @param string $aksi (Contoh: 'CREATE', 'UPDATE', 'DELETE', 'APPROVE')
     * @param string $deskripsi (Contoh: 'Menyetujui peminjaman kamera')
     * @param array|null $oldData (Data sebelum diubah)
     * @param array|null $newData (Data setelah diubah)
     */
    public function createLog($aksi, $deskripsi, $oldData = null, $newData = null)
    {
        return $this->logs()->create([
            'user_id'    => Auth::id() ?? 1, // Default ke ID 1 jika dijalankan via system/console
            'aksi'       => $aksi,
            'deskripsi'  => $deskripsi,
            'data_lama'  => $oldData,
            'data_baru'  => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}