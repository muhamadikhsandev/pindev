<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Verified;

class AuthController extends Controller
{


public function googleLogin(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    // Cari user berdasarkan email[cite: 5]
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Email ini belum terdaftar di sistem PINDEV. Silakan hubungi Admin.'
        ], 404);
    }

    // Cek status user[cite: 5]
    if ($user->status !== 'aktif') {
        return response()->json([
            'message' => 'Akun Anda tidak aktif atau ditangguhkan.'
        ], 403);
    }

    // Buat token Sanctum untuk session API
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Berhasil login via Google',
        'user' => $user,
        'token' => $token,
    ]);
}
    /**
     * REGISTER
     * Menangani pendaftaran user baru (default role: peminjam)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'nis'      => 'nullable|string|unique:users', 
            'password' => 'required|string|min:8|confirmed',
            // jurusan_id bisa ditambahkan di sini kalau ada di form register
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid', 
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'nis'      => $request->nis,
                'password' => Hash::make($request->password),
                'role'     => 'peminjam', // Default role
                'status'   => 'aktif',    // Default status
            ]);

            // Kirim email verifikasi
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email Anda untuk verifikasi.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Register Error:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mendaftar, terjadi kesalahan server.'], 500);
        }
    }

    /**
     * VERIFY EMAIL
     */
    public function verify(Request $request)
    {
        Log::info('--- DEBUG: REQUEST VERIFIKASI DITERIMA ---');

        if (!$request->hasValidSignature()) {
            Log::warning('DEBUG: Signature Invalid', ['url' => $request->fullUrl()]);
            return response()->json(['message' => 'Link verifikasi tidak valid atau sudah kadaluarsa.'], 403);
        }

        try {
            $user = User::findOrFail($request->route('id'));

            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                event(new Verified($user));
                Log::info('DEBUG: Verifikasi Berhasil', ['user_id' => $user->id]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Email berhasil diverifikasi!',
                'token'   => $token,
                'user'    => $user // 🔥 FIX: Kirim seluruh object user, password otomatis ke-hide oleh Model
            ]);

        } catch (\Exception $e) {
            Log::error('DEBUG: Gagal Verifikasi', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan server.'], 500);
        }
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        Log::info('Login Attempt:', ['email' => $request->email]);

        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Input tidak valid', 'errors' => $validator->errors()], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Cek Kredensial
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Email atau password salah'], 401);
            }

            // Cek Verifikasi Email (Opsional, matikan jika tidak perlu)
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email belum diverifikasi. Silakan cek inbox Anda.'], 403);
            }

            // Buat Token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Login Success:', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Login berhasil',
                'token'   => $token,
                'user'    => $user, // 🔥 FIX UTAMA: Tidak pakai only(). Semua field (role, status, nis, jurusan_id) otomatis terkirim!
            ]);

        } catch (\Exception $e) {
            Log::error('Login Error:', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan pada server'], 500);
        }
    }

    /**
     * ME (GET CURRENT USER)
     */
    public function me(Request $request) 
    { 
        return response()->json($request->user()); 
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout']);
    }
}