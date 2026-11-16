@extends('layouts.app')
@section('title', 'Penyesuaian Stok')
@section('content')
@include('components.page-header', ['title' => 'Mutasi / penyesuaian lokal', 'description' => 'Antar gudang gerai → gunakan STR (/inventory/str).'])

@if($warehouseId)
<div class="fafa-card mb-3"><div class="fafa-card-body form-fafa">
<form method="POST" action="{{ route('retail.stock-adjustments.store') }}">@csrf
<div class="row">
<div class="col-md-3"><label>Tanggal</label><input type="date" name="tgl" value="{{ date('Y-m-d') }}" class="form-control" required></div>
<div class="col-md-3"><label>Produk</label><select name="product_id" class="form-control" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->sku }} — {{ $p->name }}</option>@endforeach</select></div>
<div class="col-md-2"><label>Arah</label><select name="direction" class="form-control"><option value="in">Masuk</option><option value="out">Keluar</option></select></div>
<div class="col-md-2"><label>Qty</label><input type="number" step="0.01" name="qty" class="form-control" required></div>
<div class="col-md-2 align-self-end"><button class="btn-fafa btn-fafa-primary w-100">Catat</button></div>
</div></form></div></div>

<div class="fafa-card mb-3"><div class="fafa-card-body">
<form method="GET" class="form-inline"><select name="filter" class="form-control mr-2" onchange="this.form.submit()">
<option value="all" @selected($filter==='all')>Semua</option><option value="available" @selected($filter==='available')>Ada stok</option>
<option value="low" @selected($filter==='low')>Menipis</option><option value="empty" @selected($filter==='empty')>Habis</option>
</select></form>
<table class="fafa-table mt-2"><thead><tr><th>SKU</th><th>Nama</th><th>Qty</th></tr></thead>
<tbody>@foreach($items as $row)<tr><td>{{ $row->product?->sku }}</td><td>{{ $row->product?->name }}</td><td>{{ $row->qty_on_hand }}</td></tr>@endforeach</tbody></table>
<x-fafa-pagination :paginator="$items" />
</div></div>

<div class="fafa-card"><div class="fafa-card-header">Mutasi lokal terakhir</div><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Tgl</th><th>Arah</th><th>Product</th><th>Qty</th></tr></thead>
<tbody>@foreach($movements as $m)<tr><td>{{ $m->tgl }}</td><td>{{ $m->direction }}</td><td>#{{ $m->product_id }}</td><td>{{ $m->qty }}</td></tr>@endforeach</tbody></table>
</div></div>
@else
<p class="text-muted">Set context gerai/gudang terlebih dahulu.</p>
@endif
@endsection
