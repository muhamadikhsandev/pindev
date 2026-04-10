<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Alat;
use App\Models\AlatUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartItemController extends Controller
{
    /**
     * Tampilkan isi keranjang User yang sedang login
     */
    public function index(Request $request)
    {
        try {
            // Ambil keranjang beserta alat, kategori, dan hitung stok virtualnya
            $cartItems = CartItem::with(['alat' => function($q) {
                    $q->with('kategori')->withCount(['units as stok' => function ($query) {
                        $query->where('status', AlatUnit::STATUS_TERSEDIA)
                              ->whereIn('kondisi', [AlatUnit::KONDISI_BAIK, AlatUnit::KONDISI_RUSAK_RINGAN]); 
                    }]);
                }])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get();

            $formatted = $cartItems->map(function ($item) {
                if (!$item->alat) return null;

                return [
                    'id'            => $item->alat->id,      
                    'cart_id'       => $item->id,            
                    'nama_alat'     => $item->alat->nama_alat,
                    'stok'          => $item->alat->stok, // Stok Virtual (dari withCount)
                    'image_url'     => $item->alat->foto_path ? asset('storage/' . $item->alat->foto_path) : null,
                    'foto_path'     => $item->alat->foto_path, 
                    'kategori_name' => $item->alat->kategori ? $item->alat->kategori->nama_kategori : 'Umum',
                    'qty'           => $item->qty,
                ];
            })->filter();

            return response()->json(['data' => $formatted->values()]);

        } catch (\Exception $e) {
            Log::error("Cart Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat keranjang', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tambah/Update barang ke keranjang
     */
    public function store(Request $request)
    {
        // 1. Ekstrak ID alat. 
        // Mengantisipasi jika frontend mengirim "alat_id" atau "tool" (object)
        $alat_id = $request->input('alat_id') ?? $request->input('tool.id');
        $qty = $request->input('qty');

        if (!$alat_id) {
            return response()->json(['message' => 'Alat ID tidak valid!'], 422);
        }

        try {
            $user = $request->user();

            // 2. Hitung Stok Virtual (Berdasarkan Unit Fisik yang Tersedia)
            $stokTersedia = AlatUnit::where('alat_id', $alat_id)
                                    ->where('status', AlatUnit::STATUS_TERSEDIA)
                                    ->whereIn('kondisi', [AlatUnit::KONDISI_BAIK, AlatUnit::KONDISI_RUSAK_RINGAN])
                                    ->count();

            // Jika stok habis di gudang
            if ($stokTersedia <= 0) {
                return response()->json(['message' => 'Stok alat sedang kosong/dipinjam'], 400);
            }

            // Jika QTY 0 atau kurang dari frontend, hapus barang dari keranjang
            if ($qty <= 0) {
                CartItem::where('user_id', $user->id)->where('alat_id', $alat_id)->delete();
                return response()->json(['message' => 'Barang dihapus dari keranjang'], 200);
            }

            // 3. Cari barang di keranjang
            $existingItem = CartItem::where('user_id', $user->id)
                ->where('alat_id', $alat_id)
                ->first();

            if ($existingItem) {
                // UPDATE (Override jumlah keranjang dengan jumlah terbaru dari input modal)
                // Jika mau ditambah (bukan di-override), ubah menjadi: $existingItem->qty + $qty;
                $newQty = $qty; 
                
                if ($newQty > $stokTersedia) {
                    return response()->json(['message' => 'Stok tidak mencukupi. Sisa: ' . $stokTersedia], 400);
                }

                $existingItem->update(['qty' => $newQty]);
                $cartItem = $existingItem;
            } else {
                // INSERT BARU
                if ($qty > $stokTersedia) {
                    return response()->json(['message' => 'Jumlah melebihi stok tersedia. Sisa: ' . $stokTersedia], 400);
                }

                $cartItem = CartItem::create([
                    'user_id' => $user->id,
                    'alat_id' => $alat_id,
                    'qty'     => $qty
                ]);
            }

            return response()->json([
                'message' => 'Berhasil masuk keranjang',
                'data' => $cartItem
            ], 200);

        } catch (\Exception $e) {
            Log::error("Cart Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan sistem', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update jumlah spesifik
     * URL: PUT /api/cart/{alat_id}
     */
    public function update(Request $request, $id) 
    {
        $request->validate(['qty' => 'required|integer|min:1']);

        try {
            $cartItem = CartItem::where('user_id', $request->user()->id)
                ->where('alat_id', $id)
                ->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Item tidak ditemukan di keranjang'], 404);
            }

            // Hitung stok virtual terbaru
            $stokTersedia = AlatUnit::where('alat_id', $id)
                                    ->where('status', AlatUnit::STATUS_TERSEDIA)
                                    ->whereIn('kondisi', [AlatUnit::KONDISI_BAIK, AlatUnit::KONDISI_RUSAK_RINGAN])
                                    ->count();

            if ($request->qty > $stokTersedia) {
                 return response()->json(['message' => 'Melebihi sisa stok tersedia (' . $stokTersedia . ')'], 400);
            }

            $cartItem->update(['qty' => $request->qty]);

            return response()->json(['message' => 'Qty diperbarui']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus item dari keranjang
     * URL: DELETE /api/cart/{alat_id}
     */
    public function destroy(Request $request, $id)
    {
        $deleted = CartItem::where('user_id', $request->user()->id)
            ->where('alat_id', $id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Item dihapus']);
        }
        
        return response()->json(['message' => 'Gagal menghapus atau item tidak ditemukan'], 404);
    }
    
    /**
     * Hapus semua isi keranjang
     */
    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => 'Keranjang dibersihkan']);
    }
}