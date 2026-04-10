<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'nis',  
        'jurusan_id',
        'kelas_id',     // WAJIB DITAMBAHKAN AGAR DATA KELAS TERSIMPAN
        'password',
        'role',         
        'status',       
        'foto_profile',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url', 
        'initials'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->foto_profile) {
            return asset('storage/' . $this->foto_profile);
        }
        return null;
    }

    public function getInitialsAttribute()
    {
        $words = explode(' ', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    // Helpers
    public function isActive() { return $this->status === 'aktif'; }
    public function isNonActive() { return $this->status === 'nonaktif'; }
    public function isAdmin() { return $this->role === 'admin'; }
    public function isPetugas() { return $this->role === 'petugas'; }
    public function isPeminjam() { return $this->role === 'peminjam'; }
    public function isLulus() { return $this->status === 'lulus'; }
    public function isResign() { return $this->status === 'resign'; }

    // Relationships
    public function jurusan(): BelongsTo { return $this->belongsTo(Jurusan::class, 'jurusan_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); } // RELASI KELAS
    public function peminjamanSebagaiPeminjam(): HasMany { return $this->hasMany(Peminjaman::class, 'user_id'); }
    public function peminjamanSebagaiPetugas(): HasMany { return $this->hasMany(Peminjaman::class, 'petugas_id'); }
}