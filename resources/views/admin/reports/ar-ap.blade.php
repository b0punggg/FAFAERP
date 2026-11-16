@extends('layouts.app')
@section('title', 'Hutang dan piutang')
@section('content')
@include('components.page-header', ['title' => 'Hutang dan piutang', 'description' => 'Saldo yang belum lunas pada gerai ini. Daftar menampilkan 100 baris terbaru tiap jenis.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@endsection
