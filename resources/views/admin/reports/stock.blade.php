@extends('layouts.app')
@section('title', 'Stok')
@section('content')
@include('components.page-header', ['title' => 'Stok barang', 'description' => 'Angka merah habis, angka kuning tersisa 5 atau kurang.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        <label class="report-field">Tampilan
            <select name="filter" class="form-control">
                <option value="all" @selected($filter==='all')>Semua</option>
                <option value="available" @selected($filter==='available')>Ada stok</option>
                <option value="low" @selected($filter==='low')>Menipis, 5 atau kurang</option>
                <option value="empty" @selected($filter==='empty')>Habis</option>
            </select>
        </label>
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@if($rows->lastPage() > 1)<p class="report-hint">Layar menampilkan satu halaman. Cetak dan Excel memuat semua barang pada filter ini.</p>@endif
<x-fafa-pagination :paginator="$rows" />
@endsection
