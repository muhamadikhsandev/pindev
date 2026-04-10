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
// Tambahkan import di bagian atas controller
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
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PengembalianExport, 'Laporan_Pengembalian.xlsx');
        }

        // Generate PDF menggunakan DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pengembalian_pdf', ['data' => $data]);
        
        // Atur ukuran kertas
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Pengembalian_' . date('Ymd') . '.pdf');
    } catch (\Exception $e) {
        return response()->json(['message' => 'Gagal export: ' . $e->getMessage()], 500);
    }
}
    /**
     * Menampilkan daftar pengembalian
     */
    public function index(Request $request)
    {
        try {
            $query = Pengembalian::with(['peminjaman.peminjam']);

            if ($request->search) {
                $query->whereHas('peminjaman', function($q) use ($request) {
                    $q->where('kode_peminjaman', 'like', "%{$request->search}%");
                });
            }

            $data = $query->latest()->get();
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengambil opsi peminjaman untuk dropdown
     * FIX: Mengembalikan key 'data' agar sesuai dengan usePengembalian.ts
     */
    public function getOptions()
    {
        try {
            // Ambil peminjaman yang statusnya sedang dipinjam
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

            return response()->json(['data' => $options], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil opsi: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'peminjaman_id' => 'required|exists:peminjaman,id',
            'tanggal_kembali' => 'required|date',
            'kondisi_kembali' => 'required|string',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

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
            return response()->json(['message' => 'Berhasil', 'data' => $pengembalian], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}