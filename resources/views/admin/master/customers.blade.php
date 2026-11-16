@extends('layouts.app')
@section('title', 'Master Pelanggan')
@section('content')
@include('components.page-header', ['title' => 'Master Pelanggan', 'description' => 'Kode IDPEL- urut otomatis seperti sistem lama. Pelanggan tanpa gerai tetap tampil di semua toko.'])

@php
    $editing = (bool) ($editItem ?? null);
    $formAction = $editing ? route('master.customers.update', $editItem) : route('master.customers.store');
    $kdValue = old('kd_pel', $editing ? $editItem->kd_pel : $nextKd);
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $editing ? 'Ubah '.$editItem->kd_pel : 'Data pelanggan' }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ $formAction }}" class="form-fafa" id="form-pelanggan">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="kd_pel">Kode Pelanggan</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="kd_pel" name="kd_pel" value="{{ $kdValue }}" required maxlength="50" {{ $editing ? 'readonly' : '' }}>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_pel">Nama Pelanggan</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_pel" name="nm_pel" value="{{ old('nm_pel', $editItem->nm_pel ?? '') }}" required maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="al_pel">Alamat Pelanggan</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="al_pel" name="al_pel" value="{{ old('al_pel', $editItem->al_pel ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="telp_pel">No. Telp / HP</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="telp_pel" name="telp_pel" value="{{ old('telp_pel', $editItem->telp_pel ?? '') }}" maxlength="50">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <button type="submit" class="btn-fafa btn-fafa-primary w-100">Simpan</button>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('master.customers.index', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline flex-wrap" action="{{ route('master.customers.index') }}">
            <select name="sort" class="form-control mr-2 mb-2" onchange="this.form.submit()">
                <option value="abjad" @selected($sort === 'abjad')>Abjad A–Z</option>
                <option value="id_asc" @selected($sort === 'id_asc')>ID Pelanggan</option>
            </select>
            <input name="q" class="form-control mr-2 mb-2" value="{{ request('q') }}" placeholder="Cari nama / kode / alamat">
            <button class="btn-fafa btn-fafa-secondary mb-2">Cari</button>
            <a href="{{ route('master.customers.index') }}" class="btn-fafa btn-fafa-link mb-2">Reset cari</a>
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
                    <th>ID Pelanggan</th>
                    <th>Nama Pelanggan</th>
                    <th>Alamat</th>
                    <th>No. Telp/HP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $p)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}.</td>
                    <td>{{ $p->kd_pel }}</td>
                    <td>{{ $p->nm_pel }}</td>
                    <td>{{ $p->al_pel }}</td>
                    <td>{{ $p->telp_pel }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.customers.index', ['edit' => $p->no_urut, 'q' => request('q'), 'sort' => $sort]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.customers.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?')">
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
