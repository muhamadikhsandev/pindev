<?php

namespace Database\Seeders;

use App\Models\SatuanAlat;
use Illuminate\Database\Seeder;

class SatuanAlatSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_satuan' => 'Unit'],
            ['nama_satuan' => 'Pcs'],
            ['nama_satuan' => 'Meter'], // Biasanya buat kabel LAN/Fiber
            ['nama_satuan' => 'Roll'],
            ['nama_satuan' => 'Set'],
            ['nama_satuan' => 'Box'],
        ];

        foreach ($data as $item) {
            SatuanAlat::create($item);
        }
    }
}