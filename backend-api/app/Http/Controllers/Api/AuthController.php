<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    /**
     * 🔐 LOGIN (Pindev Lab PPLG)
     */
    public function login(Request $request)
    {
        Log::info('--- LOGIN ATTEMPT START ---', ['email' => $request->email]);

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            Log::warning('Login Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Input tidak valid',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 1. Cek Kredensial
        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning('Login Failed: Wrong Credentials', ['email' => $request->email]);
            return response()->json([
                'message' => 'Email atau kata sandi salah.'
            ], 401);
        }

        $user = Auth::user();
        Log::info('Credentials Correct. Checking User Status...', [
            'user_id' => $user->id,
            'role'    => $user->role,
            'status'  => $user->status
        ]);

        /**
         * 2. 🛡️ FILTER KEAMANAN STATUS (LOGGED)
         */
        
        // A. Cek Nonaktif
        if ($user->isNonActive()) {
            Log::error('Access Denied: Account Non-Active', ['user_id' => $user->id]);
            Auth::logout();
            return response()->json(['message' => 'Akun Anda dinonaktifkan. Hubungi Admin Lab PPLG.'], 403);
        }

        // B. Cek Resign (Spesifik per Role)
        if ($user->isResign()) {
            Log::error('Access Denied: Account Resigned', ['user_id' => $user->id, 'role' => $user->role]);
            Auth::logout();
            
            $msg = match(true) {
                $user->isAdmin(), $user->isPetugas() => 'Staf sudah tidak bertugas lagi di Lab.',
                $user->isGuru() => 'Guru sudah tidak lagi mengajar/pindah tugas.',
                $user->isSiswa() => 'Siswa sudah tidak terdaftar (Resign/Pindah Sekolah).',
                default => 'Akun tidak aktif (Resign).'
            };
            return response()->json(['message' => $msg], 403);
        }

        // C. Cek Lulus (Khusus Siswa)
        if ($user->isSiswa() && $user->isLulus()) {
            Log::error('Access Denied: Student Graduated', ['user_id' => $user->id]);
            Auth::logout();
            return response()->json(['message' => 'Akses ditolak. Alumni tidak diizinkan meminjam device.'], 403);
        }

        // D. Cek Verifikasi Email (Khusus Peminjam)
        if ($user->isPeminjam() && !$user->hasVerifiedEmail()) {
            Log::warning('Access Denied: Email Not Verified', ['user_id' => $user->id]);
            Auth::logout();
            return response()->json(['message' => 'Email belum diverifikasi. Cek inbox Anda.'], 403);
        }

        /**
         * 3. ✅ LOLOS FILTER - PROSES SESSION
         */
        try {
            if ($request->hasSession()) {
                $request->session()->regenerate();
                Log::info('Session Regenerated Successfully', ['session_id' => $request->session()->getId()]);
            } else {
                Log::warning('Login Proceeding without Session Store (Check Middleware)');
            }

            Log::info('--- LOGIN SUCCESS ---', [
                'user_id' => $user->id,
                'target_dashboard' => ($user->isAdmin() || $user->isPetugas()) ? $user->role : 'peminjam'
            ]);

            return response()->json([
                'message' => 'Selamat datang, ' . $user->name,
                'user'    => $user
            ], 200);

        } catch (\Exception $e) {
            Log::critical('Final Login Error:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan sistem saat login.'], 500);
        }
    }

    /**
     * 🚪 LOGOUT (LOGGED)
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        Log::info('User Logging Out...', ['user_id' => $user?->id]);

        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Log::info('Session Invalidated and Token Regenerated');
        }

        return response()->json(['message' => 'Berhasil keluar dari sistem Pindev.']);
    }

    /**
     * 👤 ME (Profile Data)
     */
    public function me(Request $request)
    {
        Log::info('Fetching User Profile (ME)', ['user_id' => $request->user()->id]);
        return response()->json($request->user()->load(['jurusan', 'kelas']));
    }

    /**
     * 📝 REGISTER (Siswa & Guru)
     */
    public function register(Request $request)
    {
        Log::info('Register Attempt:', ['email' => $request->email, 'role' => 'peminjam']);

        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:users',
            'nomor_identitas' => 'required|string|unique:users',
            'status_peminjam' => 'required|in:GURU,SISWA',
            'password'        => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Log::warning('Register Validation Failed:', $validator->errors()->toArray());
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
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

            Log::info('User Created, Sending Verification Email', ['user_id' => $user->id]);
            $user->sendEmailVerificationNotification();

            return response()->json(['message' => 'Pendaftaran berhasil. Silakan cek email.'], 201);
        } catch (\Exception $e) {
            Log::error('Register Exception:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mendaftar.'], 500);
        }
    }

    /**
     * 📧 VERIFY EMAIL
     */
    public function verify(Request $request)
    {
        Log::info('Email Verification Attempt', ['id' => $request->route('id')]);

        if (!$request->hasValidSignature()) {
            Log::warning('Email Verification: Invalid Signature');
            return response()->json(['message' => 'Link tidak valid.'], 403);
        }

        $user = User::findOrFail($request->route('id'));

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            Log::info('Email Verified Successfully', ['user_id' => $user->id]);
        }

        return response()->json(['message' => 'Email diverifikasi.', 'user' => $user]);
    }

    /**
     * 🔑 FORGOT PASSWORD
     */
    public function forgotPassword(Request $request)
    {
        Log::info('Forgot Password Requested', ['email' => $request->email]);
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link reset dikirim ke email.'])
            : response()->json(['message' => 'Gagal mengirim link.'], 400);
    }
}