@extends('layouts.app')

@section('title', 'Settlement Harian')

@section('content')
<div class="container">
    <h2>Settlement Kasir Harian</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('retail.settlements.store') }}" class="card card-body mb-3">
        @csrf
        <div class="row">
            <div class="col-md-2"><input type="date" name="settlement_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
            <div class="col-md-2"><input name="pos_cash_total" type="number" step="0.01" class="form-control" placeholder="POS Cash" required></div>
            <div class="col-md-2"><input name="physical_cash" type="number" step="0.01" class="form-control" placeholder="Fisik" required></div>
            <div class="col-md-2"><input name="qris_total" type="number" step="0.01" class="form-control" placeholder="QRIS"></div>
            <div class="col-md-2"><input name="edc_total" type="number" step="0.01" class="form-control" placeholder="EDC"></div>
            <div class="col-md-2"><button class="btn btn-primary">Simpan</button></div>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>Tanggal</th><th>POS</th><th>Fisik</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($items as $row)
            <tr>
                <td>{{ $row->settlement_date }}</td>
                <td>{{ $row->pos_cash_total }}</td>
                <td>{{ $row->physical_cash }}</td>
                <td>{{ $row->status }}</td>
                <td>@if($row->status !== 'approved')<form method="POST" action="{{ route('retail.settlements.approve', $row) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <x-fafa-pagination :paginator="$items" />
</div>
@endsection
