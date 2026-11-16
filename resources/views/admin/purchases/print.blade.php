<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Nota Beli {{ $purchase->no_fak }}</title>
<style>body{font-family:monospace;font-size:12px;max-width:80mm;margin:1rem auto} table{width:100%} @media print{button{display:none}}</style>
</head>
<body>
<div style="text-align:center"><strong>NOTA PEMBELIAN</strong></div>
<p>No: {{ $purchase->no_fak }}<br>Tgl: {{ $purchase->tgl_fak }}<br>Sup: {{ $purchase->nm_sup ?: '-' }}</p>
<table>@foreach($purchase->items as $it)
<tr><td>{{ $it->kd_brg }}</td><td>{{ $it->jml_brg }} x {{ number_format($it->hrg_beli,0,',','.') }}</td></tr>
@endforeach</table>
<p><strong>Total: Rp {{ number_format($purchase->tot_bayar, 0, ',', '.') }}</strong></p>
<button onclick="window.print()">Print</button>
</body></html>
