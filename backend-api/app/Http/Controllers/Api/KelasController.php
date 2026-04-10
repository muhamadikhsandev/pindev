<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Traits\BulkActionTrait; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KelasImport;

class KelasController extends Controller
{
    use BulkActionTrait;

    protected $model;

    public function __construct(Kelas $kelas)
    {
        $this->model = $kelas;
    }

    public function index(Request $request)
    {
        try {
            // Kita pakai with('jurusan') agar data jurusannya ikut terbawa (Eager Loading)
            $query = Kelas::with('jurusan');
            
            // Pencarian berdasarkan nama kelas, nama jurusan, atau kode jurusan
            if ($request->search) {
                $query->where('nama_kelas', 'like', "%{$request->search}%")
                      ->orWhereHas('jurusan', function($q) use ($request) {
                          $q->where('nama_jurusan', 'like', "%{$request->search}%")
                            ->orWhere('kode_jurusan', 'like', "%{$request->search}%");
                      });
            }
            
            $data = $query->latest()->get()->map(fn($item) => [
                'id'           => $item->id,
                'nama_kelas'   => $item->nama_kelas,
                'jurusan_id'   => $item->jurusan_id,
                'nama_jurusan' => $item->jurusan ? $item->jurusan->nama_jurusan : 'Tanpa Jurusan',
                'kode_jurusan' => $item->jurusan ? $item->jurusan->kode_jurusan : '-', // MENAMBAHKAN KODE JURUSAN
            ]);

            return response()->json(['data' => $data]); 

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500); 
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nama_kelas' => 'required|string|max:255',
            'jurusan_id' => 'required|exists:jurusan,id', 
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $kelas = Kelas::create($request->all());
            
            // Load relasi jurusan setelah dibuat untuk dikembalikan ke frontend
            $kelas->load('jurusan');
            
            return response()->json(['message' => 'Kelas berhasil ditambahkan', 'data' => $kelas], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $kelas = Kelas::with('jurusan')->findOrFail($id);
            return response()->json(['data' => $kelas]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Kelas tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'nama_kelas' => 'required|string|max:255',
            'jurusan_id' => 'required|exists:jurusan,id',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $kelas = Kelas::findOrFail($id);
            $kelas->update($request->all());
            
            return response()->json(['message' => 'Kelas berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new KelasImport, $request->file('file'));
            return response()->json([
                'message' => 'Data Kelas berhasil di-import massal!'
            ], 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }
            return response()->json(['message' => 'Validasi Excel gagal', 'errors' => $errors], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal meng-import data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kelas = Kelas::findOrFail($id);
            $kelas->delete();
            return response()->json(['message' => 'Kelas berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}