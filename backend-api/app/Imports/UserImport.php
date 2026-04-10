<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UserImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $role;

    public function __construct($role = 'peminjam')
    {
        $this->role = $role;
    }

    public function model(array $row)
    {
        $nis = $row['nis'] ?? null;
        
        // Email & Password Otomatis
        $email = !empty($row['email']) ? $row['email'] : ($nis . '@peminjam.pindev.com');
        $password = !empty($row['password']) ? $row['password'] : $nis;

        // Cari ID Jurusan berdasarkan KODE (Contoh: PPLG)
        $kodeJurusan = $row['jurusan'] ?? null;
        $jurusan = Jurusan::where('kode_jurusan', $kodeJurusan)->first();

        // Cari ID Kelas berdasarkan NAMA (Contoh: XI PPLG 1)
        $namaKelas = $row['kelas'] ?? null;
        $kelas = Kelas::where('nama_kelas', $namaKelas)->first();

        return new User([
            'name'        => $row['name'] ?? $row['nama'],
            'nis'         => $nis,
            'jurusan_id'  => $jurusan ? $jurusan->id : null,
            'kelas_id'    => $kelas ? $kelas->id : null,
            'role'        => $this->role,
            'email'       => $email, 
            'password'    => Hash::make($password),
            'status'      => 'nonaktif', // <--- SUDAH DIUBAH KE NONAKTIF
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'nis'  => $this->role === 'peminjam' ? 'required|digits:10|unique:users,nis' : 'nullable',
        ];
    }
}