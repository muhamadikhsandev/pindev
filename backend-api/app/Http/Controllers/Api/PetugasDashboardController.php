<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Denda;
use Carbon\Carbon;

class PetugasDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        // 1. Summary Cards (Fokus Operasional)
        $menungguPetugas = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PETUGAS)->count();
        $sedangDipinjam = Peminjaman::where('status', Peminjaman::STATUS_DIPINJAM)->count();
        $menungguPengecekan = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PENGECEKAN)->count();
        
        // Denda yang dibuat hari ini
        $dendaHariIni = Denda::whereDate('created_at', $today)->sum('jumlah_denda');

        // 2. Statistik Harian (Header)
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

        // 4. Tugas Hari Ini (Task List Gabungan)
        // Ambil data yang menunggu verifikasi
        $tasksVerifikasi = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PETUGAS)
            ->oldest() // Ambil yang paling lama nunggu
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'kode' => $p->kode_peminjaman,
                    'type' => 'Verifikasi',
                    'message' => 'Menunggu verifikasi pengajuan',
                    'time' => Carbon::parse($p->created_at)->diffForHumans()
                ];
            });

        // Ambil data yang menunggu pengecekan alat (pengembalian)
        $tasksPengecekan = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PENGECEKAN)
            ->oldest()
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'kode' => $p->kode_peminjaman,
                    'type' => 'Pengecekan',
                    'message' => 'Cek kondisi fisik alat yang kembali',
                    'time' => Carbon::parse($p->updated_at)->diffForHumans()
                ];
            });

        // Ambil data keterlambatan (Status dipinjam, tapi tanggal rencana kembali sudah lewat)
        $tasksTerlambat = Peminjaman::where('status', Peminjaman::STATUS_DIPINJAM)
            ->whereDate('tanggal_rencana_kembali', '<', $today)
            ->latest()
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'kode' => $p->kode_peminjaman,
                    'type' => 'Terlambat',
                    'message' => 'Melewati batas waktu pengembalian',
                    'time' => 'Telat ' . Carbon::parse($p->tanggal_rencana_kembali)->diffInDays($today) . ' Hari'
                ];
            });

        // Gabungkan semua task, ambil maksimal 8 tugas untuk ditampilkan di UI
        $allTasks = $tasksVerifikasi
            ->merge($tasksPengecekan)
            ->merge($tasksTerlambat)
            ->take(8)
            ->values();

        // 5. Tabel: Aktivitas Terakhir
        $recentActivities = Peminjaman::with(['user', 'alat'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($p) {
                $namaBarang = 'Unknown';
                if ($p->alat && $p->alat->count() > 0) {
                    $namaBarang = $p->alat->first()->nama_alat;
                    if ($p->alat->count() > 1) {
                        $namaBarang .= ' (+' . ($p->alat->count() - 1) . ')';
                    }
                }

                return [
                    'name' => ($p->user->name ?? 'User') . ' - ' . $namaBarang,
                    'code' => $p->kode_peminjaman,
                    'date' => Carbon::parse($p->created_at)->format('d M Y, H:i'),
                    'status' => $p->status
                ];
            });

        // Response Assembly
        return response()->json([
            'stats' => [
                'menunggu_petugas' => $menungguPetugas,
                'sedang_dipinjam' => $sedangDipinjam,
                'menunggu_pengecekan' => $menungguPengecekan,
                'denda_hari_ini' => (int) $dendaHariIni,
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