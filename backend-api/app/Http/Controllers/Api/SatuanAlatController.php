<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SatuanAlat;
use App\Traits\BulkActionTrait; // Import Trait
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SatuanAlatController extends Controller
{
    use BulkActionTrait; // Gunakan Trait untuk hapus masal

    protected $model;

    public function __construct(SatuanAlat $satuan)
    {
        $this->model = $satuan;
    }

    public function index(Request $request)
    {
        try {
            $query = SatuanAlat::query();
            
            if ($request->search) {
                $query->where('nama_satuan', 'like', "%{$request->search}%");
            }
            
            $data = $query->latest()->get()->map(fn($item) => [
                'id'          => $item->id,
                'nama_satuan' => $item->nama_satuan,
            ]);

            return response()->json(['data' => $data]); 

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500); 
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nama_satuan' => 'required|string|max:255|unique:satuan_alat,nama_satuan',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $satuan = SatuanAlat::create($request->all());
            
            return response()->json(['message' => 'Satuan berhasil ditambahkan', 'data' => $satuan], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $satuan = SatuanAlat::findOrFail($id);
            return response()->json(['data' => $satuan]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Satuan tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'nama_satuan' => 'required|string|max:255|unique:satuan_alat,nama_satuan,' . $id,
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $satuan = SatuanAlat::findOrFail($id);
            $satuan->update($request->all());
            
            return response()->json(['message' => 'Satuan berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $satuan = SatuanAlat::findOrFail($id);
            $satuan->delete();
            return response()->json(['message' => 'Satuan berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}