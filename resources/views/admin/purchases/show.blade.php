@extends('layouts.app')
@section('title', 'Detail Pembelian')
@section('content')
@include('components.page-header', ['title' => 'Faktur '.$purchase->no_fak])
<div class="mb-3">
<a href="{{ route('purchases.print', $purchase->no_urut) }}" target="_blank" class="btn-fafa btn-fafa-primary"><i class="fa fa-print"></i> Cetak</a>
<a href="{{ route('purchases.index') }}" class="btn-fafa btn-fafa-secondary">Kembali</a>
</div>
<p>Supplier: {{ $purchase->nm_sup ?: $purchase->kd_sup }} · Tanggal: {{ $purchase->tgl_fak }}</p>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>SKU</th><th>Qty</th><th>Harga</th></tr></thead>
<tbody>@foreach($purchase->items as $it)<tr><td>{{ $it->kd_brg }}</td><td>{{ $it->jml_brg }}</td><td>{{ number_format($it->hrg_beli, 0, ',', '.') }}</td></tr>@endforeach</tbody>
<tfoot><tr><td colspan="2" class="text-right"><strong>Total bayar</strong></td><td><strong>Rp {{ number_format($purchase->tot_bayar, 0, ',', '.') }}</strong></td></tr></tfoot>
</table></div></div>
@endsection
