<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Aktivitas {{ $from }}-{{ $to }}</title>
<style>body{font-family:monospace;font-size:11px} table{width:100%} @media print{button{display:none}}</style></head><body>
<h3>Mutasi / opname / retur</h3>
<table>@foreach($rows as $r)<tr><td>{{ $r->tgl }}</td><td>{{ $r->type }}</td><td>{{ $r->ref }}</td></tr>@endforeach</table>
<button onclick="window.print()">Print</button></body></html>
