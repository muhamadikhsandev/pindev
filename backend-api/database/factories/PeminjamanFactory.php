<?php

namespace Database\Factories;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    protected $model = Peminjaman::class;

  public function definition(): array
{
    return [
        'user_id' => 45, // Langsung patok ke ID 45
        'petugas_id' => 45, // Atau sesuaikan dengan ID petugas yang lo mau
        'jurusan' => $this->faker->randomElement(array_keys(Peminjaman::getJurusanOptions())),
        'tujuan' => $this->faker->sentence(3),
        'tanggal_pinjam' => now(),
        'tanggal_rencana_kembali' => now()->addDays(3),
        'status' => $this->faker->randomElement(Peminjaman::getStatuses()),
        'catatan' => $this->faker->optional()->sentence(),
    ];
}
}