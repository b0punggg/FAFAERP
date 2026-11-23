@extends('layouts.app')
@section('title', 'Jurnal antar-PT')
@section('content')
@include('components.page-header', ['title' => 'Jurnal antar-PT', 'description' => 'Draf harus diposting agar jurnal penjual (piutang) dan pembeli (hutang) tertulis. Kirim order digital/TO juga membuat invoice posted.'])
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
<div class="fafa-card mt-3">
    <div class="fafa-card-header">Posting draf</div>
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>Nomor</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_no }}</td>
                    <td>{{ $invoice->status === 'posted' ? 'Diposting' : 'Draf' }}</td>
                    <td>
                        @if($invoice->status !== 'posted')
                            <form method="POST" action="{{ route('finance.inter-company.post', $invoice) }}">@csrf<button class="btn-fafa btn-fafa-primary btn-fafa-sm">Posting jurnal</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<x-fafa-pagination :paginator="$invoices" />
@endsection
