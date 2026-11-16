@extends('layouts.app')
@section('title', 'Mutasi, opname, dan retur')
@section('content')
@include('components.page-header', ['title' => 'Mutasi, opname, dan retur'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-date-filter', compact('from','to'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@endsection
