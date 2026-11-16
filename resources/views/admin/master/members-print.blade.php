<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Member {{ $storeName }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 16px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 0 0 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">Cetak / PDF</button></p>
    <h1>Daftar Member</h1>
    <p>{{ $storeName }} — {{ now()->format('d-m-Y H:i') }} — {{ $items->count() }} data</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Member</th>
                <th>Nama</th>
                <th>Nama Toko</th>
                <th>Alamat</th>
                <th>Telp</th>
                <th>Tgl Daftar</th>
                <th>Poin</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $i => $m)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $m->kd_member }}</td>
                <td>{{ $m->nm_member }}</td>
                <td>{{ $m->nm_toko }}</td>
                <td>{{ $m->al_member }}</td>
                <td>{{ $m->no_telp }}</td>
                <td>{{ optional($m->tgl_daftar)->format('d-m-Y') }}</td>
                <td>{{ number_format((float) $m->poin, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
