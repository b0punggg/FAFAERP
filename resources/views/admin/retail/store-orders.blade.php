@extends('layouts.app')
@section('title', 'Pemesanan Gerai')
@section('content')
@include('components.page-header', ['title' => 'Daftar pemesanan', 'actionUrl' => route('retail.orders.create'), 'actionLabel' => 'Pemesanan baru'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>No PO gerai</th><th>Tgl</th><th>Total</th><th>Status</th><th></th></tr></thead>
<tbody>@foreach($orders as $o)<tr>
<td>{{ $o->no_po }}</td><td>{{ $o->tgl_po }}</td><td>{{ number_format($o->tot_pesan,0,',','.') }}</td><td>{{ $o->status }}</td>
<td>
<a href="{{ route('retail.orders.print', $o->no_po) }}" target="_blank" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Cetak</a>
@if($o->status==='draft')
<form method="POST" action="{{ route('retail.orders.submit', $o->id) }}" class="d-inline">@csrf<button class="btn-fafa btn-fafa-sm btn-fafa-primary">Kirim</button></form>
@endif
</td></tr>@endforeach</tbody></table></div><x-fafa-pagination :paginator="$orders" /></div>
@endsection
