<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Laba {{ $from }} - {{ $to }}</title>
<style>body{font-family:monospace;font-size:11px} table{width:100%;border-collapse:collapse} td,th{border-bottom:1px solid #ccc;padding:3px} @media print{button{display:none}}</style></head><body>
<h3>Laba penjualan {{ $from }} s/d {{ $to }}</h3>
<p>Total laba: {{ number_format($totalProfit,0,',','.') }}</p>
<table><thead><tr><th>Faktur</th><th>Barang</th><th>Laba</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ $r->no_fak }}</td><td>{{ $r->kd_brg }}</td><td align="right">{{ number_format($r->profit,0,',','.') }}</td></tr>@endforeach</tbody></table>
<button onclick="window.print()">Print</button></body></html>
