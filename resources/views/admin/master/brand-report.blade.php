@extends('layouts.app')
@section('title', 'Brand Report Penjualan')
@section('content')
@include('components.page-header', [
    'title' => 'Brand Report Penjualan',
    'description' => 'Daftar nama brand untuk laporan penjualan per brand. Nama dicocokkan ke nama barang; yang tidak cocok masuk Lainnya.',
])
@if($errors->any())
    <div class="alert-fafa alert-fafa-error mb-3">{{ $errors->first() }}</div>
@endif

@php
    $editing = (bool) ($editItem ?? null);
    $formAction = $editing ? route('master.brand-report.update', $editItem) : route('master.brand-report.store');
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $editing ? 'Ubah '.$editItem->nm_brand : 'Data brand' }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ $formAction }}" class="form-fafa">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_brand">Nama Brand</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_brand" name="nm_brand" value="{{ old('nm_brand', $editItem->nm_brand ?? '') }}" required maxlength="100" placeholder="Contoh: WARDAH">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <button type="submit" class="btn-fafa btn-fafa-primary w-100">Simpan</button>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('master.brand-report.index', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline flex-wrap" action="{{ route('master.brand-report.index') }}">
            <select name="sort" class="form-control mr-2 mb-2" onchange="this.form.submit()">
                <option value="abjad" @selected($sort === 'abjad')>Abjad A–Z</option>
                <option value="id_asc" @selected($sort === 'id_asc')>Urutan</option>
            </select>
            <input name="q" class="form-control mr-2 mb-2" value="{{ request('q') }}" placeholder="Cari nama brand">
            <button class="btn-fafa btn-fafa-secondary mb-2">Cari</button>
            <a href="{{ route('master.brand-report.index') }}" class="btn-fafa btn-fafa-link mb-2">Reset cari</a>
            <a href="{{ route('reports.sales-by-brand') }}" class="btn-fafa btn-fafa-link mb-2">Lihat laporan →</a>
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
                    <th>Nama Brand</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $b)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}.</td>
                    <td>{{ $b->nm_brand }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.brand-report.index', ['edit' => $b->no_urut, 'q' => request('q'), 'sort' => $sort]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.brand-report.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Hapus brand ini?')">
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
