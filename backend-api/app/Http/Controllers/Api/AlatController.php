<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\AlatUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Wajib import Storage
use Illuminate\Support\Facades\Log;
use App\Imports\AlatImport; // Pastikan diimport di atas
use Maatwebsite\Excel\Facades\Excel;

class AlatController extends Controller
{
    public function getKondisiOptions()
    {
        return response()->json(['data' => AlatUnit::getKondisiList()]);
    }

public function index()
{
    // 🔥 HIT COUNTER
    Log::info('🔥 API ALAT HIT - ' . now());

    $startTotal = microtime(true);

    // 🔥 DEBUG QUERY COUNT
    DB::enableQueryLog();

    // ================= QUERY =================
    $startQuery = microtime(true);

    $alats = Alat::query()
        // 🔥 FIX 1: Tambahkan 'harga' (Sudah benar)
        ->select('id', 'kategori_id', 'satuan_id', 'nama_alat', 'foto_path', 'harga')
        ->with(['kategori:id,nama_kategori', 'satuan:id,nama_satuan'])
        ->withCount([
            'units as stok' => function ($query) {
                $query->where('status', AlatUnit::STATUS_TERSEDIA)
                      ->whereIn('kondisi', [
                          AlatUnit::KONDISI_BAIK,
                          AlatUnit::KONDISI_RUSAK_RINGAN
                      ]);
            }
        ])
        // ->limit(20) // ❌ HAPUS ATAU KOMENTAR BARIS INI BIAR DATA KELUAR SEMUA
        ->get();

    $endQuery = microtime(true);

    // ================= LOOP =================
    $startLoop = microtime(true);

    foreach ($alats as $alat) {
        // Kita tetep kirim kondisi buat jaga-jaga frontend butuh logika warna stok
        $alat->kondisi = $alat->stok > 0
            ? AlatUnit::KONDISI_BAIK
            : AlatUnit::KONDISI_HILANG;
    }

    $endLoop = microtime(true);

    // ================= TOTAL =================
    $endTotal = microtime(true);

    // 🔥 LOGGING (Biar lu bisa pantau performa kalau data udah ribuan)
    Log::info('⏱️ TOTAL TIME: ' . round(($endTotal - $startTotal), 4) . 's | Data Count: ' . $alats->count());

    return response()->json(['data' => $alats]);
}

public function import(Request $request)
{
    // Validasi ekstensi file dan ukuran maksimal (5MB)
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:5120',
    ]);

    try {
        $file = $request->file('file');

        // 1. Eksekusi proses import data
        Excel::import(new AlatImport, $file);

        // 2. Kalkulasi jumlah baris data yang berhasil diproses
        // Membaca Sheet pertama [0] untuk menghitung total entri
        $dataArray = Excel::toArray(new AlatImport, $file);
        $totalEntries = isset($dataArray[0]) ? count($dataArray[0]) : 0;

        return response()->json([
            'status' => 'success',
            'message' => "Proses import berhasil. Sebanyak {$totalEntries} data alat telah ditambahkan ke sistem."
        ], 200);

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        $detailedErrors = [];

        foreach ($failures as $failure) {
            foreach ($failure->errors() as $error) {
                // Menyusun pesan error per baris untuk mempermudah debugging user
                $detailedErrors[] = "Baris " . $failure->row() . ": " . $error;
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => $detailedErrors[0] ?? 'Terjadi kesalahan validasi pada data Excel Anda.',
            'errors' => $failures
        ], 422);

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();

        // Penanganan khusus untuk kendala duplikasi data pada tingkat database
        if (str_contains($errorMessage, 'Duplicate entry')) {
            $errorMessage = "Terdapat data duplikat pada file Anda atau pada database sistem.";
        }

        return response()->json([
            'status' => 'error',
            'message' => "Gagal memproses import data: " . $errorMessage,
            'details' => $errorMessage
        ], 500);
    }
}
    public function store(Request $request)
    {
        $request->validate([
            'nama_alat'   => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori_alat,id',
            'satuan_id'   => 'required|exists:satuan_alat,id',
            'stok_awal'   => 'required|integer|min:1',
            'harga'       => 'nullable|numeric',
            'foto'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' // Validasi file foto
        ]);

        try {
            DB::beginTransaction();

            // LOGIKA UPLOAD FOTO
            $fotoPath = null;
            if ($request->hasFile('foto')) {
                $fotoPath = $request->file('foto')->store('alat-images', 'public');
            }

            // 1. Buat Master Alat
            $alat = Alat::create([
                'nama_alat'   => $request->nama_alat,
                'kategori_id' => $request->kategori_id,
                'satuan_id'   => $request->satuan_id,
                'harga'       => $request->harga,
                'foto_path'   => $fotoPath, // Simpan path foto
            ]);

            // 2. Buat Prefix Kode Unit
            $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $alat->nama_alat);
            $prefix = strtoupper(Str::limit($cleanName, 4, ''));
            if (strlen($prefix) < 4) {
                $prefix = str_pad($prefix, 4, 'X'); 
            }

            // 3. Looping untuk membuat unit
            $unitsData = [];
            for ($i = 1; $i <= $request->stok_awal; $i++) {
                $kodeUnit = $prefix . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $uniqueCode = $kodeUnit . '-' . $alat->id; 

                $unitsData[] = [
                    'alat_id'    => $alat->id,
                    'kode_unit'  => $uniqueCode,
                    'kondisi'    => AlatUnit::KONDISI_BAIK,
                    'status'     => AlatUnit::STATUS_TERSEDIA,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            AlatUnit::insert($unitsData);

            DB::commit();

            return response()->json([
                'message' => 'Alat dan ' . $request->stok_awal . ' unit berhasil ditambahkan',
                'data' => $alat->load('units')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menambahkan alat', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $alat = Alat::with(['kategori', 'satuan', 'units'])
            ->withCount(['units as stok' => function ($query) {
                $query->where('status', AlatUnit::STATUS_TERSEDIA)
                      ->whereIn('kondisi', [AlatUnit::KONDISI_BAIK, AlatUnit::KONDISI_RUSAK_RINGAN]);
            }])
            ->findOrFail($id);

        $alat->kondisi = $alat->stok > 0 ? AlatUnit::KONDISI_BAIK : AlatUnit::KONDISI_HILANG;

        return response()->json(['data' => $alat]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_alat'   => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori_alat,id',
            'satuan_id'   => 'required|exists:satuan_alat,id',
            'harga'       => 'nullable|numeric',
            'foto'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        try {
            $alat = Alat::findOrFail($id);
            $fotoPath = $alat->foto_path;

            // Jika ada upload foto baru
            if ($request->hasFile('foto')) {
                // Hapus foto lama jika ada
                if ($alat->foto_path && Storage::disk('public')->exists($alat->foto_path)) {
                    Storage::disk('public')->delete($alat->foto_path);
                }
                // Simpan foto baru
                $fotoPath = $request->file('foto')->store('alat-images', 'public');
            }

            $alat->update([
                'nama_alat'   => $request->nama_alat,
                'kategori_id' => $request->kategori_id,
                'satuan_id'   => $request->satuan_id,
                'harga'       => $request->harga,
                'foto_path'   => $fotoPath,
            ]);

            return response()->json(['message' => 'Alat berhasil diperbarui', 'data' => $alat]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui alat', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $alat = Alat::findOrFail($id);

            // Hapus file fisik foto jika ada
            if ($alat->foto_path && Storage::disk('public')->exists($alat->foto_path)) {
                Storage::disk('public')->delete($alat->foto_path);
            }

            $alat->delete();
            return response()->json(['message' => 'Master Alat beserta unit berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus alat', 'error' => $e->getMessage()], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        try {
            $alats = Alat::whereIn('id', $request->ids)->get();
            
            // Hapus semua foto fisik sebelum data di database dihapus
            foreach ($alats as $alat) {
                if ($alat->foto_path && Storage::disk('public')->exists($alat->foto_path)) {
                    Storage::disk('public')->delete($alat->foto_path);
                }
            }

            Alat::whereIn('id', $request->ids)->delete();
            return response()->json(['message' => 'Data Alat terpilih berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data masal', 'error' => $e->getMessage()], 500);
        }
    }
}