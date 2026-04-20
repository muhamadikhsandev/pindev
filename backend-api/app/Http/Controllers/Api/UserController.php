<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Traits\BulkActionTrait;

class UserController extends Controller
{
    use BulkActionTrait;

    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    private function formatName($name)
    {
        return ucwords(strtolower(trim($name)));
    }

    public function index(Request $request)
    {
        try {
            $query = User::with(['jurusan', 'kelas']);
            if ($request->role) $query->where('role', $request->role);
            if ($request->status_peminjam) $query->where('status_peminjam', strtoupper($request->status_peminjam));
            
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                      ->orWhere('nomor_identitas', 'like', "%{$request->search}%");
                });
            }

            $users = $query->latest()->get();
            return response()->json(['data' => $users]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $identityType = $request->status_peminjam === 'GURU' ? 'NIP' : 'NIS';
        
        // Cek manual siapa pemilik nomor identitas tersebut jika sudah ada
        $existingUser = User::where('nomor_identitas', $request->nomor_identitas)->first();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email', 
            'nomor_identitas' => 'required|string|max:20|unique:users,nomor_identitas', 
            'role' => 'required',
            'status_peminjam' => 'nullable|in:SISWA,GURU', 
        ], [
            'nomor_identitas.unique' => "Gagal! $identityType ini sudah terdaftar atas nama: " . ($existingUser ? $existingUser->name : 'Pengguna Lain'),
            'nomor_identitas.required' => "$identityType wajib diisi.",
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = User::create([
                'name' => $this->formatName($request->name),
                'email' => $request->email ?: null, 
                'nomor_identitas' => $request->nomor_identitas,
                'password' => Hash::make($request->password ?? $request->nomor_identitas),
                'role' => $request->role,
                'status_peminjam' => $request->status_peminjam,
                'jurusan_id' => $request->jurusan_id,
                'kelas_id' => $request->kelas_id,
                'status' => 'nonaktif', 
            ]);
            return response()->json(['message' => 'User berhasil dibuat', 'data' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $identityType = ($request->status_peminjam ?? $user->status_peminjam) === 'GURU' ? 'NIP' : 'NIS';

        // Cek pemilik nomor identitas selain user ini sendiri
        $existingUser = User::where('nomor_identitas', $request->nomor_identitas)
                            ->where('id', '!=', $id)
                            ->first();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required',
            'nomor_identitas' => ['sometimes', 'required', Rule::unique('users')->ignore($user->id)],
        ], [
            'nomor_identitas.unique' => "Gagal Update! $identityType sudah digunakan oleh: " . ($existingUser ? $existingUser->name : 'Pengguna Lain'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $updateData = $request->all();
            if (isset($updateData['name'])) $updateData['name'] = $this->formatName($updateData['name']);
            
            if ($request->hasFile('foto_profile')) {
                if ($user->foto_profile) Storage::disk('public')->delete($user->foto_profile);
                $updateData['foto_profile'] = $request->file('foto_profile')->store('profile_photos', 'public');
            }

            $user->update($updateData);
            return response()->json(['message' => 'Data diperbarui', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->foto_profile) Storage::disk('public')->delete($user->foto_profile);
            $user->delete();
            return response()->json(['message' => 'User berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus', 'error' => $e->getMessage()], 500);
        }
    }
}