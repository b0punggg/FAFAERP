@extends('layouts.app')
@section('title', 'Trial balance')
@section('content')
@include('components.page-header', ['title' => 'Trial balance', 'description' => 'Saldo debit dan kredit dari jurnal yang sudah diposting.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        <label class="report-field">Perusahaan
            <select name="company_id" class="form-control">
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected($companyId == $company->id)>{{ $company->code }} — {{ $company->legal_name }}</option>
                @endforeach
            </select>
        </label>
        @include('components.report-date-filter', compact('from','to'))
        @include('components.report-actions')
    </form>
    @include('components.report-summary')
</div></div>
@include('components.report-sheet')
@endsection
