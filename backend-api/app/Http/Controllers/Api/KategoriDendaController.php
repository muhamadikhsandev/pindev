<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriDenda;
use App\Traits\BulkActionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriDendaController extends Controller
{
    use BulkActionTrait;

    protected $model;

    public function __construct(KategoriDenda $kategoriDenda)
    {
        $this->model = $kategoriDenda;
    }

    public function index(Request $request)
    {
        try {
            $query = KategoriDenda::query();
            
            if ($request->search) {
                $query->where('nama_kategori', 'like', "%{$request->search}%");
            }
            
            $data = $query->latest()->get()->map(fn($item) => [
                'id'            => $item->id,
                'nama_kategori' => $item->nama_kategori,
                'metode_denda'  => $item->metode_denda,
                'nilai_denda'   => $item->nilai_denda,
            ]);

            return response()->json(['data' => $data]); 

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500); 
        }
    }

    public function store(Request $request)
    {
        // Validasi disesuaikan dengan nilai ENUM di database
        $v = Validator::make($request->all(), [
            'nama_kategori' => 'required|in:TELAT,RUSAK_RINGAN,RUSAK_BERAT,HILANG|unique:kategori_denda,nama_kategori',
            'metode_denda'  => 'required|in:PER_HARI,SEKALI_BAYAR,PERSENTASE',
            'nilai_denda'   => 'required|numeric|min:0',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $kategoriDenda = KategoriDenda::create($request->all());
            return response()->json(['message' => 'Kategori Denda berhasil ditambahkan', 'data' => $kategoriDenda], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $kategoriDenda = KategoriDenda::findOrFail($id);
            return response()->json(['data' => $kategoriDenda]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Kategori Denda tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'nama_kategori' => 'required|in:TELAT,RUSAK_RINGAN,RUSAK_BERAT,HILANG|unique:kategori_denda,nama_kategori,' . $id,
            'metode_denda'  => 'required|in:PER_HARI,SEKALI_BAYAR,PERSENTASE',
            'nilai_denda'   => 'required|numeric|min:0',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $kategoriDenda = KategoriDenda::findOrFail($id);
            $kategoriDenda->update($request->all());
            
            return response()->json(['message' => 'Kategori Denda berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kategoriDenda = KategoriDenda::findOrFail($id);
            $kategoriDenda->delete();
            return response()->json(['message' => 'Kategori Denda berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}