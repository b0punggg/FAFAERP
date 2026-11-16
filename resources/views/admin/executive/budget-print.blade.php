<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $budget->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; }
        h1 { font-size: 16px; background: #d9ead3; text-align: center; padding: 8px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { background: #d9ead3; }
        .total td { background: #fff2cc; font-weight: bold; }
        .sign { margin-top: 28px; display: flex; justify-content: space-between; }
        .sign div { width: 30%; text-align: center; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
@php
    $plan = $budget->plan_5w2h ?? [];
    $total = $budget->lines->sum('planned_amount');
@endphp
<button onclick="window.print()">Cetak</button>
<h1>INISIATIF PROGRAM</h1>
<table>
    <tr><th style="width:180px">Nama program (What)</th><td>{{ $budget->title }}</td></tr>
    <tr><th>Dasar program (Why)</th><td><ol>@foreach($plan['why'] ?? [] as $why)<li>{{ $why }}</li>@endforeach</ol></td></tr>
    <tr><th>Sasaran (Who)</th><td>{{ $plan['sasaran'] ?? '' }} @if(!empty($plan['sasaran_jumlah'])) — {{ $plan['sasaran_jumlah'] }} pelanggan @endif</td></tr>
    <tr><th>Pelaksana (Who)</th><td>@foreach($plan['pelaksana'] ?? [] as $person)<div>{{ $person['peran'] ?? '' }} &gt; {{ $person['nama'] ?? '' }}</div>@endforeach</td></tr>
    <tr><th>Waktu (When)</th><td>{{ optional($budget->period_start)->format('d/m/Y') }} – {{ optional($budget->period_end)->format('d/m/Y') }}</td></tr>
    <tr><th>Tempat (Where)</th><td>{{ $budget->tempat }}</td></tr>
    <tr><th>Tata cara (How)</th><td>
        @foreach($plan['how'] ?? [] as $block)
            <strong>{{ $block['nama'] }}</strong>
            <ol>@foreach($block['langkah'] ?? [] as $step)<li>{{ $step }}</li>@endforeach</ol>
        @endforeach
    </td></tr>
</table>
<table>
    <thead><tr><th>No</th><th>Pengeluaran</th><th>Qty</th><th>Harga</th><th>Jumlah</th></tr></thead>
    <tbody>
        @foreach($budget->lines as $line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $line->kegiatan ? $line->kegiatan.' — ' : '' }}{{ $line->uraian ?: $line->cost_center }}</td>
                <td>{{ number_format($line->qty, 0, ',', '.') }}</td>
                <td>{{ number_format($line->unit_price, 0, ',', '.') }}</td>
                <td>{{ number_format($line->planned_amount, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total"><td colspan="4">Jumlah</td><td>{{ number_format($total, 0, ',', '.') }}</td></tr>
    </tbody>
</table>
<div class="sign">
    <div>Mengetahui<br><br><br>{{ $plan['mengetahui'] ?? '................' }}</div>
    <div>Menyetujui<br><br><br>{{ $plan['menyetujui'] ?? '................' }}</div>
    <div>Yang membuat<br><br><br>{{ $plan['dibuat'] ?? '................' }}</div>
</div>
</body>
</html>
