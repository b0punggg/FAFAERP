@extends('layouts.app')
@section('title', 'Rekapitulasi')
@section('content')
@include('components.page-header', ['title' => 'Rekapitulasi bulanan', 'description' => 'Ringkasan gerai yang sedang dibuka. Laba masih estimasi operasional.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-date-filter', compact('month','year'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@endsection
