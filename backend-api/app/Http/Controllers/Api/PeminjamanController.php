<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\Pengembalian;
use App\Models\Denda;
use App\Models\KategoriDenda;
use App\Models\AlatUnit; 
use App\Models\Alat;
use App\Traits\BulkActionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Exports\PeminjamanExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


class PeminjamanController extends Controller
{
    use BulkActionTrait; 

    protected $model;

    public function __construct(Peminjaman $peminjaman)
    {
        $this->model = $peminjaman;
    }


public function export(Request $request)
    {
        try {
            $type = $request->query('type', 'pdf');
            
            // FIX: Perbaiki relasi eager loading menjadi 'detail_peminjaman'
            $data = Peminjaman::with(['peminjam', 'detail_peminjaman.alat', 'detail_peminjaman.unit.alat'])->latest()->get();

            if ($type === 'excel') {
                return Excel::download(new PeminjamanExport, 'Laporan_Peminjaman_' . date('Ymd_His') . '.xlsx');
            }

            // Generate PDF
            $pdf = Pdf::loadView('exports.peminjaman_pdf', ['data' => $data]);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('Laporan_Peminjaman_' . date('Ymd_His') . '.pdf');
            
        } catch (\Exception $e) {
            // Tulis ke log agar kalau error lagi kita tau persis penyebabnya
            \Illuminate\Support\Facades\Log::error('Export Peminjaman Error: ' . $e->getMessage());
            
            return response()->json(['message' => 'Gagal Export: ' . $e->getMessage()], 500);
        }
    }
    public function index()
    {
        try {
            $data = Peminjaman::with(['peminjam', 'detail_peminjaman.alat.kategori', 'detail_peminjaman.unit'])
                ->orderByRaw("FIELD(status, 'Menunggu Petugas', 'Menunggu Admin', 'Disetujui', 'Dipinjam', 'Menunggu Pengecekan', 'Bermasalah', 'Dikembalikan', 'Ditolak')")
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal mengambil data'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_pinjam'          => 'required|date',
            'tanggal_rencana_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'tujuan'                  => 'required|string',
            'items'                   => 'required|array|min:1',
            'items.*.id'              => 'required|exists:alat,id',
            'items.*.qty'             => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // FIX Intelephense P1013: Sekarang editor tahu $user adalah instance App\Models\User
            $user->load('jurusan');
            $jurusanName = $user->jurusan ? $user->jurusan->nama_jurusan : '-';

            $peminjaman = Peminjaman::create([
                'user_id'                 => $user->id,
                'jurusan'                 => $jurusanName, 
                'tanggal_pinjam'          => $request->tanggal_pinjam,
                'tanggal_rencana_kembali' => $request->tanggal_rencana_kembali,
                'tujuan'                  => $request->tujuan,
                'status'                  => 'Menunggu Petugas',
            ]);

            foreach ($request->items as $item) {
                // LOGIKA AUTO-ASSIGN UNIT FISIK
                $qtyDiminta = $item['qty'];
                
                $availableUnits = AlatUnit::where('alat_id', $item['id'])
                                          ->where('status', AlatUnit::STATUS_TERSEDIA)
                                          ->take($qtyDiminta)
                                          ->get();

                if ($availableUnits->count() < $qtyDiminta) {
                    $alat = Alat::find($item['id']);
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error', 
                        'message' => "Stok unit fisik '{$alat->nama_alat}' sedang dipinjam/rusak. Coba kurangi jumlah."
                    ], 422);
                }

                foreach ($availableUnits as $unit) {
                    DetailPeminjaman::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_id'       => $item['id'], 
                        'alat_unit_id'  => $unit->id,   
                        'jumlah'        => 1            
                    ]);

