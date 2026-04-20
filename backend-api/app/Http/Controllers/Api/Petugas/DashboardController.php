<?php

namespace App\Http\Controllers\Api\Petugas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Denda;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        // 1. Summary Cards (Fokus Operasional)
        $menungguPetugas = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PETUGAS)->count();
        $sedangDipinjam = Peminjaman::where('status', Peminjaman::STATUS_DIPINJAM)->count();
        $menungguPengecekan = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PENGECEKAN)->count();
        
        // 🔥 FIX: Ambil TOTAL akumulasi denda, bukan cuma hari ini
        $totalDenda = Denda::sum('jumlah_denda');

        // 2. Statistik Harian (Informasi Tambahan)
        $dipinjamHariIni = Peminjaman::whereDate('tanggal_pinjam', $today)->count();
        $dikembalikanHariIni = Pengembalian::whereDate('tanggal_kembali', $today)->count();

        // 3. Chart Data (7 Hari Terakhir)
        $chartCategories = [];
        $peminjamanSeries = [];
        $pengembalianSeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartCategories[] = $date->format('d M');
            $peminjamanSeries[] = Peminjaman::whereDate('tanggal_pinjam', $date->toDateString())->count();
            $pengembalianSeries[] = Pengembalian::whereDate('tanggal_kembali', $date->toDateString())->count();
        }

        // 4. Tasks Terintegrasi (Verifikasi, Pengecekan, Terlambat)
        $tasksVerifikasi = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PETUGAS)
            ->oldest()->take(5)->get()
            ->map(fn($p) => [
                'id' => $p->id, 'kode' => $p->kode_peminjaman, 'type' => 'Verifikasi',
                'message' => 'Menunggu verifikasi pengajuan', 'time' => Carbon::parse($p->created_at)->diffForHumans()
            ]);

        $tasksPengecekan = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PENGECEKAN)
            ->oldest()->take(5)->get()
            ->map(fn($p) => [
                'id' => $p->id, 'kode' => $p->kode_peminjaman, 'type' => 'Pengecekan',
                'message' => 'Cek fisik alat kembali', 'time' => Carbon::parse($p->updated_at)->diffForHumans()
            ]);

        $allTasks = $tasksVerifikasi->merge($tasksPengecekan)->take(8)->values();

        // 5. Recent Activities
        $recentActivities = Peminjaman::with(['user', 'alat'])->latest()->take(5)->get()
            ->map(function($p) {
                $namaBarang = $p->alat->first()->nama_alat ?? 'Alat';
                if ($p->alat->count() > 1) $namaBarang .= ' (+' . ($p->alat->count() - 1) . ')';
                return [
                    'name' => ($p->user->name ?? 'User') . ' - ' . $namaBarang,
                    'code' => $p->kode_peminjaman,
                    'date' => Carbon::parse($p->created_at)->format('d M Y, H:i'),
                    'status' => $p->status
                ];
            });

        return response()->json([
            'stats' => [
                'menunggu_petugas' => $menungguPetugas,
                'sedang_dipinjam' => $sedangDipinjam,
                'menunggu_pengecekan' => $menungguPengecekan,
                'total_denda' => (int) $totalDenda, // Key diperbarui
            ],
            'daily_stats' => [
                'dipinjam' => $dipinjamHariIni,
                'dikembalikan' => $dikembalikanHariIni,
            ],
            'chart' => [
                'categories' => $chartCategories,
                'series' => [
                    ['name' => 'Dipinjam', 'data' => $peminjamanSeries],
                    ['name' => 'Dikembalikan', 'data' => $pengembalianSeries]
                ]
            ],
            'tasks' => $allTasks,
            'recent_activities' => $recentActivities
        ]);
    }
}