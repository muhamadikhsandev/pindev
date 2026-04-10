<!DOCTYPE html>
<html>
<head>
    <title>Laporan Denda PINDEV</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { bg-color: #f2f2f2; font-weight: bold; text-transform: uppercase; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN TAGIHAN DENDA PINDEV</h2>
        <p>Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Peminjam</th>
                <th>Kode TRX</th>
                <th>Kategori</th>
                <th>Jumlah (Rp)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($denda as $item)
            <tr>
                <td>{{ $item->pengembalian->peminjaman->peminjam->name ?? '-' }}</td>
                <td>{{ $item->pengembalian->peminjaman->kode_peminjaman ?? '-' }}</td>
                <td>{{ $item->kategori->nama_kategori ?? '-' }}</td>
                <td>{{ number_format($item->jumlah_denda, 0, ',', '.') }}</td>
                <td>{{ strtoupper($item->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Sistem Manajemen Inventaris PINDEV</div>
</body>
</html>