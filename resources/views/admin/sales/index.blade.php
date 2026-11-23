@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')
@include('components.page-header', [
    'title' => 'Daftar Penjualan',
    'description' => 'Riwayat transaksi gerai aktif',
    'actionUrl' => route('sales.create'),
    'actionLabel' => 'Transaksi Baru',
])

<div class="sales-subnav mb-3">
    <a href="{{ route('sales.drafts.index') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Draft pending</a>
    <a href="{{ route('sales.void-requests.index') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Approval void</a>
    <a href="{{ route('sales.deletion-history') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">History hapus</a>
    <a href="{{ route('retail.settlements.index') }}" class="btn-fafa btn-fafa-primary btn-fafa-sm">Tutup toko hari ini</a>
</div>
@if(empty($settlementToday))
<div class="alert alert-warning">Belum ada settlement hari ini. Isi fisik kas di <a href="{{ route('retail.settlements.index') }}">Tutup toko</a> sebelum pulang.</div>
@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="sales-toolbar">
            <div>
                <label for="q">Cari</label>
                <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="No. nota, pelanggan, atau member">
            </div>
            <div>
                <label for="from">Dari</label>
                <input id="from" type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div>
                <label for="to">Sampai</label>
                <input id="to" type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
            <div>
                <label for="bayar">Cara bayar</label>
                <select id="bayar" name="bayar" class="form-control">
                    <option value="">Semua</option>
                    @foreach(['TUNAI' => 'Tunai', 'TEMPO' => 'Tempo', 'COD' => 'COD', 'QRIS' => 'QRIS', 'BNI' => 'BNI', 'PIUTANG' => 'Masih piutang'] as $value => $label)
                        <option value="{{ $value }}" @selected($bayar === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort">Urutkan</label>
                <select id="sort" name="sort" class="form-control">
                    @foreach([
                        'terbaru' => 'Terbaru',
                        'terlama' => 'Terlama',
                        'total_besar' => 'Total terbesar',
                        'total_kecil' => 'Total terkecil',
                        'pelanggan' => 'Pelanggan A–Z',
                        'faktur' => 'No. nota',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="btn-fafa btn-fafa-primary">Tampilkan</button>
                @if($q || $from || $to || $bayar || $sort !== 'terbaru')
                    <a href="{{ route('sales.index') }}" class="btn-fafa btn-fafa-secondary">Reset</a>
                @endif
            </div>
        </form>
        <div class="sales-summary">
            <span><strong>{{ number_format($summary->jumlah ?? 0, 0, ',', '.') }}</strong> nota</span>
            <span>Total <strong>Rp {{ number_format($summary->total ?? 0, 0, ',', '.') }}</strong></span>
            <span>Piutang <strong>Rp {{ number_format($summary->piutang ?? 0, 0, ',', '.') }}</strong></span>
        </div>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <div class="table-responsive">
            <table class="fafa-table">
                <thead>
                    <tr>
                        <th>Nota</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th class="sales-money">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($sales as $sale)
                    @php
                        $cara = $sale->kd_bayar ?: ((float) $sale->byr_hutang > 0 ? 'TEMPO' : 'TUNAI');
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $sale->no_fak }}</strong>
                            <span class="sales-sub">{{ $cara }}</span>
                        </td>
                        <td>{{ date('d/m/Y', strtotime($sale->tgl_jual)) }}</td>
                        <td>
                            {{ $sale->nm_pel ?: 'Umum' }}
                            @if($sale->nm_member)
                                <span class="sales-sub">Member {{ $sale->nm_member }}</span>
                            @endif
                        </td>
                        <td class="sales-money">
                            Rp {{ number_format($sale->tot_bayar, 0, ',', '.') }}
                            @if((float) $sale->byr_hutang > 0)
                                <span class="sales-sub">Piutang Rp {{ number_format($sale->byr_hutang, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="sales-row-actions">
                            <a href="{{ route('sales.show', $sale->no_urut) }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Detail</a>
                            <a href="{{ route('sales.print', $sale->no_urut) }}" target="_blank" class="btn-fafa btn-fafa-primary btn-fafa-sm"><i class="fa fa-print"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada nota pada filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-fafa-pagination :paginator="$sales" />
</div>
@endsection
