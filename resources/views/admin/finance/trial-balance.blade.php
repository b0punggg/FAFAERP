@extends('layouts.app')
@section('title', 'Trial balance')
@section('content')
@include('components.page-header', ['title' => 'Trial balance', 'description' => 'Saldo debit dan kredit dari jurnal yang sudah diposting. Klik kode akun untuk melihat nota.'])
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
<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama akun</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <a href="{{ route('finance.trial-balance.ledger', ['company_id' => $companyId, 'from' => $from, 'to' => $to, 'account' => $row['code']]) }}">{{ $row['code'] }}</a>
                    </td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-right">Rp {{ number_format((float) $row['total_debit'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((float) $row['total_credit'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ $sheet->empty }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
