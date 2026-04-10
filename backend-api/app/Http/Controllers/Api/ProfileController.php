<?php

namespace App\Http\Controllers\Api; // 🔥 Tetap di folder Api

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Mengambil data user yang sedang login
     */
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Memperbarui data user
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Validasi input dengan custom error message
        $validated = $request->validate([
            'name'                  => 'sometimes|required|string|max:255',
            'email'                 => 'sometimes|required|email|unique:users,email,' . $user->id,
            'nis'                   => 'nullable|string|max:20',
            'jurusan_id'            => 'nullable|exists:jurusans,id',
            'foto_profile'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            
            'current_password'      => 'nullable|required_with:password|current_password',
            'password'              => 'nullable|min:8|confirmed|different:current_password',
        ], [
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
            'password.different'                => 'Password baru tidak boleh sama dengan password saat ini.'
        ]);

        // 2. Logic Upload Foto Profile
        if ($request->hasFile('foto_profile')) {
            if ($user->foto_profile) {
                Storage::disk('public')->delete($user->foto_profile);
            }
            $path = $request->file('foto_profile')->store('profiles', 'public');
            $validated['foto_profile'] = $path;
        }

        // 3. Logic Update Password (HANYA jika diisi)
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }
        
        unset($validated['current_password']);

        // 4. Update data ke database
        $user->update($validated);
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'user'    => $user
        ]);
    }
}