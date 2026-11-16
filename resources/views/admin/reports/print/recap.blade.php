<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Rekap {{ $month }}/{{ $year }}</title>
<style>body{font-family:monospace;font-size:12px} @media print{button{display:none}}</style></head><body>
<h3>Rekapitulasi {{ $month }}/{{ $year }}</h3>
<p>Omset: {{ number_format($omset,0,',','.') }}<br>Pembelian: {{ number_format($beli,0,',','.') }}<br>Biaya: {{ number_format($biaya,0,',','.') }}<br>Laba est: {{ number_format($laba_kotor_est,0,',','.') }}</p>
<button onclick="window.print()">Print</button></body></html>
