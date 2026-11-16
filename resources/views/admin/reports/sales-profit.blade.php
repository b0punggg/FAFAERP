@extends('layouts.app')
@section('title', 'Laba penjualan')
@section('content')
@include('components.page-header', ['title' => 'Laba penjualan', 'description' => $useLaba ? 'Laba mengikuti catatan penjualan.' : 'Laba dihitung dari harga beli terakhir.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-date-filter', compact('from','to'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
<p class="report-hint">Daftar ini memuat sampai 500 baris terakhir pada periode yang dipilih.</p>
@endsection
