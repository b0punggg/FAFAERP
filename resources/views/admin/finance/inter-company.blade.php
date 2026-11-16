@extends('layouts.app')
@section('title', 'Jurnal antar-PT')
@section('content')
@include('components.page-header', ['title' => 'Jurnal antar-PT', 'description' => 'Catat tagihan dari satu PT ke PT lain. Daftar di bawah bisa dicetak atau diunduh.'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Buat draf</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('finance.inter-company.store') }}" class="report-bar">
            @csrf
            <label class="report-field">PT penjual
                <select name="seller_company_id" class="form-control" required>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->code }} — {{ $company->legal_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="report-field">PT pembeli
                <select name="buyer_company_id" class="form-control" required>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->code }} — {{ $company->legal_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="report-field">Nilai
                <input name="amount" type="number" step="0.01" min="0.01" class="form-control" required>
            </label>
            <label class="report-field">Tanggal
                <input name="invoice_date" type="date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </label>
            <button class="btn-fafa btn-fafa-primary" type="submit">Simpan draf</button>
        </form>
    </div>
</div>

<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-actions')
    </form>
</div></div>
@include('components.report-sheet')
@if($invoices->lastPage() > 1)<p class="report-hint">Layar menampilkan satu halaman. Cetak dan Excel memuat seluruh daftar.</p>@endif
<x-fafa-pagination :paginator="$invoices" />
@endsection
