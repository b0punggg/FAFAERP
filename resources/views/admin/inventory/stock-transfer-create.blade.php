@extends('layouts.app')

@section('title', 'Buat STR')

@section('content')
<div class="container">
    <h2>Buat Stock Transfer Request</h2>
    <form method="POST" action="{{ route('inventory.str.store') }}">
        @csrf
        <div class="form-group"><label>Dari gudang</label>
            <select name="from_warehouse_id" class="form-control" required>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>@endforeach</select>
        </div>
        <div class="form-group"><label>Ke gudang</label>
            <select name="to_warehouse_id" class="form-control" required>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>@endforeach</select>
        </div>
        <div class="form-group"><label>Product ID</label><input name="lines[0][product_id]" type="number" class="form-control" required></div>
        <div class="form-group"><label>Qty</label><input name="lines[0][qty]" type="number" step="0.01" class="form-control" required></div>
        <button class="btn btn-primary">Simpan STR</button>
    </form>
</div>
@endsection
