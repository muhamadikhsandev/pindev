<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Denda;
use App\Models\DetailPeminjaman;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $userId = $user->id;

            // --- 1. STATS CARDS ---
            
            // Hitung yang sedang dipinjam
            $sedangDipinjam = Peminjaman::where('user_id', $userId)
                ->where('status', 'Dipinjam') 
                ->count();

            // Hitung Menunggu (Petugas + Admin)
            // Sesuai request: "Menunggu" diganti "Menunggu Petugas" atau "Menunggu Admin"
            $menungguValidasi = Peminjaman::where('user_id', $userId)
                ->whereIn('status', ['Menunggu Petugas', 'Menunggu Admin'])
                ->count();

            // Total Denda
            $totalDenda = Denda::whereHas('pengembalian.peminjaman', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->sum('jumlah_denda');

            // --- 2. WARNINGS / STATUS TERDEKAT (LOGIKA BARU) ---
            // Kita cari peminjaman aktif ('Dipinjam') atau yang sudah lewat tapi belum balik ('Terlambat' jika ada status itu)
            $activeLoans = Peminjaman::with('detail_peminjaman.alat')
                ->where('user_id', $userId)
                ->where('status', 'Dipinjam') // Hanya cek yang sedang dipinjam
                ->orderBy('tanggal_rencana_kembali', 'asc') // Urutkan dari yang paling cepat harus kembali
                ->get();

            $warnings = [];
            $today = Carbon::now()->startOfDay();

            foreach ($activeLoans as $loan) {
                // Ambil nama alat pertama untuk display
                $alatPertama = $loan->detail_peminjaman->first()?->alat;
                $namaAlat = $alatPertama ? $alatPertama->nama_alat : 'Peminjaman #' . $loan->id;
                
                if ($loan->detail_peminjaman->count() > 1) {
                    $namaAlat .= " (+" . ($loan->detail_peminjaman->count() - 1) . " alat)";
                }

                // Gunakan tanggal_rencana_kembali sebagai deadline
                $deadline = Carbon::parse($loan->tanggal_rencana_kembali)->startOfDay();
                
                // Hitung selisih hari: 
                // Negatif = Lewat deadline (Terlambat)
                // 0 = Hari ini
                // Positif = Masih ada waktu
                $diff = $today->diffInDays($deadline, false); 

                // Logika Pesan Warning
                if ($diff < 0) {
                    // SUDAH LEWAT (Terlambat)
                    $warnings[] = [
                        'message' => "{$namaAlat} terlambat " . abs($diff) . " hari (Wajib Kembali: " . $deadline->format('d M') . ")",
                        'type' => 'danger'
                    ];
                } elseif ($diff == 0) {
                    // HARI INI
                    $warnings[] = [
                        'message' => "{$namaAlat} harus dikembalikan HARI INI.",
                        'type' => 'warning'
                    ];
                } elseif ($diff <= 3) {
                    // KURANG DARI 3 HARI
                    $warnings[] = [
                        'message' => "{$namaAlat} dikembalikan {$diff} hari lagi (" . $deadline->format('d M') . ").",
                        'type' => 'warning'
                    ];
                }
            }

            // --- 3. CHART DATA (7 Hari Terakhir) ---
            $chartCategories = [];
            $dataPeminjaman = [];
            $dataPengembalian = []; // Opsional jika ingin ditampilkan

            Carbon::setLocale('id');

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $formattedDate = $date->format('Y-m-d');
                $chartCategories[] = $date->translatedFormat('D'); // Sen, Sel...

                $dataPeminjaman[] = Peminjaman::where('user_id', $userId)
                    ->whereDate('tanggal_pinjam', $formattedDate)
                    ->count();
            }

            // --- 4. KATEGORI FAVORIT ---
            $kategoriFavorit = DetailPeminjaman::join('peminjaman', 'detail_peminjaman.peminjaman_id', '=', 'peminjaman.id')
                ->join('alat', 'detail_peminjaman.alat_id', '=', 'alat.id')
                ->join('kategori_alat', 'alat.kategori_id', '=', 'kategori_alat.id')
                ->where('peminjaman.user_id', $userId)
                ->select('kategori_alat.nama_kategori', DB::raw('count(*) as total'))
                ->groupBy('kategori_alat.nama_kategori')
                ->orderByDesc('total')
                ->limit(4)
                ->get();
            
            $totalAlatDipinjam = $kategoriFavorit->sum('total');

            $categoriesFormatted = $kategoriFavorit->map(function($item) use ($totalAlatDipinjam) {
                $colors = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-slate-400'];
                return [
                    'label' => $item->nama_kategori,
                    'count' => $item->total,
                    'percent' => $totalAlatDipinjam > 0 ? round(($item->total / $totalAlatDipinjam) * 100) : 0,
                    'color' => $colors[rand(0, 4)] 
                ];
            });

            // --- 5. AKTIVITAS TERAKHIR ---
            $recentActivities = Peminjaman::with(['detail_peminjaman.alat'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($peminjaman) {
                    $alatPertama = $peminjaman->detail_peminjaman->first()?->alat;
                    $namaAlat = $alatPertama ? $alatPertama->nama_alat : 'Peminjaman Alat';
                    $kodeAlat = $alatPertama ? $alatPertama->kode_alat ?? 'ID-'.$alatPertama->id : '-';
                    
                    if ($peminjaman->detail_peminjaman->count() > 1) {
                        $namaAlat .= " (+" . ($peminjaman->detail_peminjaman->count() - 1) . ")";
                    }

                    return [
                        'id' => $peminjaman->id,
                        'name' => $namaAlat,
                        'code' => (string) $kodeAlat,
                        'date' => Carbon::parse($peminjaman->tanggal_pinjam)->translatedFormat('d M Y'),
                        // Tambahkan Deadline agar Frontend bisa menampilkan tanggal kembali
                        'deadline' => Carbon::parse($peminjaman->tanggal_rencana_kembali)->translatedFormat('d M Y'), 
                        'status' => $peminjaman->status,
                    ];
                });

            return response()->json([
                'stats' => [
                    'sedang_dipinjam' => $sedangDipinjam,
                    'menunggu_validasi' => $menungguValidasi,
                    'total_denda' => (int) $totalDenda,
                ],
                'chart' => [
                    'categories' => $chartCategories,
                    'series' => [
                        ['name' => 'Peminjaman', 'data' => $dataPeminjaman],
                    ]
                ],
                'categories' => $categoriesFormatted,
                'recent_activities' => $recentActivities,
                'warnings' => $warnings // Array warning dinamis dari backend
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error', 'error' => $e->getMessage()], 500);
        }
    }
}