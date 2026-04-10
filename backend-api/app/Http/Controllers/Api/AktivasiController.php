<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AktivasiController extends Controller
{
    public function cekNis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nis' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'NIS wajib diisi'], 422);
        }

        $user = User::where('nis', $request->nis)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'NIS tidak terdaftar dalam sistem.'], 404);
        }

        if ($user->status === 'aktif') {
            return response()->json(['success' => false, 'message' => 'Akun ini sudah aktif.'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'NIS ditemukan.',
            'data' => [
                'name' => $user->name,
                'nis'  => $user->nis
            ]
        ], 200);
    }


    public function simpanPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:users,id',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $user = User::findOrFail($request->id);
    
    $user->update([
        'password' => bcrypt($request->password), // Laravel otomatis nge-hash tapi lebih aman pake ini
        'status' => 'aktif', // Aktifkan akun di sini
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password berhasil disimpan, akun Anda kini aktif!'
    ]);
}

    public function aktivasi(Request $request)
    {
        Log::info('Mencoba mengirim email aktivasi ke: ' . $request->email);

        $validator = Validator::make($request->all(), [
            'nis'   => 'required|exists:users,nis',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email salah atau sudah terdaftar.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('nis', $request->nis)->first();
            
            // Simpan email baru
            $user->update(['email' => $request->email]);

            // KIRIM EMAIL VERIFIKASI (WAJIB JALAN)
            $user->sendEmailVerificationNotification();

            Log::info('Email berhasil dikirim via Mailtrap untuk NIS: ' . $request->nis);

            return response()->json([
                'success' => true,
                'message' => 'Link aktivasi telah dikirim ke ' . $request->email,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Gagal kirim email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage()
            ], 500);
        }
    }
}