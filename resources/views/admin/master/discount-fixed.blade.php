@extends('layouts.app')
@section('title', 'Diskon Tetap')
@section('content')
@include('components.page-header', ['title' => 'Diskon Tetap (disctetap)', 'description' => 'Harga & limit qty per SKU/satuan — gerai aktif'])

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline">
            <input name="q" class="form-control mr-2" value="{{ request('q') }}" placeholder="Cari SKU…">
            <button class="btn-fafa btn-fafa-secondary">Cari</button>
        </form>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Tambah / atur diskon</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('master.discount-fixed.store') }}" class="form-fafa row">
            @csrf
            <div class="col-md-2 form-group"><label>SKU</label><input name="kd_brg" class="form-control" required></div>
            <div class="col-md-1 form-group"><label>Sat</label><input type="number" name="kd_sat" class="form-control" value="1" min="1"></div>
            <div class="col-md-2 form-group"><label>Harga jual</label><input type="number" step="0.01" name="hrg_jual" class="form-control" required></div>
            <div class="col-md-2 form-group"><label>Limit qty</label><input type="number" step="0.01" name="lim_jual" class="form-control" value="0"></div>
            <div class="col-md-2 align-self-end"><button class="btn-fafa btn-fafa-primary w-100">Simpan</button></div>
        </form>
    </div>
</div>

@if($edit)
<div class="fafa-card mb-3 border-primary">
    <div class="fafa-card-header">Edit #{{ $edit->no_urut }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('master.discount-fixed.update', $edit) }}" class="form-fafa row">
            @csrf
            @method('PUT')
            <div class="col-md-2 form-group"><label>SKU</label><input name="kd_brg" class="form-control" value="{{ $edit->kd_brg }}" required></div>
            <div class="col-md-1 form-group"><label>Sat</label><input type="number" name="kd_sat" class="form-control" value="{{ $edit->kd_sat }}"></div>
            <div class="col-md-2 form-group"><label>Harga jual</label><input type="number" step="0.01" name="hrg_jual" class="form-control" value="{{ $edit->hrg_jual }}" required></div>
            <div class="col-md-2 form-group"><label>Limit qty</label><input type="number" step="0.01" name="lim_jual" class="form-control" value="{{ $edit->lim_jual }}"></div>
            <div class="col-md-2 align-self-end">
                <button class="btn-fafa btn-fafa-primary">Update</button>
                <a href="{{ route('master.discount-fixed.index') }}" class="btn-fafa btn-fafa-link">Batal</a>
            </div>
        </form>
    </div>
</div>
@endif

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>SKU</th><th>Sat</th><th>Harga</th><th>Limit</th><th>Toko</th><th></th></tr></thead>
            <tbody>
            @foreach($items as $row)
                <tr>
                    <td>{{ $row->kd_brg }}</td>
                    <td>{{ $row->kd_sat }}</td>
                    <td>{{ number_format($row->hrg_jual, 0, ',', '.') }}</td>
                    <td>{{ $row->lim_jual }}</td>
                    <td>{{ $row->kd_toko ?? '—' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.discount-fixed.index', ['edit' => $row->no_urut]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.discount-fixed.destroy', $row) }}" class="d-inline" onsubmit="return confirm('Hapus?')">
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
