@extends('layouts.app')
@section('title', 'Barang masuk')
@section('content')
@include('components.page-header', ['title' => 'Barang masuk', 'description' => 'Qty dan nilai pembelian per barang dalam satu bulan.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-date-filter', compact('month','year'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@endsection
