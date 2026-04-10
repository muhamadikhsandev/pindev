<?php

namespace App\Exports;

use App\Models\Denda;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // Tambahkan ini
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Tambahkan ini

class DendaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    /**
    * Ambil data denda beserta relasinya
    */
    public function collection()
    {
        return Denda::with(['pengembalian.peminjaman.peminjam', 'kategori'])->latest()->get();
    }

    /**
    * Header kolom di Excel
    */
    public function headings(): array
    {
        return [
            'ID',
            'NAMA PEMINJAM',
            'KODE TRANSAKSI',
            'KATEGORI DENDA',
            'JUMLAH DENDA (Rp)',
            'STATUS PEMBAYARAN',
            'KETERANGAN',
            'TANGGAL TERBIT'
        ];
    }

    /**
    * Mapping data
    * FIX: Jangan diformat string di sini agar bisa diformat Currency oleh Excel
    */
    public function map($denda): array
    {
        return [
            $denda->id,
            strtoupper($denda->pengembalian->peminjaman->peminjam->name ?? '-'),
            $denda->pengembalian->peminjaman->kode_peminjaman ?? '-',
            $denda->kategori->nama_kategori ?? '-',
            $denda->jumlah_denda, // Kirim angka mentah saja
            strtoupper(str_replace('_', ' ', $denda->status)),
            $denda->keterangan ?? '-',
            $denda->created_at->format('d/m/Y H:i')
        ];
    }

    /**
    * FIX: Memberi format mata uang (Currency) pada kolom E (Jumlah Denda)
    */
    public function columnFormats(): array
    {
        return [
            'E' => '"Rp "#,##0', // Format Rupiah tanpa desimal
        ];
    }

    /**
    * Styling Header
    */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true, 
                    'color' => ['argb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '10B981'], 
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
    * Manipulasi Sheet (Border, Alignment, Zebra)
    */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $highestColumn = $event->sheet->getDelegate()->getHighestColumn();
                $cellRange = 'A1:' . $highestColumn . $highestRow;

                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(30);

                // Terapkan Border
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                // Alignment
                $event->sheet->getDelegate()->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('C2:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('F2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Kolom E (Jumlah Denda) Rata Kanan
                $event->sheet->getDelegate()->getStyle('E2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Zebra Color
                for ($i = 2; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $event->sheet->getDelegate()->getStyle('A' . $i . ':' . $highestColumn . $i)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('F8FAFC');
                    }
                }
            },
        ];
    }
}