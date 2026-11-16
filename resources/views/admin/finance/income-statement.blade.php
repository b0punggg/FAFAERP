@extends('layouts.app')

@section('title', 'Laba Rugi')

@section('content')
@include('components.page-header', ['title' => 'Laba Rugi', 'description' => 'Dari jurnal yang sudah diposting, per PT'])
<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-fafa row align-items-end">
            <div class="col-md-4 form-group">
                <label>Perusahaan</label>
                <select name="company_id" class="form-control">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected($companyId == $company->id)>{{ $company->code }} — {{ $company->legal_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group"><label>Dari</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
            <div class="col-md-2 form-group"><label>Sampai</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
            <div class="col-md-4">@include('components.report-actions')</div>
        </form>
        @include('components.report-summary')
    </div>
</div>
@if($statement)
<div class="row">
    <div class="col-md-6">
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Pendapatan</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <tbody>
                    @forelse($statement['revenue'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada jurnal pendapatan pada periode ini.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot><tr><th colspan="2">Jumlah pendapatan</th><th class="text-right">{{ number_format($statement['total_revenue'], 0, ',', '.') }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Beban</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <tbody>
                    @forelse($statement['expense'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada jurnal beban pada periode ini.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot><tr><th colspan="2">Jumlah beban</th><th class="text-right">{{ number_format($statement['total_expense'], 0, ',', '.') }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="fafa-card">
    <div class="fafa-card-body">
        <strong>Laba (rugi) bersih: Rp {{ number_format($statement['net'], 0, ',', '.') }}</strong>
        <p class="mb-0 mt-2">Angka ini mengikuti jurnal PT. Laba penjualan gerai di menu laporan tetap estimasi operasional.</p>
    </div>
</div>
@else
<p>Pilih perusahaan yang punya jurnal.</p>
@endif
@endsection
