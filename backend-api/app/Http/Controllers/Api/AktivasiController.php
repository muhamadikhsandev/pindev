<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AktivasiController extends Controller
{
    /**
     * Step 1: Cek apakah NIS/NIP ada di database master
     */
    public function cekIdentitas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_identitas' => 'required|string',
        ], [
            'nomor_identitas.required' => 'Nomor identitas (NIS/NIP) wajib diisi.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Cari user berdasarkan nomor_identitas
        $user = User::where('nomor_identitas', $request->nomor_identitas)->first();

        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'Nomor identitas tidak ditemukan dalam database master.'
            ], 404);
        }

        // --- CEK JIKA SUDAH AKTIF ---
        if ($user->status === 'aktif') {
            return response()->json([
                'success' => false,
                'is_already_active' => true,
                'message' => "Halo {$user->name}, akun kamu sudah aktif. Silakan langsung login.",
                'data' => [
                    'name' => $user->name
                ]
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "Data ditemukan: {$user->name}",
            'data' => [
                'name' => $user->name,
                'nomor_identitas' => $user->nomor_identitas,
                'status_peminjam' => $user->status_peminjam // GURU atau SISWA
            ]
        ], 200);
    }

    /**
     * Step 2: Kirim Link Aktivasi ke Email
     */
    public function aktivasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_identitas' => 'required|exists:users,nomor_identitas',
            'email' => 'required|email|unique:users,email',
        ], [
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'email.email' => 'Format email tidak valid.',
            'nomor_identitas.exists' => 'Data identitas tidak valid.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('nomor_identitas', $request->nomor_identitas)->first();
            
            // Update email yang akan digunakan untuk login/verifikasi
            $user->update(['email' => $request->email]);

            // Kirim email verifikasi
            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Link aktivasi telah dikirim ke ' . $request->email . '. Silakan cek kotak masuk atau spam.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Gagal kirim email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server gagal mengirim email. Cek konfigurasi mail server.'
            ], 500);
        }
    }

    /**
     * Step 3: Simpan Password Baru setelah Verifikasi Email
     */
    public function simpanPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->id);
            
            $user->update([
                'password' => Hash::make($request->password),
                'status' => 'aktif', // Set status jadi aktif setelah password dibuat
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil disimpan! Akun Anda kini aktif, silakan login.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan password.'
            ], 500);
        }
    }
}