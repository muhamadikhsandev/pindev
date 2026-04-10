<?php

namespace App\Exports;

use App\Models\Peminjaman;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PeminjamanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    public function collection()
    {
        // FIX: Menggunakan relasi 'detail_peminjaman' (sesuai standar database kamu)
        return Peminjaman::with(['peminjam', 'detail_peminjaman.alat', 'detail_peminjaman.unit.alat'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'KODE TRANSAKSI',
            'NAMA PEMINJAM',
            'TANGGAL PINJAM',
            'STATUS',
            'DAFTAR ALAT (JUMLAH)'
        ];
    }

    public function map($item): array
    {
        // FIX: Antisipasi nama relasi yang bervariasi
        $detailList = $item->detail_peminjaman ?? $item->details ?? collect([]);

        // Menggabungkan daftar alat menjadi satu string
        $daftarAlat = $detailList->map(function($d) {
            // Cek apakah relasi alat langsung ada, atau bersarang di dalam 'unit'
            $namaAlat = $d->alat->nama_alat ?? $d->unit->alat->nama_alat ?? 'Alat Tidak Diketahui';
            $jumlah = $d->jumlah ?? 1;
            
            return "{$namaAlat} ({$jumlah})";
        })->implode(', ');

        return [
            $item->id,
            $item->kode_peminjaman,
            strtoupper($item->peminjam->name ?? '-'),
            \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d/m/Y'),
            strtoupper($item->status),
            $daftarAlat
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '0EA5E9'], // Sky-500
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $highestColumn = $event->sheet->getDelegate()->getHighestColumn();
                $cellRange = 'A1:' . $highestColumn . $highestRow;

                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Zebra Color (Sky-50)
                for ($i = 2; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $event->sheet->getDelegate()->getStyle('A' . $i . ':' . $highestColumn . $i)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('F0F9FF'); 
                    }
                }
            },
        ];
    }
}