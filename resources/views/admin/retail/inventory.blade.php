@extends('layouts.app')
@section('title', 'Stok Gudang')
@section('content')
<div class="container">
    <h2>Stok per Gudang (ERP)</h2>
    @if(!$warehouseId)<p class="alert alert-warning">Warehouse context belum diset untuk gerai ini.</p>@endif
    <table class="table table-striped"><thead><tr><th>SKU</th><th>Nama</th><th>Qty</th></tr></thead>
    <tbody>@foreach($items as $row)<tr><td>{{ $row->product->sku ?? '-' }}</td><td>{{ $row->product->name ?? '-' }}</td><td>{{ $row->qty_on_hand }}</td></tr>@endforeach</tbody></table>
    <x-fafa-pagination :paginator="$items" />
</div>
@endsection
