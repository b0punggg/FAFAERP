<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>PO {{ $header->no_po }}</title>
<style>body{font-family:monospace;font-size:12px} table{width:100%} @media print{button{display:none}}</style></head><body>
<h3>Pemesanan {{ $header->no_po }}</h3>
<p>Tanggal: {{ $header->tgl_po }}<br>Supplier: {{ $header->nm_sup ?: $header->kd_sup }}</p>
<table><thead><tr><th>Nama</th><th>Qty</th><th>Satuan</th><th>Harga</th><th>Jumlah</th></tr></thead>
<tbody>@foreach($lines as $l)<tr><td>{{ $l->nm_brg }}</td><td>{{ $l->qty_pesan }}</td><td>{{ $l->nm_sat ?? '' }}</td><td>{{ number_format($l->hrg_beli,0,',','.') }}</td><td>{{ number_format($l->jumlah,0,',','.') }}</td></tr>@endforeach</tbody></table>
<p><strong>Total: {{ number_format($header->tot_pesan,0,',','.') }}</strong></p>
<button onclick="window.print()">Print</button>
</body></html>
