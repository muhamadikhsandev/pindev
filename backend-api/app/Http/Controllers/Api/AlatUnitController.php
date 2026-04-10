<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlatUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlatUnitController extends Controller
{
    /**
     * GET /api/alat-unit
     * Menampilkan semua daftar fisik unit beserta relasi ke master alat.
     */
    public function index()
    {
        try {
            // Ambil semua unit dan sertakan nama master alatnya
            $units = AlatUnit::with('alat:id,nama_alat')->latest()->get();
            
            return response()->json(['data' => $units]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data unit', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/alat-unit/{id}
     * Menampilkan detail satu unit.
     */
    public function show($id)
    {
        try {
            $unit = AlatUnit::with('alat:id,nama_alat')->findOrFail($id);
            return response()->json(['data' => $unit]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unit tidak ditemukan'], 404);
        }
    }

    /**
     * PUT /api/alat-unit/{id}
     * Memperbarui kondisi dan status unit.
     */
    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'kondisi' => 'required|string',
            'status'  => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        try {
            $unit = AlatUnit::findOrFail($id);
            $unit->update([
                'kondisi' => $request->kondisi,
                'status'  => $request->status,
            ]);
            
            return response()->json([
                'message' => 'Unit berhasil diperbarui', 
                'data' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui unit', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/alat-unit/{id}
     * Menghapus unit fisik secara permanen.
     */
    public function destroy($id)
    {
        try {
            $unit = AlatUnit::findOrFail($id);
            $unit->delete();
            
            return response()->json(['message' => 'Unit fisik berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus unit', 'error' => $e->getMessage()], 500);
        }
    }
}