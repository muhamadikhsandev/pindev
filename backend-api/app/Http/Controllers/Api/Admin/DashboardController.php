<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Denda;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /**
         * =============================
         * 1. SUMMARY CARDS
         * =============================
         */
        $totalAlat = Alat::count();

        $menungguValidasi = Peminjaman::whereIn('status', [
            Peminjaman::STATUS_MENUNGGU_PETUGAS,
            Peminjaman::STATUS_MENUNGGU_ADMIN
        ])->count();

        // 🔥 FIX: gak pakai constant yang gak ada
        $pengembalianBermasalah = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PENGECEKAN)->count();

        $totalDenda = (int) Denda::sum('jumlah_denda');

        /**
         * =============================
         * 2. SYSTEM STATUS
         * =============================
         */
        $stokHabis = Alat::whereDoesntHave('units', function ($query) {
            $query->where('status', AlatUnit::STATUS_TERSEDIA);
        })->count();

        $alatRusak = AlatUnit::whereIn('kondisi', [
            AlatUnit::KONDISI_RUSAK_RINGAN,
            AlatUnit::KONDISI_RUSAK_BERAT
        ])->count();

        /**
         * =============================
         * 3. CHART (7 HARI TERAKHIR)
         * =============================
         */
        $chartCategories = [];
        $peminjamanSeries = [];
        $pengembalianSeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            $chartCategories[] = $date->format('d M');

            $peminjamanSeries[] = Peminjaman::whereDate('tanggal_pinjam', $date)->count();

            $pengembalianSeries[] = Pengembalian::whereDate('tanggal_kembali', $date)->count();
        }

        /**
         * =============================
         * 4. TOP KATEGORI
         * =============================
         */
        $topCategories = DB::table('detail_peminjaman')
            ->join('alat', 'detail_peminjaman.alat_id', '=', 'alat.id')
            ->join('kategori_alat', 'alat.kategori_id', '=', 'kategori_alat.id')
            ->select(
                'kategori_alat.nama_kategori',
                DB::raw('COUNT(detail_peminjaman.id) as total')
            )
            ->groupBy('kategori_alat.id', 'kategori_alat.nama_kategori')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        $totalTransaksi = DB::table('detail_peminjaman')->count() ?: 1;

        $colors = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500'];

        $categoriesData = $topCategories->map(function ($item, $index) use ($totalTransaksi, $colors) {
            return [
                'label' => $item->nama_kategori,
                'count' => (int) $item->total,
                'percent' => round(($item->total / $totalTransaksi) * 100),
                'color' => $colors[$index % count($colors)]
            ];
        });

        /**
         * =============================
         * 5. RECENT REQUESTS
         * =============================
         */
        $recentRequests = Peminjaman::with(['user', 'alat'])
            ->whereIn('status', [
                Peminjaman::STATUS_MENUNGGU_PETUGAS,
                Peminjaman::STATUS_MENUNGGU_ADMIN
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($pinjam) {

                $namaAlat = 'Tidak ada alat';

                if ($pinjam->alat && $pinjam->alat->count() > 0) {
                    $first = $pinjam->alat->first();
                    $namaAlat = $first->nama_alat;

                    if ($pinjam->alat->count() > 1) {
                        $namaAlat .= ' (+' . ($pinjam->alat->count() - 1) . ' alat)';
                    }
                }

                return [
                    'id' => $pinjam->id,
                    'user_name' => $pinjam->user->name ?? 'System',
                    'item_name' => $namaAlat,
                    'date' => $pinjam->created_at->format('d M Y'),
                    'status' => $pinjam->status
                ];
            });

        /**
         * =============================
         * RESPONSE FINAL
         * =============================
         */
        return response()->json([
            'stats' => [
                'total_alat' => $totalAlat,
                'menunggu_validasi' => $menungguValidasi,
                'pengembalian_bermasalah' => $pengembalianBermasalah,
                'total_denda_belum_lunas' => $totalDenda,
            ],
            'system_status' => [
                'stok_habis' => $stokHabis,
                'alat_rusak' => $alatRusak,
            ],
            'chart' => [
                'categories' => $chartCategories,
                'series' => [
                    ['name' => 'Dipinjam', 'data' => $peminjamanSeries],
                    ['name' => 'Dikembalikan', 'data' => $pengembalianSeries],
                ]
            ],
            'categories' => $categoriesData,
            'recent_requests' => $recentRequests
        ]);
    }
}