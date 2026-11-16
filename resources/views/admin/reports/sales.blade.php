@extends('layouts.app')
@section('title', 'Penjualan')
@section('content')
@include('components.page-header', ['title' => 'Penjualan', 'description' => 'Faktur pada gerai yang sedang dibuka.'])
<div class="report-links">
    <a href="{{ route('reports.sales-by-product', request()->only('from','to')) }}">Per barang</a>
    <a href="{{ route('reports.sales-by-customer', request()->only('from','to')) }}">Per pelanggan</a>
    <a href="{{ route('reports.sales-profit', request()->only('from','to')) }}">Laba</a>
</div>
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-date-filter', compact('from','to'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@if($rows->lastPage() > 1)<p class="report-hint">Layar menampilkan satu halaman. Cetak dan Excel memuat semua faktur pada periode ini.</p>@endif
<x-fafa-pagination :paginator="$rows" />
@endsection
