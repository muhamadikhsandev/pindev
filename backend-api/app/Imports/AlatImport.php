<?php

namespace App\Imports;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\KategoriAlat;
use App\Models\SatuanAlat;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AlatImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows)
    {
        $kategoriMap = KategoriAlat::pluck('id', 'nama_kategori')->toArray();
        $satuanMap = SatuanAlat::pluck('id', 'nama_satuan')->toArray();

        DB::transaction(function () use ($rows, $kategoriMap, $satuanMap) {
            foreach ($rows as $row) {
                $kategoriName = trim($row['kategori']);
                $satuanName = trim($row['satuan']);

                $kategoriId = $kategoriMap[$kategoriName] ?? null;
                $satuanId = $satuanMap[$satuanName] ?? null;

                if (!$kategoriId || !$satuanId) {
                    continue; 
                }

                $alat = Alat::create([
                    'nama_alat'   => trim($row['nama_alat']),
                    'kategori_id' => $kategoriId,
                    'satuan_id'   => $satuanId,
                    'harga'       => $row['harga'] ?? 0,
                    'foto_path'   => null, 
                ]);

                $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $alat->nama_alat);
                $prefix = strtoupper(Str::limit($cleanName, 4, ''));
                if (strlen($prefix) < 4) {
                    $prefix = str_pad($prefix, 4, 'X'); 
                }

                $stokAwal = (int) $row['stok_awal'];
                $unitsData = [];
                for ($i = 1; $i <= $stokAwal; $i++) {
                    $kodeUnit = $prefix . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                    $uniqueCode = $kodeUnit . '-' . $alat->id; 

                    $unitsData[] = [
                        'alat_id'    => $alat->id,
                        'kode_unit'  => $uniqueCode,
                        'kondisi'    => AlatUnit::KONDISI_BAIK,
                        'status'     => AlatUnit::STATUS_TERSEDIA,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                AlatUnit::insert($unitsData);
            }
        });
    }

    public function rules(): array
    {
        return [
            // 🔥 FIX: Ganti 'alats' menjadi 'alat' agar sesuai database kamu
            'nama_alat' => 'required|string|max:255|unique:alat,nama_alat',
            'kategori'  => 'required|exists:kategori_alat,nama_kategori',
            'satuan'    => 'required|exists:satuan_alat,nama_satuan',
            'stok_awal' => 'required|integer|min:1|max:200',
            'harga'     => 'nullable|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_alat.unique' => 'Gagal! Alat ":input" sudah ada di database. Hapus data lama atau ganti nama jika ini barang berbeda.',
            'nama_alat.required' => 'Nama alat tidak boleh kosong.',
            'kategori.exists'  => 'Nama Kategori ":input" tidak terdaftar di sistem.',
            'satuan.exists'    => 'Nama Satuan ":input" tidak terdaftar di sistem.',
            'stok_awal.max'    => 'Stok awal maksimal 200 unit per item demi performa.',
            'stok_awal.min'    => 'Stok awal minimal 1 unit.',
        ];
    }
}