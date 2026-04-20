<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /**
     * Menangani proses update sandi baru ke database.
     */
    public function resetPasswordStore(Request $request)
    {
        // 1. Validasi Input dari Frontend (Next.js)
        $validator = Validator::make($request->all(), [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi sandi tidak cocok.',
            'password.min'       => 'Sandi minimal harus 8 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // 2. Cari User berdasarkan Email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User dengan email tersebut tidak ditemukan.'
            ], 404);
        }

        // 3. PROTEKSI: Cek apakah sandi baru sama dengan sandi lama 🔥
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Sandi baru tidak boleh sama dengan sandi lama kamu!'
            ], 422);
        }

        // 4. Eksekusi Reset Password menggunakan Password Broker Laravel
        // Ini akan memvalidasi token secara otomatis dan mengupdate password
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
                
                // Opsional: Hapus atau update token remember_me jika ada
                $user->setRememberToken(null);
            }
        );

        // 5. Berikan Response berdasarkan hasil dari Laravel Broker
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Sandi berhasil diperbarui. Silakan login kembali.'
            ]);
        }

        // Jika token salah, kadaluwarsa, atau email tidak cocok dengan token
        return response()->json([
            'success' => false,
            'message' => 'Link reset sudah tidak valid atau kadaluwarsa. Silakan minta link baru.'
        ], 400);
    }
}