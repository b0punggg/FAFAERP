@extends('layouts.app')

@section('title', 'Stock Transfer (STR)')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h2>Mutasi Antar Gudang (STR)</h2>
        <a href="{{ route('inventory.str.create') }}" class="btn btn-primary">Buat STR</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <table class="table table-striped">
        <thead><tr><th>No</th><th>STR</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        @foreach($items as $str)
            <tr>
                <td>{{ $str->id }}</td>
                <td>{{ $str->str_no }}</td>
                <td>{{ $str->status }}</td>
                <td>
                    @if(in_array($str->status, ['draft', 'approved']))
                        <form method="POST" action="{{ route('inventory.str.approve', $str) }}" class="d-inline">@csrf<button class="btn btn-sm btn-warning">Kirim</button></form>
                    @endif
                    @if($str->status === 'in_transit')
                        <form method="POST" action="{{ route('inventory.str.receive', $str) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Terima</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <x-fafa-pagination :paginator="$items" />
</div>
@endsection
