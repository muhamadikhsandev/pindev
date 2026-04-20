<?php

namespace App\Http\Controllers\Api\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Pengembalian;
use Illuminate\Http\Request;

class PengembalianController extends Controller
{
    public function index()
    {
        try {
            // Eager loading mendalam.
            // PASTIKAN di model Peminjaman.php kamu punya fungsi:
            // 1. user() atau peminjam()
            // 2. detail_peminjaman() atau detailPeminjaman()
            $data = Pengembalian::with([
                'peminjaman.user', // Relasi ke tabel users
                'peminjaman.detail_peminjaman.alat', // Relasi ke detail dan alat
            ])->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Data riwayat pengembalian berhasil diambil',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            // Kalau ada relasi yang salah nama (misal: method user() tidak ada), errornya akan ketahuan di sini
            return response()->json([
                'success' => false,
                'message' => 'Error Relasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'peminjaman_id' => 'required|exists:peminjaman,id',
            'kondisi_kembali' => 'required|in:' . Pengembalian::KONDISI_BAIK . ',' . Pengembalian::KONDISI_BERMASALAH,
            'catatan' => 'nullable|string'
        ]);

        try {
            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $validated['peminjaman_id'],
                'tanggal_kembali' => now()->toDateString(),
                'kondisi_kembali' => $validated['kondisi_kembali'],
                'catatan' => $validated['catatan'] ?? 'Tanpa catatan',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data pengembalian berhasil disimpan',
                'data' => $pengembalian
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal simpan: ' . $e->getMessage()
            ], 500);
        }
    }
}