<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengembalian PINDEV</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 10px;
        }
        .header h2 {
            text-transform: uppercase;
            margin: 0;
            color: #7c3aed;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #64748b;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #7c3aed;
            color: white;
            text-transform: uppercase;
            padding: 10px;
            text-align: left;
            letter-spacing: 0.5px;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier', monospace; }
        
        /* Badge Styles */
        .badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-baik { background-color: #dcfce7; color: #15803d; }
        .badge-rusak { background-color: #fef3c7; color: #92400e; }
        .badge-berat { background-color: #fee2e2; color: #b91c1c; }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: right;
            color: #94a3b8;
            font-size: 9px;
        }
        .meta-info {
            margin-bottom: 15px;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Data Pengembalian Barang</h2>
        <p>Sistem Manajemen Inventaris PINDEV - Laporan Resmi</p>
    </div>

    <div class="meta-info">
        Dicetak oleh: {{ auth()->user()->name ?? 'Admin' }} <br>
        Tanggal Cetak: {{ now()->format('d F Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Kode TRX</th>
                <th width="25%">Nama Peminjam</th>
                <th width="15%">Tgl Kembali</th>
                <th width="15%">Kondisi</th>
                <th width="25%">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-mono font-bold">{{ $item->peminjaman->kode_peminjaman ?? '-' }}</td>
                <td class="font-bold">{{ strtoupper($item->peminjaman->peminjam->name ?? '-') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal_kembali)->format('d/m/Y') }}</td>
                <td>
                    @php
                        $kondisi = $item->kondisi_kembali;
                        $class = 'badge-baik';
                        if(str_contains($kondisi, 'Rusak Ringan')) $class = 'badge-rusak';
                        if(str_contains($kondisi, 'Rusak Berat') || str_contains($kondisi, 'Hilang')) $class = 'badge-berat';
                    @endphp
                    <span class="badge {{ $class }}">{{ $kondisi }}</span>
                </td>
                <td>{{ $item->catatan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Halaman 1 / 1 - Dokumen ini dihasilkan secara otomatis oleh sistem.
    </div>

</body>
</html>