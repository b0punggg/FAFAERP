@extends('layouts.app')
@section('title', 'Dead Stock')
@section('content')
@include('components.page-header', ['title' => 'Dead Stock'])
<div class="fafa-card mb-3"><div class="fafa-card-body"><form class="form-fafa row align-items-end" method="GET">@include('components.report-date-filter', ['month'=>$month,'year'=>$year'])
<a href="{{ route('utility.dead-stock.print', request()->query()) }}" target="_blank" class="btn-fafa btn-fafa-secondary col-md-2">Cetak</a></form></div></div>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0"><table class="fafa-table"><thead><tr><th>SKU</th><th>Nama</th><th>Stok</th><th>Terakhir jual</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ $r->kd_brg }}</td><td>{{ $r->nm_brg }}</td><td>{{ $r->stok }}</td><td>{{ $r->last_sale ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
