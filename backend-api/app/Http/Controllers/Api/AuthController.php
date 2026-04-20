<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    /**
     * 🔐 LOGIN (Sistem Pindev)
     * Menggunakan Laravel Sanctum & Mendukung Fitur Remember Me
     */
    public function login(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
            'remember' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Input tidak valid',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Ambil status 'Remember Me' dari Request
        $remember = $request->boolean('remember');

        // 3. Cek Kredensial (Email & Password)
        // PENTING: Variabel $remember dimasukkan ke argumen kedua agar remember_token di DB terisi
        if (!Auth::attempt($request->only('email', 'password'), $remember)) {
            return response()->json([
                'message' => 'Email atau kata sandi salah.'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        /**
         * 4. 🛡️ FILTER KEAMANAN STATUS
         * Jika akun tidak memenuhi syarat, batalkan login
         */
        
        // A. Cek Akun Nonaktif
        if ($user->isNonActive()) {
            Auth::logout();
            $user->tokens()->delete();
            return response()->json(['message' => 'Akun Anda dinonaktifkan. Hubungi Admin.'], 403);
        }

        // B. Cek Status Resign
        if ($user->isResign()) {
            Auth::logout();
            $user->tokens()->delete();
            $msg = match(true) {
                $user->isAdmin(), $user->isPetugas() => 'Staf sudah tidak bertugas lagi.',
                $user->isGuru() => 'Guru sudah pindah tugas.',
                $user->isSiswa() => 'Siswa sudah tidak terdaftar.',
                default => 'Akun tidak aktif (Resign).'
            };
            return response()->json(['message' => $msg], 403);
        }

        // C. Cek Status Lulus (Khusus Siswa)
        if ($user->isSiswa() && $user->isLulus()) {
            Auth::logout();
            return response()->json(['message' => 'Akses ditolak. Alumni tidak diizinkan.'], 403);
        }

        // D. Cek Verifikasi Email (Wajib untuk Peminjam)
        if ($user->isPeminjam() && !$user->hasVerifiedEmail()) {
            Auth::logout();
            return response()->json(['message' => 'Email belum diverifikasi. Silakan cek inbox Anda.'], 403);
        }

        /**
         * 5. ✅ GENERATE TOKEN & RESPONSE
         */
        try {
            // Jika tidak pilih remember, hapus token lama agar session hanya aktif di satu tempat
            if (!$remember) {
                $user->tokens()->delete(); 
            }

            // Buat Token Sanctum baru
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message'      => 'Selamat datang, ' . $user->name,
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user->load(['jurusan', 'kelas'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan sistem saat login.'], 500);
        }
    }

    /**
     * 🚪 LOGOUT
     * Menghapus Token Sanctum & Mengakhiri Session
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            // Hapus token yang digunakan saat ini
            $request->user()->currentAccessToken()->delete();
        }

        Auth::logout();

        return response()->json([
            'message' => 'Berhasil keluar dari sistem Pindev.'
        ]);
    }

    /**
     * 👤 ME (Cek Profil User)
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load(['jurusan', 'kelas']));
    }

    /**
     * 📝 REGISTER (Pendaftaran Peminjam Baru)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:users',
            'nomor_identitas' => 'required|string|unique:users',
            'status_peminjam' => 'required|in:GURU,SISWA',
            'password'        => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data pendaftaran tidak valid', 
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'nomor_identitas' => $request->nomor_identitas,
                'status_peminjam' => $request->status_peminjam,
                'password'        => Hash::make($request->password),
                'role'            => 'peminjam',
                'status'          => 'aktif',
            ]);

            // Kirim link verifikasi email
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Pendaftaran berhasil. Silakan cek email untuk verifikasi.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mendaftar, terjadi gangguan.'], 500);
        }
    }

    /**
     * 📧 VERIFY EMAIL (Endpoint Verifikasi)
     */
    public function verify(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Link verifikasi tidak valid atau sudah kedaluwarsa.'], 403);
        }

        $user = User::findOrFail($request->route('id'));

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email berhasil diverifikasi.', 
            'user'    => $user
        ]);
    }

    /**
     * 🔑 FORGOT PASSWORD (Kirim Link Reset)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link reset kata sandi telah dikirim ke email Anda.'])
            : response()->json(['message' => 'Gagal mengirim email reset.'], 400);
    }
}