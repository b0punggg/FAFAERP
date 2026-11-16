<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Cetak Kas {{ $month }}/{{ $year }}</title>
<style>body{font-family:monospace;font-size:11px;max-width:210mm;margin:1rem auto} table{width:100%;border-collapse:collapse} th,td{border-bottom:1px solid #ccc;padding:4px} @media print{button{display:none}}</style>
</head><body>
<h3 style="text-align:center">Arus Kas — {{ $kdToko }} — {{ $month }}/{{ $year }}</h3>
<table><thead><tr><th>Tgl</th><th>Kas in/out</th><th>Nominal</th><th>Ket</th></tr></thead>
<tbody>@foreach($entries as $e)<tr><td>{{ $e->tgl }}</td><td>{{ $e->direction }}</td><td align="right">{{ number_format($e->amount,0,',','.') }}</td><td>{{ $e->description }}</td></tr>@endforeach</tbody></table>
@if($expenses->isNotEmpty())
<h4>Biaya operasional</h4>
<table><thead><tr><th>Tgl</th><th>Jenis</th><th>Nominal</th><th>Ket</th></tr></thead>
<tbody>@foreach($expenses as $b)<tr><td>{{ $b->tgl_biaya }}</td><td>{{ $b->nm_jenis }}</td><td align="right">{{ number_format($b->nominal,0,',','.') }}</td><td>{{ $b->ket_biaya }}</td></tr>@endforeach</tbody></table>
@endif
<p>Masuk: {{ number_format($totIn,0,',','.') }} | Keluar: {{ number_format($totOut,0,',','.') }} | Biaya: {{ number_format($totBiaya,0,',','.') }}</p>
<button onclick="window.print()">Print</button>
</body></html>
