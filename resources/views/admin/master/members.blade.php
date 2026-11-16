@extends('layouts.app')
@section('title', 'Master Member')
@section('content')
@include('components.page-header', ['title' => 'Master Member', 'description' => 'Kode IDMEM- urut per gerai: member baru meneruskan nomor terakhir gerai yang sedang aktif.'])

@php
    $editing = (bool) ($editItem ?? null);
    $formAction = $editing ? route('master.members.update', $editItem) : route('master.members.store');
    $kdValue = old('kd_member', $editing ? $editItem->kd_member : $nextKd);
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $editing ? 'Ubah '.$editItem->kd_member : 'Data member' }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ $formAction }}" class="form-fafa" id="form-member">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="kd_member">Kode Member</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="kd_member" name="kd_member" value="{{ $kdValue }}" required maxlength="50" {{ $editing ? 'readonly' : '' }}>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_member">Nama Member</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_member" name="nm_member" value="{{ old('nm_member', $editItem->nm_member ?? '') }}" required maxlength="255">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="nm_toko">Nama Toko</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="nm_toko" name="nm_toko" value="{{ old('nm_toko', $editItem->nm_toko ?? '') }}" maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="al_member">Alamat Member</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="al_member" name="al_member" value="{{ old('al_member', $editItem->al_member ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="no_telp">No. Telp / HP</label>
                        <div class="col-sm-8">
                            <input class="form-control" id="no_telp" name="no_telp" value="{{ old('no_telp', $editItem->no_telp ?? '') }}" maxlength="50">
                        </div>
                    </div>
                    @if($editing)
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label">Poin</label>
                        <div class="col-sm-8">
                            <input class="form-control" value="{{ number_format((float) $editItem->poin, 0, ',', '.') }}" disabled>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <button type="submit" class="btn-fafa btn-fafa-primary w-100">Simpan</button>
                </div>
                <div class="col-sm-6">
                    <a href="{{ route('master.members.index', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary w-100" id="btn-reset-member">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline flex-wrap" action="{{ route('master.members.index') }}">
            <select name="sort" class="form-control mr-2 mb-2" onchange="this.form.submit()">
                <option value="abjad" @selected($sort === 'abjad')>Abjad A–Z</option>
                <option value="id_asc" @selected($sort === 'id_asc')>ID Member</option>
                <option value="poin_desc" @selected($sort === 'poin_desc')>Poin tertinggi</option>
                <option value="poin_asc" @selected($sort === 'poin_asc')>Poin terendah</option>
            </select>
            <input name="q" class="form-control mr-2 mb-2" value="{{ request('q') }}" placeholder="Cari nama / kode / telp">
            <button class="btn-fafa btn-fafa-secondary mb-2">Cari</button>
            <a href="{{ route('master.members.index') }}" class="btn-fafa btn-fafa-link mb-2">Reset cari</a>
            <a href="{{ route('master.members.export', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary mb-2">Excel</a>
            <a href="{{ route('master.members.print', request()->only('q', 'sort')) }}" class="btn-fafa btn-fafa-secondary mb-2" target="_blank">PDF / Cetak</a>
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
                    <th>ID Member</th>
                    <th>Nama Member</th>
                    <th>Nama Toko</th>
                    <th>Alamat</th>
                    <th>No. Telp/HP</th>
                    <th>Tgl Daftar</th>
                    <th>Poin</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $m)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}.</td>
                    <td>{{ $m->kd_member }}</td>
                    <td>{{ $m->nm_member }}</td>
                    <td>{{ $m->nm_toko ?: '—' }}</td>
                    <td>{{ $m->al_member }}</td>
                    <td>{{ $m->no_telp }}</td>
                    <td>{{ optional($m->tgl_daftar)->format('d-m-Y') ?: '—' }}</td>
                    <td>{{ number_format((float) $m->poin, 0, ',', '.') }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('retail.points.history', ['kd_member' => $m->kd_member]) }}" class="btn-fafa btn-fafa-sm btn-fafa-link">Poin</a>
                        <a href="{{ route('master.members.index', ['edit' => $m->no_urut, 'q' => request('q'), 'sort' => $sort]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.members.destroy', $m) }}" class="d-inline" onsubmit="return confirm('Hapus member ini?')">
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
<div class="mt-2"><a href="{{ route('retail.points.index') }}">Tukar poin manual →</a></div>
@endsection
