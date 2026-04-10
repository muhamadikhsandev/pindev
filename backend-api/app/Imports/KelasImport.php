<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Jurusan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class KelasImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // 1. Logika 'First or Create' untuk Jurusan
        // Kita ngecek dari kolom 'kode_jurusan' di excel (misal: PPLG, TKJ)
        $jurusan = Jurusan::firstOrCreate(
            ['kode_jurusan' => strtoupper(trim($row['kode_jurusan']))],
            ['nama_jurusan' => trim($row['nama_jurusan'] ?? strtoupper(trim($row['kode_jurusan'])))] // Default nama jika kosong
        );

        // 2. Simpan data Kelas
        return new Kelas([
            'jurusan_id' => $jurusan->id,
            'nama_kelas' => strtoupper(trim($row['nama_kelas'])),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_kelas'   => 'required|string|max:255|unique:kelas,nama_kelas', 
            'kode_jurusan' => 'required|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_kelas.unique' => 'Kelas ":input" sudah terdaftar di database.',
        ];
    }
}