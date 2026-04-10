<?php

namespace App\Imports;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\SatuanAlat;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AlatImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // 1. Logika 'First or Create' untuk Kategori
        $kategori = KategoriAlat::firstOrCreate(
            ['nama_kategori' => trim($row['kategori'])]
        );

        // 2. Logika 'First or Create' untuk Satuan
        $satuan = SatuanAlat::firstOrCreate(
            ['nama_satuan' => trim($row['satuan'])]
        );

        // --- LOGIKA TRANSFORMASI KONDISI (Flexibility) ---
        // Kita ubah input admin (misal: "rusak ringan") jadi ("RUSAK_RINGAN")
        $kondisiRaw = strtoupper(trim($row['kondisi'] ?? 'BAIK'));
        $kondisiFix = str_replace(' ', '_', $kondisiRaw);

        // Daftar kondisi yang diizinkan (sesuai Model & ENUM)
        $allowedKondisi = [
            Alat::KONDISI_BAIK, 
            Alat::KONDISI_RUSAK_RINGAN, 
            Alat::KONDISI_RUSAK_BERAT, 
            Alat::KONDISI_HILANG, 
            Alat::KONDISI_PERBAIKAN
        ];

        // Cek validitas, kalau admin ngetik aneh-aneh balikin ke default 'BAIK'
        $kondisiFinal = in_array($kondisiFix, $allowedKondisi) ? $kondisiFix : Alat::KONDISI_BAIK;

        // 3. Simpan data Alat
        return new Alat([
            'kategori_id' => $kategori->id,
            'satuan_id'   => $satuan->id,
            'nama_alat'   => $row['nama_alat'],
            'stok'        => $row['stok'] ?? 0,
            'kondisi'     => $kondisiFinal,
            'harga'       => $row['harga'] ?? 0,
            'foto_path'   => null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_alat' => 'required|string|max:255|unique:alat,nama_alat', 
            'kategori'  => 'required|string',
            'satuan'    => 'required|string',
            'stok'      => 'required|numeric',
            'harga'     => 'nullable|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_alat.unique' => '":input"',
        ];
    }
}