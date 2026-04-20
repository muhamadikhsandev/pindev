<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Denda;
use App\Models\Pengembalian;
use App\Models\KategoriDenda;
use App\Models\Alat;
use App\Models\AlatUnit;
use App\Traits\BulkActionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Exports\DendaExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class DendaController extends Controller
{
    use BulkActionTrait;

    protected $model;

    public function __construct(Denda $denda)
    {
        $this->model = $denda;
    }

    /**
     * MENGAMBIL DATA DENDA KHUSUS USER YANG LOGIN (Untuk Peminjam)
     */
    public function dendaSaya(Request $request)
    {
        try {
            $user = $request->user();
            
            // Cari denda yang pengembalian -> peminjamannya milik user yang login
            $data = Denda::with(['pengembalian.peminjaman', 'kategori', 'alat'])
                ->whereHas('pengembalian.peminjaman', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data denda',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE STATUS LUNAS (Dipanggil setelah bayar QRIS/Manual)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $denda = Denda::findOrFail($id);
            $denda->update(['status' => Denda::STATUS_LUNAS]);

            return response()->json([
                'success' => true,
                'message' => 'Status denda berhasil diupdate menjadi Lunas',
                'data' => $denda
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update status'], 500);
        }
    }

    /**
     * Export Laporan ke Excel atau PDF
     */
    public function export(Request $request)
    {
        try {
            $type = $request->query('type', 'pdf');
            $data = Denda::with(['pengembalian.peminjaman.peminjam', 'kategori', 'alat'])->latest()->get();

            if ($type === 'excel') {
                return Excel::download(new DendaExport, 'Laporan_Denda_' . now()->format('Ymd_His') . '.xlsx');
            }

            $pdf = Pdf::loadView('exports.denda_pdf', ['denda' => $data]);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('Laporan_Denda_' . now()->format('Ymd_His') . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memproses export: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengambil data referensi untuk Form Denda (Untuk Admin)
     */
    public function getOptions()
    {
        try {
            $pengembalian = Pengembalian::with('peminjaman.peminjam')
                ->latest()
                ->get()
                ->map(function ($item) {
                    $kode = $item->peminjaman->kode_peminjaman ?? ('#RET-' . $item->id);
                    $nama = $item->peminjaman->peminjam->name ?? 'User Terhapus';
                    return [
                        'id' => $item->id,
                        'nama_kategori' => $kode . ' | ' . $nama,
                    ];
                });

            $kategori = KategoriDenda::select('id', 'nama_kategori', 'metode_denda', 'nilai_denda')->get();

            $alat = Alat::select('id', 'nama_alat', 'harga')
                ->whereHas('units', function($query) {
                    $query->where('kondisi', '!=', AlatUnit::KONDISI_RUSAK_BERAT);
                })
                ->get();

            return response()->json([
                'pengembalian' => $pengembalian,
                'kategori'     => $kategori,
                'alat'         => $alat
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil opsi: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = Denda::with(['pengembalian.peminjaman.peminjam', 'kategori', 'alat']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('pengembalian.peminjaman', function ($q) use ($search) {
                    $q->where('kode_peminjaman', 'like', "%{$search}%")
                      ->orWhereHas('peminjam', function($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return response()->json(['success' => true, 'data' => $query->latest()->get()], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pengembalian_id'   => 'required|exists:pengembalian,id',
            'kategori_denda_id' => 'required|exists:kategori_denda,id',
            'alat_id'           => 'nullable|exists:alat,id',
            'jumlah_denda'      => 'required|numeric|min:0',
            'status'            => 'required|in:' . Denda::STATUS_BELUM_BAYAR . ',' . Denda::STATUS_LUNAS,
            'keterangan'        => 'nullable|string'
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        try {
            $denda = Denda::create($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Data denda berhasil ditambahkan',
                'data'    => $denda->load(['pengembalian.peminjaman.peminjam', 'kategori', 'alat'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan denda'], 500);
        }
    }

    public function show($id)
    {
        try {
            $denda = Denda::with(['pengembalian.peminjaman.peminjam', 'kategori', 'alat'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $denda], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pengembalian_id'   => 'required|exists:pengembalian,id',
            'kategori_denda_id' => 'required|exists:kategori_denda,id',
            'alat_id'           => 'nullable|exists:alat,id',
            'jumlah_denda'      => 'required|numeric|min:0',
            'status'            => 'required|in:' . Denda::STATUS_BELUM_BAYAR . ',' . Denda::STATUS_LUNAS,
            'keterangan'        => 'nullable|string'
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        try {
            $denda = Denda::findOrFail($id);
            $denda->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Data denda berhasil diperbarui',
                'data'    => $denda->load(['pengembalian.peminjaman.peminjam', 'kategori', 'alat'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui denda'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $denda = Denda::findOrFail($id);
            $denda->delete();
            return response()->json(['success' => true, 'message' => 'Data denda berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data'], 500);
        }
    }
}