<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Retur {{ $header->no_retur }}</title>
<style>body{font-family:monospace;font-size:12px;max-width:80mm;margin:1rem auto} table{width:100%} @media print{button{display:none}}</style>
</head><body>
<div style="text-align:center"><strong>RETUR PENJUALAN</strong></div>
<p>{{ $header->no_retur }}<br>Faktur: {{ $header->no_fak }}<br>Tgl: {{ $header->tgl_retur }}</p>
<table>@foreach($lines as $l)<tr><td>{{ $l->kd_brg }}</td><td>{{ $l->qty }} x {{ number_format($l->hrg_jual,0,',','.') }}</td></tr>@endforeach</table>
<p><strong>Total: Rp {{ number_format($header->total, 0, ',', '.') }}</strong></p>
<button onclick="window.print()">Print</button>
</body></html>
