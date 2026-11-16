<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Dead stock {{ $month }}/{{ $year }}</title>
<style>body{font-family:monospace;font-size:11px} table{width:100%} @media print{button{display:none}}</style></head><body>
<h3>Dead stock — tidak terjual {{ $month }}/{{ $year }}</h3>
<table><thead><tr><th>SKU</th><th>Nama</th><th>Stok</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ $r->kd_brg }}</td><td>{{ $r->nm_brg }}</td><td>{{ $r->stok }}</td></tr>@endforeach</tbody></table>
<button onclick="window.print()">Print</button></body></html>