                    // Kunci unit ini jadi "DIPINJAM" agar tidak bentrok dengan transaksi lain
                    $unit->update(['status' => AlatUnit::STATUS_DIPINJAM]);
                }
            }

            // Bersihkan isi keranjang dari tabel cart_items
            DB::table('cart_items')->where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Peminjaman berhasil diajukan.', 'data' => $peminjaman], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Menunggu Petugas,Menunggu Admin,Disetujui,Dipinjam,Menunggu Pengecekan,Bermasalah,Dikembalikan,Ditolak',
            'catatan'=> 'nullable|string|max:1000', 
            'kategori_denda_id' => 'nullable|exists:kategori_denda,id',
            'alat_id'           => 'nullable|exists:alat,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::with('detail_peminjaman.unit')->findOrFail($id);
            $newStatus = $request->status;
            $updateData = ['status' => $newStatus];

            if ($request->filled('catatan')) {
                $updateData['catatan'] = $request->catatan;
            }

            if (in_array($newStatus, ['Disetujui', 'Ditolak'])) {
                $updateData['petugas_id'] = Auth::id();
            }

            // 1. JIKA DITOLAK
            // Bebaskan kembali unit fisik yang tadi dikunci saat awal checkout
            if ($newStatus == 'Ditolak' && in_array($peminjaman->status, ['Menunggu Petugas', 'Menunggu Admin', 'Disetujui'])) {
                 foreach ($peminjaman->detail_peminjaman as $detail) {
                     if($detail->unit) {
                         $detail->unit->update(['status' => AlatUnit::STATUS_TERSEDIA]);
                     }
                 }
            } 
            // 2. JIKA DIKEMBALIKAN (AMAN)
            elseif ($newStatus == 'Dikembalikan' && $peminjaman->status != 'Dikembalikan') {
                $pengembalian = Pengembalian::where('peminjaman_id', $peminjaman->id)->latest()->first();
                if ($pengembalian) {
                    $pengembalian->update([
                        'kondisi_kembali' => Pengembalian::KONDISI_BAIK,
                        'catatan' => 'Pengecekan selesai, aman.'
                    ]);
                }
                // Bebaskan kembali unit fisik agar bisa dipinjam siswa lain
                foreach ($peminjaman->detail_peminjaman as $detail) {
                    if($detail->unit) {
                        $detail->unit->update(['status' => AlatUnit::STATUS_TERSEDIA]);
                    }
                }
            } 
            // 3. JIKA MENUNGGU PENGECEKAN (CEK TELAT/DENDA TELAT)
            elseif ($newStatus == 'Menunggu Pengecekan') {
                $tgl_sekarang = Carbon::now();
                $pengembalian = Pengembalian::updateOrCreate(
                    ['peminjaman_id' => $peminjaman->id],
                    [
                        'tanggal_kembali' => $tgl_sekarang->toDateString(),
                        'kondisi_kembali' => Pengembalian::KONDISI_BELUM_DICEK,
                        'catatan' => 'Barang diterima, menunggu pengecekan',
                    ]
                );

                $tgl_kembali_input = Carbon::parse($pengembalian->tanggal_kembali)->startOfDay();
                $tgl_rencana_murni = Carbon::parse($peminjaman->tanggal_rencana_kembali)->startOfDay();
                
                if ($tgl_kembali_input->greaterThan($tgl_rencana_murni)) {
                    $hari_telat = $tgl_rencana_murni->diffInDays($tgl_kembali_input); 
                    $kategoriTelat = KategoriDenda::where('nama_kategori', 'TELAT')->first();
                    
                    if ($kategoriTelat && $hari_telat > 0) {
                        $total_denda_telat = $kategoriTelat->nilai_denda * $hari_telat;
                        $jumlah_denda_final = min($total_denda_telat, 30000); 

                        $idAlatPertama = $peminjaman->detail_peminjaman->first()->alat_id;

                        Denda::updateOrCreate(
                            [
                                'pengembalian_id' => $pengembalian->id,
                                'kategori_denda_id' => $kategoriTelat->id
                            ],
                            [
                                'alat_id' => $idAlatPertama, 
                                'jumlah_denda' => $jumlah_denda_final,
                                'status' => 'belum_bayar',
                                'keterangan' => "Terlambat {$hari_telat} hari (Rp " . number_format($kategoriTelat->nilai_denda) . "/hari)",
                            ]
                        );
                    }
                }
            } 
            // 4. JIKA BERMASALAH (DENDA RUSAK/HILANG)
            elseif ($newStatus == 'Bermasalah') {
                $pengembalian = Pengembalian::where('peminjaman_id', $peminjaman->id)->latest()->first();
                if (!$pengembalian) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Data pengembalian tidak ditemukan.'], 422);
                }

                $pengembalian->update([
                    'kondisi_kembali' => Pengembalian::KONDISI_BERMASALAH,
                    'catatan' => $request->catatan ?? 'Barang bermasalah'
                ]);

                if ($request->filled('kategori_denda_id')) {
                    $finalAlatId = $request->filled('alat_id') ? $request->alat_id : $peminjaman->detail_peminjaman->first()->alat_id;
                    $kategori = KategoriDenda::find($request->kategori_denda_id);
                    
                    if ($kategori) {
                        $jumlah_denda = $kategori->metode_denda === 'PERSENTASE' 
                            ? (($kategori->nilai_denda / 100) * (Alat::find($finalAlatId)->harga ?? 0))
                            : $kategori->nilai_denda;

                        Denda::updateOrCreate(
                            [
                                'pengembalian_id' => $pengembalian->id,
                                'kategori_denda_id' => $request->kategori_denda_id,
                            ],
                            [
                                'alat_id' => $finalAlatId,
                                'jumlah_denda' => $jumlah_denda,
                                'status' => 'belum_bayar',
                                'keterangan' => $request->catatan ?? 'Penalti: ' . $kategori->nama_kategori,
                            ]
                        );
                    }
                }
            }

            $peminjaman->update($updateData);
            DB::commit();

            return response()->json(['status' => 'success', 'message' => "Status berhasil diperbarui menjadi {$newStatus}"], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function verifyByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_peminjaman' => 'required|string|exists:peminjaman,kode_peminjaman',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Kode Peminjaman tidak valid!'], 422);
        }

        try {
            $peminjaman = Peminjaman::with('detail_peminjaman.unit')->where('kode_peminjaman', $request->kode_peminjaman)->first();

            if ($peminjaman->status !== 'Disetujui') {
                return response()->json([
                    'status' => 'error', 
                    'message' => "Gagal! Status peminjaman ini adalah: {$peminjaman->status}. Hanya status 'Disetujui' yang bisa diverifikasi."
                ], 422);
            }

            // Di sistem baru, stok tidak dikurangi secara manual karena fisik unit
            // sudah terkunci sebagai 'DIPINJAM' sejak awal checkout (store).
            $peminjaman->update([
                'status' => 'Dipinjam',
                'catatan' => $peminjaman->catatan . " | Barang diserahkan oleh petugas pada: " . now()->format('d-m-Y H:i'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Verifikasi Berhasil! Barang resmi dipinjam.',
                'data' => $peminjaman
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function riwayat()
    {
        try {
            $user = Auth::user();
            $data = Peminjaman::with(['peminjam', 'detail_peminjaman.alat.kategori', 'detail_peminjaman.unit']) 
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal memuat riwayat'], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = Peminjaman::with(['peminjam', 'detail_peminjaman.alat.kategori', 'detail_peminjaman.unit', 'pengembalian'])->findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $peminjaman = Peminjaman::findOrFail($id);
            $peminjaman->delete();
            return response()->json(['message' => 'Peminjaman berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}