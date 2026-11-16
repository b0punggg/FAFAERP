@extends('layouts.app')
@section('title', 'Cek Data')
@section('content')
@include('components.page-header', ['title' => 'Cek integritas data'])
<div class="fafa-card mb-3"><div class="fafa-card-header">Barcode duplikat</div><div class="fafa-card-body" style="padding:0"><table class="fafa-table"><thead><tr><th>Barcode</th><th>Count</th><th>SKU</th></tr></thead>
<tbody>@forelse($dupBarcodes as $r)<tr><td>{{ $r->barcode }}</td><td>{{ $r->cnt }}</td><td>{{ $r->skus }}</td></tr>@empty<tr><td colspan="3" class="text-muted text-center">OK</td></tr>@endforelse</tbody></table></div></div>
<div class="fafa-card mb-3"><div class="fafa-card-header">Stok negatif</div><div class="fafa-card-body" style="padding:0"><table class="fafa-table"><thead><tr><th>SKU</th><th>Faktur</th><th>Stok</th></tr></thead>
<tbody>@forelse($negativeStock as $r)<tr><td>{{ $r->kd_brg }}</td><td>{{ $r->no_fak }}</td><td>{{ $r->stok_jual }}</td></tr>@empty<tr><td colspan="3" class="text-muted text-center">OK</td></tr>@endforelse</tbody></table></div></div>
<div class="fafa-card"><div class="fafa-card-header">Faktur jual tanpa baris</div><div class="fafa-card-body" style="padding:0"><table class="fafa-table"><thead><tr><th>Faktur</th><th>Tgl</th><th>Total</th></tr></thead>
<tbody>@forelse($emptySales as $r)<tr><td>{{ $r->no_fak }}</td><td>{{ $r->tgl_jual }}</td><td>{{ $r->tot_bayar }}</td></tr>@empty<tr><td colspan="3" class="text-muted text-center">OK</td></tr>@endforelse</tbody></table></div></div>
@endsection
