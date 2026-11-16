@extends('layouts.app')
@section('title', 'Master Supplier')
@section('content')
@include('components.page-header', ['title' => 'Master Supplier', 'description' => 'Kode IDPEM- urut otomatis seperti sistem lama. Dipakai pembelian dan retur beli gerai.'])

@php
    $editing = (bool) ($editItem ?? null);
    $formAction = $editing ? route('master.suppliers.update', $editItem) : route('master.suppliers.store');
    $kdValue = old('kd_sup', $editing ? $editItem->kd_sup : $nextKd);
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $editing ? 'Ubah '.$editItem->kd_sup : 'Data supplier' }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ $formAction }}" class="form-fafa" id="form-supplier">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="kd_sup">Kode Supplier</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="kd_sup" name="kd_sup" value="{{ $kdValue }}" required maxlength="50" {{ $editing ? 'readonly' : '' }}>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_sup">Nama Supplier</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_sup" name="nm_sup" value="{{ old('nm_sup', $editItem->nm_sup ?? '') }}" required maxlength="255">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="al_sup">Alamat</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="al_sup" name="al_sup" value="{{ old('al_sup', $editItem->al_sup ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="no_telp">No. Telp</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="no_telp" name="no_telp" value="{{ old('no_telp', $editItem->no_telp ?? $editItem->telp_sup ?? '') }}" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_sales">Contact Person</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_sales" name="nm_sales" value="{{ old('nm_sales', $editItem->nm_sales ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <button type="submit" class="btn-fafa btn-fafa-primary w-100">Simpan</button>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('master.suppliers.index', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline flex-wrap" action="{{ route('master.suppliers.index') }}">
            <select name="sort" class="form-control mr-2 mb-2" onchange="this.form.submit()">
                <option value="abjad" @selected($sort === 'abjad')>Abjad A–Z</option>
                <option value="id_asc" @selected($sort === 'id_asc')>ID Supplier</option>
            </select>
            <input name="q" class="form-control mr-2 mb-2" value="{{ request('q') }}" placeholder="Cari nama / kode / alamat">
            <button class="btn-fafa btn-fafa-secondary mb-2">Cari</button>
            <a href="{{ route('master.suppliers.index') }}" class="btn-fafa btn-fafa-link mb-2">Reset cari</a>
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
                    <th>ID Supplier</th>
                    <th>Nama Supplier</th>
                    <th>Alamat</th>
                    <th>No. Telp</th>
                    <th>Contact Person</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $s)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}.</td>
                    <td>{{ $s->kd_sup }}</td>
                    <td>{{ $s->nm_sup }}</td>
                    <td>{{ $s->al_sup }}</td>
                    <td>{{ $s->no_telp ?? $s->telp_sup }}</td>
                    <td>{{ $s->nm_sales }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.suppliers.index', ['edit' => $s->no_urut ?? $s->kd_sup, 'q' => request('q'), 'sort' => $sort]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.suppliers.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">
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
