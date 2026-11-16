@extends('layouts.app')
@section('title', 'Dashboard holding')
@section('content')
@include('components.page-header', ['title' => 'Dashboard holding', 'description' => 'Ringkasan penjualan, piutang, iklan, dan stok yang sudah di bawah batas.'])
<div class="fafa-card mb-3"><div class="fafa-card-body">
    <form method="GET" class="report-bar">
        @include('components.report-actions')
    </form>
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
                            <td>{{ $sale->branch_name ?: $sale->company_code ?: '—' }}</td>
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
        @if($ar)
        <div class="fafa-card mb-3">
            <div class="fafa-card-header">Umur piutang {{ $ar->as_of_date ? date('d/m/Y', strtotime($ar->as_of_date)) : '' }}</div>
            <div class="fafa-card-body">
                <div class="report-summary" style="margin-top:0">
                    <div><span>0–30 hari</span><strong>Rp {{ number_format($ar->bucket_0_30, 0, ',', '.') }}</strong></div>
                    <div><span>31–60 hari</span><strong>Rp {{ number_format($ar->bucket_31_60, 0, ',', '.') }}</strong></div>
                    <div><span>61 hari atau lebih</span><strong>Rp {{ number_format($ar->bucket_61_plus, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
