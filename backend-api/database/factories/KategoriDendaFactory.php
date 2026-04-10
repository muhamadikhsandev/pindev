<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KategoriDenda>
 */
class KategoriDendaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Mengambil acak dari pilihan ENUM yang kamu buat sebelumnya
            'nama_kategori' => $this->faker->randomElement(['TELAT', 'RUSAK_RINGAN', 'RUSAK_BERAT', 'HILANG']),
            
            // Mengambil acak dari metode denda
            'metode_denda' => $this->faker->randomElement(['PER_HARI', 'SEKALI_BAYAR', 'PERSENTASE']),
            
            // Menghasilkan angka decimal acak (contoh: antara 1000.00 sampai 100000.00)
            'nilai_denda' => $this->faker->randomFloat(2, 1000, 100000),
        ];
    }
}