<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    // Menentukan nama tabel (opsional jika sudah jamak, tapi aman untuk didefinisikan)
    protected $table = 'cart_items';

    // Mengizinkan pengisian data secara massal (mass assignment)
    protected $guarded = ['id'];

    /**
     * Relasi ke User
     * Satu item di keranjang dimiliki oleh satu User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Alat
     * Satu item di keranjang merujuk ke satu Alat
     */
    public function alat(): BelongsTo
    {
        // Jika nama model kamu 'Alat', panggil Alat::class
        return $this->belongsTo(Alat::class);
    }
}