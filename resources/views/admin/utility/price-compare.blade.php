@extends('layouts.app')
@section('title', 'Compare Harga')
@section('content')
@include('components.page-header', ['title' => 'Compare harga antar gerai', 'description' => 'branch_product_prices'])
@forelse($rows as $sku => $items)
<div class="fafa-card mb-2"><div class="fafa-card-header">{{ $sku }} — {{ $items->first()->name ?? '' }}</div><div class="fafa-card-body" style="padding:0"><table class="fafa-table"><thead><tr><th>Gerai</th><th>Harga jual</th></tr></thead>
<tbody>@foreach($items as $i)<tr><td>{{ $i->branch_name }}</td><td>{{ number_format($i->sell_price,0,',','.') }}</td></tr>@endforeach</tbody></table></div></div>
@empty<p class="text-muted">Belum ada data branch pricing.</p>@endforelse
@endsection
