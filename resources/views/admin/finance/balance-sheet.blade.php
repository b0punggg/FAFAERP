@extends('layouts.app')

@section('title', 'Neraca')

@section('content')
@include('components.page-header', ['title' => 'Neraca', 'description' => 'Saldo jurnal sampai tanggal yang dipilih, per PT'])
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
            <div class="col-md-2 form-group"><label>Per tanggal</label><input type="date" name="as_of" value="{{ $asOf }}" class="form-control"></div>
            <div class="col-md-4">@include('components.report-actions')</div>
        </form>
        @include('components.report-summary')
    </div>
</div>
@if($statement)
<div class="row">
    <div class="col-md-6">
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Aset</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <tbody>
                    @forelse($statement['assets'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada saldo aset.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot><tr><th colspan="2">Jumlah aset</th><th class="text-right">{{ number_format($statement['total_assets'], 0, ',', '.') }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Kewajiban dan ekuitas</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <tbody>
                    @foreach($statement['liabilities'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @foreach($statement['equity'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @if(count($statement['liabilities']) + count($statement['equity']) === 0)
                        <tr><td colspan="3">Belum ada saldo kewajiban atau ekuitas.</td></tr>
                    @endif
                    </tbody>
                    <tfoot><tr><th colspan="2">Jumlah kewajiban + ekuitas</th><th class="text-right">{{ number_format($statement['total_claims'], 0, ',', '.') }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<p>Laba berjalan dimasukkan ke ekuitas supaya neraca menutup dengan laba rugi yang belum ditutup ke akun modal.</p>
@endif
@endsection
