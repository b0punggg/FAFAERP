<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Hutang Piutang</title>
<style>body{font-family:monospace;font-size:11px} @media print{button{display:none}}</style></head><body>
<h3>Saldo Piutang & Hutang</h3>
<p>Piutang: {{ number_format($ar_total,0,',','.') }} ({{ $ar_count }} faktur)<br>Hutang: {{ number_format($ap_total,0,',','.') }}</p>
<button onclick="window.print()">Print</button></body></html>
