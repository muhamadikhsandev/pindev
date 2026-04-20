<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator; // Tambahkan ini
use Exception;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user()->load(['jurusan', 'kelas']));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        Log::info('--- START PROFILE UPDATE ---', ['user_id' => $user->id]);
        
        // DEBUG: Cek semua data yang masuk ke Laravel
        Log::info('Data Masuk:', $request->all());
        Log::info('File Masuk:', ['has_file' => $request->hasFile('foto_profile')]);

        try {
            // Validasi manual agar kita bisa menangkap error lebih detail
            $validator = Validator::make($request->all(), [
                'name'             => 'sometimes|required|string|max:255',
                'email'            => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'nomor_identitas'  => ['sometimes', 'required', 'string', Rule::unique('users')->ignore($user->id)],
                'jurusan_id'       => 'nullable',
                // Perubahan: Gunakan 'file' sebelum 'image' untuk stabilitas di Windows/XAMPP
                'foto_profile'     => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120', 
                
                'current_password' => 'nullable|required_with:password',
                'password'         => 'nullable|min:8|confirmed|different:current_password',
            ], [
                'foto_profile.mimes' => 'Format file harus jpeg, png, jpg, atau webp.',
                'foto_profile.max'   => 'Ukuran file maksimal 5MB.',
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi Gagal:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // 1. Logic Password (PENTING: Cek password lama manual)
            if ($request->filled('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Password saat ini salah.',
                        'errors'  => ['current_password' => ['Password saat ini salah.']]
                    ], 422);
                }
                $user->password = Hash::make($request->password);
                Log::info('Password berhasil di-hash.');
            }

            // 2. Logic Update Data Tekstual
            $user->name = $validated['name'] ?? $user->name;
            $user->email = $validated['email'] ?? $user->email;
            $user->nomor_identitas = $validated['nomor_identitas'] ?? $user->nomor_identitas;
            $user->jurusan_id = $validated['jurusan_id'] ?? $user->jurusan_id;

            // 3. Logic Foto Profile
            if ($request->hasFile('foto_profile')) {
                $file = $request->file('foto_profile');
                
                Log::info('File terdeteksi:', [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType()
                ]);

                // Hapus foto lama
                if ($user->foto_profile && Storage::disk('public')->exists($user->foto_profile)) {
                    Storage::disk('public')->delete($user->foto_profile);
                }

                // Simpan foto baru ke folder 'profiles' di storage/public
                $path = $file->store('profiles', 'public');
                $user->foto_profile = $path;
                Log::info('Foto baru disimpan di: ' . $path);
            }

            // Simpan semua perubahan
            $user->save();
            
            // Reload data relasi
            $user->load(['jurusan', 'kelas']);

            Log::info('--- UPDATE SUCCESS ---');

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
                'user'    => $user
            ]);

        } catch (Exception $e) {
            Log::error('FATAL ERROR:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil: ' . $e->getMessage()
            ], 500);
        }
    }
}