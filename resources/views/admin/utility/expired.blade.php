@extends('layouts.app')
@section('title', 'Expired Date')
@section('content')
@include('components.page-header', ['title' => 'Barang mendekati expired', 'description' => 'Kolom tgl_exp di batch beli (beli_brg)'])
<form method="GET" class="mb-2"><label>Within days</label><input type="number" name="days" value="{{ $days }}" class="form-control form-control-sm d-inline-block w-auto"><button class="btn-fafa btn-fafa-sm btn-fafa-secondary">Filter</button></form>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0"><table class="fafa-table"><thead><tr><th>SKU</th><th>Nama</th><th>Stok</th><th>Expired</th><th>Faktur beli</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->kd_brg }}</td><td>{{ $r->nm_brg }}</td><td>{{ $r->stok_jual }}</td><td>{{ $r->tgl_exp }}</td><td>{{ $r->no_fak }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Tidak ada batch dengan ED dalam {{ $days }} hari.</td></tr>@endforelse</tbody></table></div></div>
@endsection
