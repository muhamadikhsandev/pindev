<?php

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Loggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'nomor_identitas', // Netral untuk NIS (Siswa) atau NIP (Guru)
        'status_peminjam', // Kategori: 'GURU' atau 'SISWA'
        'jurusan_id',
        'kelas_id',
        'password',
        'role',            // admin, petugas, peminjam
        'status',          // aktif, nonaktif, lulus, resign
        'foto_profile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url', 
        'initials'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Custom Password Reset Notification
     * Mengarahkan ke URL Frontend Next.js
     */
    public function sendPasswordResetNotification($token)
    {
        $url = 'http://localhost:3000/reset-sandi?token=' . $token . '&email=' . $this->email;
        $this->notify(new \App\Notifications\ResetPasswordNotification($url));
    }

    /**
     * Custom Email Verification Notification
     */
    public function sendEmailVerificationNotification()
    {
        // Mengakses properti statis $createUrlCallback dari VerifyEmail
        $callback = \Illuminate\Auth\Notifications\VerifyEmail::$createUrlCallback;

        if ($callback) {
            // Eksekusi callback secara aman menggunakan call_user_func
            $url = call_user_func($callback, $this);
        } else {
            // Fallback manual jika callback tidak ditemukan
            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'api.verification.verify',
                \Illuminate\Support\Carbon::now()->addMinutes(60),
                [
                    'id' => $this->getKey(),
                    'hash' => sha1($this->getEmailForVerification()),
                ]
            );
        }

        $this->notify(new \App\Notifications\VerifyEmailNotification($url));
    }

    /**
     * Accessor untuk URL Foto Profile
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->foto_profile) {
            return asset('storage/' . $this->foto_profile);
        }
        return null;
    }

    /**
     * Accessor untuk Inisial Nama (Contoh: Budi Santoso -> BS)
     */
    public function getInitialsAttribute()
    {
        $words = explode(' ', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * ==========================================
     * HELPERS - Status & Role Check
     * ==========================================
     */
    
    // Status Akun
    public function isActive() { return $this->status === 'aktif'; }
    public function isNonActive() { return $this->status === 'nonaktif'; }
    public function isLulus() { return $this->status === 'lulus'; }
    public function isResign() { return $this->status === 'resign'; }

    // Role Utama
    public function isAdmin() { return $this->role === 'admin'; }
    public function isPetugas() { return $this->role === 'petugas'; }
    public function isPeminjam() { return $this->role === 'peminjam'; }

    // Pembeda Jenis Peminjam
    public function isGuru() { 
        return $this->isPeminjam() && $this->status_peminjam === 'GURU'; 
    }
    
    public function isSiswa() { 
        return $this->isPeminjam() && $this->status_peminjam === 'SISWA'; 
    }

    /**
     * ==========================================
     * RELATIONSHIPS
     * ==========================================
     */
    
    // Relasi ke Jurusan
    public function jurusan(): BelongsTo 
    { 
        return $this->belongsTo(Jurusan::class, 'jurusan_id'); 
    }

    // Relasi ke Kelas
    public function kelas(): BelongsTo 
    { 
        return $this->belongsTo(Kelas::class, 'kelas_id'); 
    }

    // Riwayat Peminjaman sebagai User yang meminjam
    public function peminjamanSebagaiPeminjam(): HasMany 
    { 
        return $this->hasMany(Peminjaman::class, 'user_id'); 
    }

    // Riwayat Peminjaman sebagai Petugas yang memproses
    public function peminjamanSebagaiPetugas(): HasMany 
    { 
        return $this->hasMany(Peminjaman::class, 'petugas_id'); 
    }
}