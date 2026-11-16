@extends('layouts.app')

@section('title', $title)

@section('content')
@include('components.page-header', [
    'title' => $title,
    'description' => $description ?? null,
])

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline mb-0">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Cari…">
            <button type="submit" class="btn-fafa btn-fafa-secondary"><i class="fa fa-search"></i> Cari</button>
            @if(request('q'))
                <a href="{{ route($routePrefix.'.index') }}" class="btn-fafa btn-fafa-link ml-2">Reset</a>
            @endif
        </form>
    </div>
</div>

@if(!empty($editItem) && !empty($resourceParam))
<div class="fafa-card mb-3 border-primary">
    <div class="fafa-card-header">Edit data</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route($routePrefix.'.update', [$resourceParam => $editItem]) }}" class="form-fafa">
            @csrf
            @method('PUT')
            @include('admin.master.partials.fields', ['mode' => 'edit'])
            <button type="submit" class="btn-fafa btn-fafa-primary"><i class="fa fa-save"></i> Simpan perubahan</button>
            <a href="{{ route($routePrefix.'.index', request()->only('q')) }}" class="btn-fafa btn-fafa-link">Batal</a>
        </form>
    </div>
</div>
@endif

@empty($hideCreate)
<div class="fafa-card mb-3">
    <div class="fafa-card-header">Tambah data</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="form-fafa">
            @csrf
            @include('admin.master.partials.fields', ['mode' => 'create'])
            <button type="submit" class="btn-fafa btn-fafa-primary"><i class="fa fa-save"></i> Simpan</button>
        </form>
    </div>
</div>
@endempty

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <div class="table-responsive">
            <table class="fafa-table">
                <thead>
                    <tr>
                        @foreach($columns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        @if(empty($readOnly))
                            <th style="width:140px">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $row)
                    <tr>
                        @foreach(array_keys($columns) as $key)
                            <td>
                                @if($key === 'is_active')
                                    {{ $row->{$key} ? 'Aktif' : 'Nonaktif' }}
                                @else
                                    {{ data_get($row, $key) }}
                                @endif
                            </td>
                        @endforeach
                        @if(empty($readOnly))
                            <td class="text-nowrap">
                                @if(!empty($resourceParam))
                                    <a href="{{ route($routePrefix.'.index', array_merge(request()->only('q'), ['edit' => $row->{$rowKey}])) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                                @endif
                                @if(!empty($detailRoute))
                                    <a href="{{ route($detailRoute, [$resourceParam => $row->{$rowKey}]) }}" class="btn-fafa btn-fafa-sm btn-fafa-primary">Detail</a>
                                @endif
                                @if(!empty($resourceParam) && empty($hideDelete))
                                    <form method="POST" action="{{ route($routePrefix.'.destroy', [$resourceParam => $row->{$rowKey}]) }}" class="d-inline" onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + (empty($readOnly) ? 1 : 0) }}" class="text-center text-muted py-4">Belum ada data.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-fafa-pagination :paginator="$items" />
</div>
@endsection
