@extends('layouts.app')
@section('title', 'Dashboard holding')
@section('content')
@include('components.page-header', ['title' => 'Dashboard holding', 'description' => 'Angka dari fact harian (jual gerai, OMS/omzet MP, TO, stok gudang, AR). Bukan menu kosong.'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-actions')
        <button form="eis-refresh" class="btn-fafa btn-fafa-secondary" type="submit">Hitung ulang fakta</button>
    </form>
    <form id="eis-refresh" method="POST" action="{{ route('executive.dashboard.refresh') }}">@csrf</form>
    <p class="text-muted mt-2">Atau <code>php artisan erp:aggregate-eis-facts --from=... --to=...</code>. Budget 5W2H: menu Program budget.</p>
</div></div>
<div class="row">
    <div class="col-md-6">
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Penjualan harian</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <thead><tr><th>Tanggal</th><th>Gerai</th><th class="text-right">Omset</th></tr></thead>
                    <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>{{ $sale->fact_date ? date('d/m/Y', strtotime($sale->fact_date)) : '—' }}</td>
                            <td>{{ trim(($sale->company_code ? $sale->company_code.' · ' : '').($sale->branch_name ?: 'PT')) }}</td>
                            <td class="text-right">Rp {{ number_format($sale->revenue, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">Belum ada fakta penjualan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Stok di bawah batas</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <thead><tr><th>Gerai</th><th>Barang</th><th class="text-right">Stok</th><th class="text-right">Batas</th></tr></thead>
                    <tbody>
                    @forelse($anomalies as $row)
                        <tr>
                            <td>{{ $row->branch_name ?: '—' }}</td>
                            <td>{{ $row->product_name ?: $row->sku ?: '—' }}</td>
                            <td class="text-right">{{ $row->qty_on_hand }}</td>
                            <td class="text-right">{{ $row->reorder_point }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">Tidak ada barang di bawah batas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Umur piutang</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <thead><tr><th>Tanggal</th><th>PT</th><th>0–30</th><th>31–60</th><th>61+</th></tr></thead>
                    <tbody>
                    @forelse($arRows as $row)
                        <tr>
                            <td>{{ $row->as_of_date ? date('d/m/Y', strtotime($row->as_of_date)) : '—' }}</td>
                            <td>{{ $row->company_code }}</td>
                            <td>Rp {{ number_format($row->bucket_0_30, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($row->bucket_31_60, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($row->bucket_61_plus, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center">Belum ada fakta AR. Hitung ulang di atas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Stok harian (qty on hand)</div>
            <div class="fafa-card-body" style="padding:0">
                <table class="fafa-table">
                    <thead><tr><th>Tanggal</th><th>Gudang</th><th class="text-right">Qty</th></tr></thead>
                    <tbody>
                    @forelse($stock as $row)
                        <tr>
                            <td>{{ $row->fact_date ? date('d/m/Y', strtotime($row->fact_date)) : '—' }}</td>
                            <td>{{ $row->warehouse_code ?: $row->kd_toko }}</td>
                            <td class="text-right">{{ number_format($row->qty_total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">Belum ada fakta stok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
