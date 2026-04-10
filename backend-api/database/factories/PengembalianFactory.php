<?php

namespace Database\Factories;

use App\Models\Pengembalian;
use App\Models\Peminjaman;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengembalianFactory extends Factory
{
    protected $model = Pengembalian::class;

    public function definition()
    {
        return [
            // Mengambil ID dari Peminjaman yang sudah ada atau buat baru
            'peminjaman_id' => Peminjaman::factory(), 
            'tanggal_kembali' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'kondisi_kembali' => $this->faker->randomElement([
                Pengembalian::KONDISI_BELUM_DICEK,
                Pengembalian::KONDISI_BAIK,
                Pengembalian::KONDISI_BERMASALAH,
            ]),
            'catatan' => $this->faker->sentence(),
        ];
    }
}