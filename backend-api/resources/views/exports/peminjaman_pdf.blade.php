<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Peminjaman PINDEV</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #334155; }
        .header { text-align: center; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #0ea5e9; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #0ea5e9; color: white; padding: 8px; text-transform: uppercase; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .status-badge { 
            padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 8px;
            background: #f1f5f9; color: #475569;
        }
        .status-disetujui { background: #e0f2fe; color: #0369a1; }
        .status-menunggu { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Peminjaman Alat</h2>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode</th>
                <th width="20%">Peminjam</th>
                <th width="15%">Tgl Pinjam</th>
                <th width="15%">Status</th>
                <th width="30%">Alat & Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td align="center">{{ $index + 1 }}</td>
                <td style="font-family: monospace;"><b>{{ $item->kode_peminjaman }}</b></td>
                <td>{{ strtoupper($item->peminjam->name ?? '-') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d/m/Y') }}</td>
                <td>
                    <span class="status-badge {{ $item->status == 'Disetujui' ? 'status-disetujui' : ($item->status == 'Menunggu' ? 'status-menunggu' : '') }}">
                        {{ strtoupper($item->status) }}
                    </span>
                </td>
                <td>
                    @php
                        // FIX: Deteksi nama array/relasi secara otomatis
                        $detailList = $item->detail_peminjaman ?? $item->details ?? [];
                    @endphp
                    @foreach($detailList as $d)
                        @php
                            $namaAlat = $d->alat->nama_alat ?? $d->unit->alat->nama_alat ?? 'Alat';
                            $jumlah = $d->jumlah ?? 1;
                        @endphp
                        - {{ $namaAlat }} ({{ $jumlah }})<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>