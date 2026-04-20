<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    public function index(Request $request)
    {
        // Menampilkan log dengan relasi user dan target model (loggable)
        $query = LogAktivitas::with(['user:id,name,role', 'loggable']);

        // Filter sederhana jika dibutuhkan
        if ($request->has('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        // Ambil data terbaru dengan pagination (sesuai standar Laravel)
        $logs = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat aktivitas berhasil dimuat',
            'data'    => $logs
        ]);
    }

    public function show($id)
    {
        $log = LogAktivitas::with(['user', 'loggable'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data'    => $log
        ]);
    }
}