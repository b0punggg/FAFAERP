@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
@include('components.page-header', [
    'title' => 'Ringkasan Gerai',
    'description' => 'Tanggal kerja: '.($businessDate ?? date('Y-m-d')).' — '.(session('nm_toko') ?? 'Semua gerai'),
    'actionUrl' => \App\Support\StaffAccess::can(auth()->user(), 'sales') ? route('sales.create') : null,
    'actionLabel' => 'Transaksi Baru',
])

<div class="stat-grid">
    <div class="stat-card" style="--stat-accent:#4f46e5">
        <div class="stat-label">Penjualan hari ini</div>
        <div class="stat-value">Rp {{ number_format($penjualanHariIni, 0, ',', '.') }}</div>
        <i class="fa fa-shopping-cart stat-icon"></i>
    </div>
    <div class="stat-card" style="--stat-accent:#06b6d4">
        <div class="stat-label">Omset bulan ini</div>
        <div class="stat-value">Rp {{ number_format($penjualanBulanIni, 0, ',', '.') }}</div>
        <i class="fa fa-line-chart stat-icon"></i>
    </div>
    <div class="stat-card" style="--stat-accent:#f59e0b">
        <div class="stat-label">Jenis barang aktif</div>
        <div class="stat-value">{{ number_format($totalJenisBarang, 0, ',', '.') }}</div>
        <i class="fa fa-tags stat-icon"></i>
    </div>
</div>

<div class="grid-2">
    <div class="fafa-card">
        <div class="fafa-card-header">
            <span><i class="fa fa-star text-warning"></i> Top 10 barang terlaris (bulan ini)</span>
        </div>
        <div class="fafa-card-body" style="padding:0">
            <table class="fafa-table">
                <thead><tr><th>#</th><th>SKU</th><th>Nama</th><th>Qty</th></tr></thead>
                <tbody>
                @forelse($topProducts as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code>{{ $p->kd_brg }}</code></td>
                        <td>{{ $p->nm_brg }}</td>
                        <td><strong>{{ number_format($p->qty_terjual, 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada penjualan bulan ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fafa-card">
        <div class="fafa-card-header">Alert stok &amp; piutang</div>
        <div class="fafa-card-body">
            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                <span><span class="badge-fafa badge-danger">{{ $zeroStock }}</span> Stok habis</span>
                <a href="{{ route('reports.stock', ['filter' => 'empty']) }}" class="small">Lihat semua</a>
            </div>
            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                <span><span class="badge-fafa badge-warning">{{ $lowStock }}</span> Stok menipis (≤5)</span>
                <a href="{{ route('reports.stock', ['filter' => 'low']) }}" class="small">Lihat semua</a>
            </div>
            <div class="d-flex justify-content-between">
                <span>Total piutang</span>
                <strong>Rp {{ number_format($piutangPelanggan, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>

<p class="text-muted small mb-3">Nilai persediaan, ROP, dan dead stock ada di aplikasi gudang: <a href="{{ route('sso.gudang', ['next' => '/pantauan.html']) }}">pantauan gerai</a> (SSO, username sama).</p>

<div class="grid-2">
    <div class="fafa-card">
        <div class="fafa-card-header">Stok habis (sample)</div>
        <div class="fafa-card-body" style="padding:0">
            <table class="fafa-table">
                <thead><tr><th>SKU</th><th>Nama</th></tr></thead>
                <tbody>
                @forelse($zeroStockList as $row)
                    <tr><td>{{ $row->kd_brg }}</td><td>{{ $row->nm_brg }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-muted text-center">Tidak ada</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="fafa-card">
        <div class="fafa-card-header">Stok menipis (sample)</div>
        <div class="fafa-card-body" style="padding:0">
            <table class="fafa-table">
                <thead><tr><th>SKU</th><th>Nama</th><th>Qty</th></tr></thead>
                <tbody>
                @forelse($lowStockList as $row)
                    <tr><td>{{ $row->kd_brg }}</td><td>{{ $row->nm_brg }}</td><td>{{ $row->stok }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-muted text-center">Tidak ada</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
