<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Traits\BulkActionTrait; // Import Trait untuk hapus masal
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JurusanController extends Controller
{
    use BulkActionTrait;

    protected $model;

    public function __construct(Jurusan $jurusan)
    {
        $this->model = $jurusan;
    }

    public function index(Request $request)
    {
        try {
            $query = Jurusan::query();
            
            // Pencarian berdasarkan nama atau kode jurusan
            if ($request->search) {
                $query->where('nama_jurusan', 'like', "%{$request->search}%")
                      ->orWhere('kode_jurusan', 'like', "%{$request->search}%");
            }
            
            $data = $query->latest()->get()->map(fn($item) => [
                'id'           => $item->id,
                'kode_jurusan' => $item->kode_jurusan,
                'nama_jurusan' => $item->nama_jurusan,
            ]);

            return response()->json(['data' => $data]); 

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500); 
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_jurusan' => 'required|string|max:50|unique:jurusan,kode_jurusan',
            'nama_jurusan' => 'required|string|max:255',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $jurusan = Jurusan::create($request->all());
            
            return response()->json(['message' => 'Jurusan berhasil ditambahkan', 'data' => $jurusan], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $jurusan = Jurusan::findOrFail($id);
            return response()->json(['data' => $jurusan]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Jurusan tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'kode_jurusan' => 'required|string|max:50|unique:jurusan,kode_jurusan,' . $id,
            'nama_jurusan' => 'required|string|max:255',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $jurusan = Jurusan::findOrFail($id);
            $jurusan->update($request->all());
            
            return response()->json(['message' => 'Jurusan berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $jurusan = Jurusan::findOrFail($id);
            $jurusan->delete();
            return response()->json(['message' => 'Jurusan berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}