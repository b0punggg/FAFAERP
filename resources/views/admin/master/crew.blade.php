@extends('layouts.app')
@section('title', 'Master Crew')
@section('content')
@include('components.page-header', ['title' => 'Master Crew', 'description' => 'Kode IDCREW- urut per gerai, seperti sistem lama. Crew aktif tampil di penjualan.'])

@php
    $editing = (bool) ($editItem ?? null);
    $formAction = $editing ? route('master.crew.update', $editItem) : route('master.crew.store');
    $kdValue = old('kd_crew', $editing ? $editItem->kd_crew : $nextKd);
    $aktifChecked = $editing
        ? (bool) old('aktif', $editItem->aktif)
        : old('aktif', '1') !== null && old('aktif', '1') !== '0';
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $editing ? 'Ubah '.$editItem->kd_crew : 'Data crew' }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ $formAction }}" class="form-fafa" id="form-crew">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="kd_crew">Kode Crew</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="kd_crew" name="kd_crew" value="{{ $kdValue }}" required maxlength="50" {{ $editing ? 'readonly' : '' }}>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_crew">Nama Crew</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_crew" name="nm_crew" value="{{ old('nm_crew', $editItem->nm_crew ?? '') }}" required maxlength="255">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_toko">Nama Toko</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_toko" name="nm_toko" value="{{ old('nm_toko', $editItem->nm_toko ?? $storeName) }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="al_crew">Alamat</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="al_crew" name="al_crew" value="{{ old('al_crew', $editItem->al_crew ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="no_telp">No. Telp / HP</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="no_telp" name="no_telp" value="{{ old('no_telp', $editItem->no_telp ?? '') }}" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label">Status</label>
                        <div class="col-sm-8" style="padding-top:0.45rem">
                            <label class="mb-0">
                                <input type="checkbox" name="aktif" value="1" @checked($aktifChecked)>
                                Aktif (tampil di penjualan)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <button type="submit" class="btn-fafa btn-fafa-primary w-100">Simpan</button>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('master.crew.index', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline flex-wrap" action="{{ route('master.crew.index') }}">
            <select name="sort" class="form-control mr-2 mb-2" onchange="this.form.submit()">
                <option value="abjad" @selected($sort === 'abjad')>Abjad A–Z</option>
                <option value="id_asc" @selected($sort === 'id_asc')>ID Crew</option>
            </select>
            <input name="q" class="form-control mr-2 mb-2" value="{{ request('q') }}" placeholder="Cari nama / kode crew">
            <button class="btn-fafa btn-fafa-secondary mb-2">Cari</button>
            <a href="{{ route('master.crew.index') }}" class="btn-fafa btn-fafa-link mb-2">Reset cari</a>
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
                    <th>ID Crew</th>
                    <th>Nama Crew</th>
                    <th>Nama Toko</th>
                    <th>Alamat</th>
                    <th>No. Telp/HP</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $c)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}.</td>
                    <td>{{ $c->kd_crew }}</td>
                    <td>{{ $c->nm_crew }}</td>
                    <td>{{ $c->nm_toko ?: '—' }}</td>
                    <td>{{ $c->al_crew }}</td>
                    <td>{{ $c->no_telp }}</td>
                    <td>{{ $c->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.crew.index', ['edit' => $c->no_urut, 'q' => request('q'), 'sort' => $sort]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.crew.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Hapus crew ini?')">
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
