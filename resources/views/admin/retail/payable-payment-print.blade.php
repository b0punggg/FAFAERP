<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Bukti Bayar Hutang #{{ $payment->id }}</title>
<style>body{font-family:monospace;font-size:12px;max-width:80mm;margin:1rem auto} @media print{button{display:none}}</style></head><body>
<p style="text-align:center"><strong>BUKTI BAYAR HUTANG</strong></p>
<p>No: {{ $payment->id }}<br>Tgl: {{ $payment->tgl_bayar }}<br>Faktur beli: {{ $purchase?->no_fak }}<br>Nominal: Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
<button onclick="window.print()">Print</button>
</body></html>
