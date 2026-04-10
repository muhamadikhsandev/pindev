<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriAlat;
use App\Traits\BulkActionTrait; // Import Trait
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriAlatController extends Controller
{
    use BulkActionTrait; // Gunakan Trait

    protected $model;

    public function __construct(KategoriAlat $kategori)
    {
        $this->model = $kategori;
    }

    public function index(Request $request)
    {
        try {
            $query = KategoriAlat::query();
            
            if ($request->search) {
                $query->where('nama_kategori', 'like', "%{$request->search}%");
            }
            
            $data = $query->latest()->get()->map(fn($item) => [
                'id'            => $item->id,
                'nama_kategori' => $item->nama_kategori,
            ]);

            return response()->json(['data' => $data]); 

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500); 
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|max:255|unique:kategori_alat,nama_kategori',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $kategori = KategoriAlat::create($request->all());
            
            return response()->json(['message' => 'Kategori berhasil ditambahkan', 'data' => $kategori], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $kategori = KategoriAlat::findOrFail($id);
            return response()->json(['data' => $kategori]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|max:255|unique:kategori_alat,nama_kategori,' . $id,
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $kategori = KategoriAlat::findOrFail($id);
            $kategori->update($request->all());
            
            return response()->json(['message' => 'Kategori berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kategori = KategoriAlat::findOrFail($id);
            $kategori->delete();
            return response()->json(['message' => 'Kategori berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}