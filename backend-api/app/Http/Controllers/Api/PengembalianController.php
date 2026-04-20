<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengembalian;
use App\Models\Peminjaman;
use App\Models\AlatUnit;
use App\Traits\BulkActionTrait; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Exports\PengembalianExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PengembalianController extends Controller
{
    use BulkActionTrait;

    protected $model;

    public function __construct(Pengembalian $pengembalian)
    {
        $this->model = $pengembalian;
    }

    public function export(Request $request)
    {
        try {
            $type = $request->query('type', 'pdf');
            $data = Pengembalian::with(['peminjaman.peminjam'])->latest()->get();

            if ($type === 'excel') {
                return Excel::download(new PengembalianExport, 'Laporan_Pengembalian.xlsx');
            }

            $pdf = Pdf::loadView('exports.pengembalian_pdf', ['data' => $data]);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('Laporan_Pengembalian_' . date('Ymd') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan daftar pengembalian
     */
    public function index(Request $request)
    {
        try {
            // 🚨 BIANG KEROK DIPERBAIKI DI SINI 🚨
            // Sebelumnya cuma ['peminjaman.peminjam']
            // Sekarang bawa semua relasi detail dan alatnya sekalian!
            $query = Pengembalian::with([
                'peminjaman.peminjam',
                'peminjaman.detail_peminjaman.alat',
                'peminjaman.detail_peminjaman.unit.alat'
            ]);

            if ($request->search) {
                $query->whereHas('peminjaman', function($q) use ($request) {
                    $q->where('kode_peminjaman', 'like', "%{$request->search}%");
                });
            }

            $data = $query->latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * METHOD BARU: Untuk melayani API endpoint /riwayat-pengembalian (Frontend RiwayatKembaliClient)
     */
    public function riwayatPengembalianUser(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = Pengembalian::with([
                'peminjaman.detail_peminjaman.unit.alat', 
                'peminjaman.detail_peminjaman.alat',
                'denda' // Eager load denda
            ]);

            if ($user && $user->role !== 'admin') {
                $query->whereHas('peminjaman', function($q) use ($user) {
                    $q->where('user_id', $user->id); 
                });
            }

            $pengembalian = $query->latest()->get();

            $formattedData = $pengembalian->map(function ($item) {
                $namaAlatList = [];
                $fotoPath = null;

                if ($item->peminjaman && $item->peminjaman->detail_peminjaman) {
                    foreach ($item->peminjaman->detail_peminjaman as $detail) {
                        $alat = $detail->alat ?? ($detail->unit->alat ?? null);
                        if ($alat) {
                            $namaAlatList[] = $alat->nama_alat ?? ($alat->nama ?? 'Alat');
                            if (!$fotoPath) {
                                $fotoPath = $alat->foto_path ?? ($alat->gambar ?? null);
                            }
                        }
                    }
                }

                return [
                    'id' => $item->id,
                    'nama_alat' => count($namaAlatList) > 0 ? implode(', ', array_unique($namaAlatList)) : 'Transaksi #' . ($item->peminjaman->kode_peminjaman ?? $item->peminjaman_id),
                    'tanggal_kembali' => $item->tanggal_kembali,
                    'kondisi' => $item->kondisi_kembali,
                    'kondisi_kembali' => $item->kondisi_kembali,
                    'catatan' => $item->catatan,
                    // --- FIX DI SINI: Samakan dengan Model Denda ---
                    'denda' => $item->denda ? [
                        'jumlah_denda' => $item->denda->jumlah_denda, // PAKAI jumlah_denda, BUKAN nominal
                        'keterangan' => $item->denda->keterangan,
                        'status' => $item->denda->status
                    ] : null,
                    'foto_path' => $fotoPath,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'message' => 'Berhasil mengambil riwayat pengembalian'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOptions()
    {
        try {
            $options = Peminjaman::with('peminjam')
                ->where('status', 'Dipinjam')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'kode_peminjaman' => $item->kode_peminjaman ?? "#TRX-{$item->id}",
                        'nama_peminjam' => $item->peminjam->name ?? 'User'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $options
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil opsi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'peminjaman_id' => 'required|exists:peminjaman,id',
            'tanggal_kembali' => 'required|date',
            'kondisi_kembali' => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $peminjaman = Peminjaman::with('detail_peminjaman.unit')->findOrFail($request->peminjaman_id);

            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $request->peminjaman_id,
                'tanggal_kembali' => $request->tanggal_kembali,
                'kondisi_kembali' => $request->kondisi_kembali,
                'catatan' => $request->catatan ?? '-',
            ]);

            // Update Unit Alat
            foreach ($peminjaman->detail_peminjaman as $detail) {
                if ($detail->unit) {
                    $detail->unit->update([
                        'status' => 'Tersedia',
                        'kondisi' => $request->kondisi_kembali === 'Baik' ? $detail->unit->kondisi : $request->kondisi_kembali
                    ]);
                }
            }

            $peminjaman->update(['status' => 'Selesai']);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Berhasil', 
                'data' => $pengembalian
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}