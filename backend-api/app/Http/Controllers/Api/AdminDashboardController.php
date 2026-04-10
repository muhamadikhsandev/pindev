<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\KategoriAlat;
use App\Models\Pengembalian;
use App\Models\Denda;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // WAJIB TAMBAHKAN INI

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Summary Cards
        $totalAlat = Alat::count();
        
        // Sesuai dengan konstanta di Model Peminjaman
        $menungguValidasi = Peminjaman::whereIn('status', [
            Peminjaman::STATUS_MENUNGGU_PETUGAS,
            Peminjaman::STATUS_MENUNGGU_ADMIN
        ])->count();
        
        $pengembalianBermasalah = Peminjaman::where('status', Peminjaman::STATUS_BERMASALAH)->count();
        
        // Total Denda dihitung langsung dari model Denda
        $totalDenda = Denda::sum('jumlah_denda');

        // 2. System Status Widget
        $stokHabis = Alat::where('stok', '<=', 0)->count();
        $alatRusak = Alat::where('kondisi', 'rusak')->count();

        // 3. Chart Data (7 Hari Terakhir)
        $chartCategories = [];
        $peminjamanSeries = [];
        $pengembalianSeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartCategories[] = $date->format('d M');
            
            // Hitung dari tanggal pinjam
            $peminjamanSeries[] = Peminjaman::whereDate('tanggal_pinjam', $date->toDateString())->count();
            
            // Hitung dari tanggal kembali (Menggunakan model Pengembalian)
            $pengembalianSeries[] = Pengembalian::whereDate('tanggal_kembali', $date->toDateString())->count();
        }

        // 4. Categories (Alat Paling Sering Dipinjam)
        // Menggunakan Query Builder DB Join karena struktur DB lebih aman dihitung dari detail_peminjaman
        $topCategories = DB::table('detail_peminjaman')
            ->join('alat', 'detail_peminjaman.alat_id', '=', 'alat.id')
            ->join('kategori_alat', 'alat.kategori_id', '=', 'kategori_alat.id')
            ->select('kategori_alat.nama_kategori', DB::raw('count(detail_peminjaman.id) as peminjaman_count'))
            ->groupBy('kategori_alat.id', 'kategori_alat.nama_kategori')
            ->orderByDesc('peminjaman_count')
            ->take(4)
            ->get();
            
        $totalTransaksi = DB::table('detail_peminjaman')->count() ?: 1; 
        $colors = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500'];
        
        $categoriesData = $topCategories->map(function($cat, $index) use ($totalTransaksi, $colors) {
            return [
                'label' => $cat->nama_kategori,
                'count' => $cat->peminjaman_count,
                'percent' => $totalTransaksi > 0 ? round(($cat->peminjaman_count / $totalTransaksi) * 100) : 0,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // 5. Tabel: 5 Pengajuan Terbaru
        $recentRequests = Peminjaman::with(['user', 'alat'])
            ->whereIn('status', [Peminjaman::STATUS_MENUNGGU_PETUGAS, Peminjaman::STATUS_MENUNGGU_ADMIN])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($pinjam) {
                // Relasi $pinjam->alat adalah hasManyThrough (mengembalikan banyak data / Collection)
                // Jadi kita ambil nama_alat dari item pertama saja
                $namaAlat = 'Unknown';
                if ($pinjam->alat && $pinjam->alat->count() > 0) {
                    $namaAlat = $pinjam->alat->first()->nama_alat;
                    // Jika pinjam lebih dari 1 alat, tambahkan teks indikator
                    if ($pinjam->alat->count() > 1) {
                        $namaAlat .= ' (+' . ($pinjam->alat->count() - 1) . ' alat)';
                    }
                }

                return [
                    'id' => $pinjam->id,
                    'user_name' => $pinjam->user->name ?? 'Unknown',
                    'item_name' => $namaAlat,
                    'date' => Carbon::parse($pinjam->created_at)->format('d M Y'),
                    'status' => $pinjam->status // "Menunggu Admin" atau "Menunggu Petugas"
                ];
            });

        // Response Assembly
        return response()->json([
            'stats' => [
                'total_alat' => $totalAlat,
                'menunggu_validasi' => $menungguValidasi,
                'pengembalian_bermasalah' => $pengembalianBermasalah,
                'total_denda_belum_lunas' => (int) $totalDenda,
            ],
            'system_status' => [
                'stok_habis' => $stokHabis,
                'alat_rusak' => $alatRusak,
            ],
            'chart' => [
                'categories' => $chartCategories,
                'series' => [
                    ['name' => 'Dipinjam', 'data' => $peminjamanSeries],
                    ['name' => 'Dikembalikan', 'data' => $pengembalianSeries]
                ]
            ],
            'categories' => $categoriesData,
            'recent_requests' => $recentRequests
        ]);
    }
}