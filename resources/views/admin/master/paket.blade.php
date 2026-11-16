@extends('layouts.app')
@section('title', 'Inisial Perpaket')
@section('content')
@include('components.page-header', [
    'title' => 'Inisial Perpaket',
    'description' => 'Bundel barang per gerai. Kasir memilih paket di penjualan untuk mengisi keranjang sekaligus.',
])

@php
    $editing = (bool) ($editItem ?? null);
    $formAction = $editing ? route('master.paket.update', $editItem) : route('master.paket.store');
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $editing ? 'Ubah '.$editItem->nm_paket : 'Nama paket' }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ $formAction }}" class="form-fafa">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_paket">Nama Paket</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_paket" name="nm_paket" value="{{ old('nm_paket', $editItem->nm_paket ?? '') }}" required maxlength="255" placeholder="Contoh: PAKET LEBARAN">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <button type="submit" class="btn-fafa btn-fafa-primary w-100">{{ $editing ? 'Simpan nama' : 'Buat paket' }}</button>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('master.paket.index', request()->only('q')) }}" class="btn-fafa btn-fafa-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline flex-wrap" action="{{ route('master.paket.index') }}">
            <input name="q" class="form-control mr-2 mb-2" value="{{ request('q') }}" placeholder="Cari nama paket">
            <button class="btn-fafa btn-fafa-secondary mb-2">Cari</button>
            <a href="{{ route('master.paket.index') }}" class="btn-fafa btn-fafa-link mb-2">Reset cari</a>
        </form>
        <p class="text-muted mb-0 mt-2">Total {{ $items->total() }} record</p>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0; overflow:auto;">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Paket</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $p)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}.</td>
                    <td>{{ $p->nm_paket }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.paket.show', $p) }}" class="btn-fafa btn-fafa-sm btn-fafa-primary">Isi barang</a>
                        <a href="{{ route('master.paket.index', ['edit' => $p->no_urut, 'q' => request('q')]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit nama</a>
                        <form method="POST" action="{{ route('master.paket.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Hapus paket ini beserta isinya?')">
                            @csrf @method('DELETE')
                            <button class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$items" />
</div>
@endsection
