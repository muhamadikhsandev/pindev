<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Jurusan; 
use App\Traits\BulkActionTrait; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UserImport;

class UserController extends Controller
{
    use BulkActionTrait; 

    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function getJurusanOptions()
    {
        try {
            $jurusan = Jurusan::select('id', 'kode_jurusan', 'nama_jurusan')->get();
            return response()->json(['data' => $jurusan]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage(), 'data' => []], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            // Relasi ditambahkan dengan jurusan dan kelas
            $query = User::with(['jurusan', 'kelas']);

            if ($request->role) {
                $query->where('role', $request->role);
            }

            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('nis', 'like', "%{$request->search}%");
                });
            }

            $data = $query->latest()->get()->map(fn($item) => [
                'id'           => $item->id,
                'name'         => $item->name,
                'email'        => $item->email,
                'nis'          => $item->nis,
                'jurusan_id'   => $item->jurusan_id,
                'jurusan_kode' => $item->jurusan->kode_jurusan ?? '-',
                'jurusan_nama' => $item->jurusan->nama_jurusan ?? '-',
                'kelas_id'     => $item->kelas_id, // Sekarang aman dipanggil
                'kelas_nama'   => $item->kelas->nama_kelas ?? '-', 
                'role'         => $item->role,
                'status'       => $item->status,
                'foto_profile' => $item->foto_profile ? asset('storage/' . $item->foto_profile) : null, 
            ]);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // FIX ERROR ROLE: Jika role kosong, paksa set menjadi "peminjam"
        $request->merge([
            'role' => $request->input('role', 'peminjam')
        ]);

        $v = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:8',
            'role'         => 'required|in:admin,petugas,peminjam',
            'status'       => 'nullable|in:aktif,nonaktif,lulus,resign',
            'nis'          => $request->role === 'peminjam' ? 'required|digits:10|unique:users,nis' : 'nullable',
            'jurusan_id'   => $request->role === 'peminjam' ? 'required|exists:jurusan,id' : 'nullable',
            'kelas_id'     => 'nullable|exists:kelas,id', // FIX KELAS
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $foto_profile_path = null;

            if ($request->hasFile('foto_profile')) {
                $foto_profile_path = $request->file('foto_profile')->store('profile_photos', 'public');
            }

            $user = User::create([
                'name'         => $request->name,
                'email'        => $request->email,
                'password'     => Hash::make($request->password),
                'nis'          => $request->nis,
                'jurusan_id'   => $request->jurusan_id,
                'kelas_id'     => $request->kelas_id, // SIMPAN KELAS
                'role'         => $request->role,
                'status'       => $request->status ?? 'aktif',
                'foto_profile' => $foto_profile_path,
            ]);

            return response()->json(['message' => 'User berhasil ditambahkan', 'data' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', 
            'role' => 'nullable|in:admin,petugas,peminjam'
        ]);

        try {
            $role = $request->input('role', 'peminjam'); 
            Excel::import(new UserImport($role), $request->file('file'));
            
            return response()->json(['message' => 'Data berhasil di-import!']);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return response()->json(['message' => 'Gagal validasi data Excel', 'errors' => $errors], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal import data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['jurusan', 'kelas'])->findOrFail($id);
            $user->foto_profile_url = $user->foto_profile ? asset('storage/' . $user->foto_profile) : null;
            
            return response()->json(['data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // FIX ERROR ROLE: Jika role tidak dikirim saat update, set default ke role sebelumnya atau peminjam
        $request->merge([
            'role' => $request->input('role', $user->role ?? 'peminjam')
        ]);

        $v = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'         => 'required|in:admin,petugas,peminjam',
            'status'       => 'required|in:aktif,nonaktif,lulus,resign',
            'nis'          => $request->role === 'peminjam' ? ['required', 'digits:10', Rule::unique('users')->ignore($user->id)] : 'nullable',
            'jurusan_id'   => $request->role === 'peminjam' ? 'required|exists:jurusan,id' : 'nullable',
            'kelas_id'     => 'nullable|exists:kelas,id', // FIX KELAS
            'password'     => 'nullable|min:8',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        try {
            $updateData = $request->only(['name', 'email', 'nis', 'jurusan_id', 'kelas_id', 'role', 'status']);
            
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('foto_profile')) {
                if ($user->foto_profile && Storage::disk('public')->exists($user->foto_profile)) {
                    Storage::disk('public')->delete($user->foto_profile);
                }
                $updateData['foto_profile'] = $request->file('foto_profile')->store('profile_photos', 'public');
            }

            $user->update($updateData);

            return response()->json(['message' => 'User berhasil diperbarui', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            if ($user->foto_profile && Storage::disk('public')->exists($user->foto_profile)) {
                Storage::disk('public')->delete($user->foto_profile);
            }

            $user->delete();
            return response()->json(['message' => 'User berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}