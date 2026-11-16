@extends('layouts.app')
@section('title', 'Transaksi member')
@section('content')
@include('components.page-header', ['title' => 'Transaksi member'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-date-filter', compact('from','to'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@if($rows->lastPage() > 1)<p class="report-hint">Layar menampilkan satu halaman. Cetak dan Excel memuat semua transaksi member pada periode ini.</p>@endif
<x-fafa-pagination :paginator="$rows" />
@endsection
