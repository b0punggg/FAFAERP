@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
@include('components.page-header', [
    'title' => 'Daftar Pembelian',
    'description' => 'Faktur pembelian gerai aktif',
    'actionUrl' => route('purchases.create'),
    'actionLabel' => 'Pembelian Baru',
])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="sales-toolbar">
            <div>
                <label for="q">Cari</label>
                <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="No. faktur atau supplier">
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
                    @foreach(['TUNAI' => 'Tunai', 'TEMPO' => 'Tempo', 'HUTANG' => 'Masih hutang'] as $value => $label)
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
                        'supplier' => 'Supplier A–Z',
                        'faktur' => 'No. faktur',
                        'jatuh_tempo' => 'Jatuh tempo',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="btn-fafa btn-fafa-primary">Tampilkan</button>
                @if($q || $from || $to || $bayar || $sort !== 'terbaru')
                    <a href="{{ route('purchases.index') }}" class="btn-fafa btn-fafa-secondary">Reset</a>
                @endif
            </div>
        </form>
        <div class="sales-summary">
            <span><strong>{{ number_format($summary->jumlah ?? 0, 0, ',', '.') }}</strong> nota</span>
            <span>Total <strong>Rp {{ number_format($summary->total ?? 0, 0, ',', '.') }}</strong></span>
            <span>Hutang <strong>Rp {{ number_format($summary->hutang ?? 0, 0, ',', '.') }}</strong></span>
        </div>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <div class="table-responsive">
            <table class="fafa-table">
                <thead>
                    <tr>
                        <th>No. Faktur</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th class="sales-money">Total</th>
                        <th>Bayar</th>
                        <th class="sales-money">Sisa</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td><strong>{{ $purchase->no_fak }}</strong></td>
                        <td>{{ date('d/m/Y', strtotime($purchase->tgl_fak)) }}</td>
                        <td>{{ $purchase->nm_sup ?: ($purchase->kd_sup ?: '—') }}</td>
                        <td class="sales-money">Rp {{ number_format($purchase->tot_bayar, 0, ',', '.') }}</td>
                        <td>
                            {{ $purchase->kd_bayar ?: '—' }}
                            @if($purchase->tgl_jt)
                                <span class="sales-sub">{{ date('d/m/Y', strtotime($purchase->tgl_jt)) }}</span>
                            @endif
                        </td>
                        <td class="sales-money">
                            @if((float) $purchase->byr_hutang > 0)
                                Rp {{ number_format($purchase->byr_hutang, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('purchases.edit', $purchase->no_urut) }}" class="btn-fafa btn-fafa-primary btn-fafa-sm">Edit</a>
                            <a href="{{ route('purchases.show', $purchase->no_urut) }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Detail</a>
                            <a href="{{ route('purchases.print', $purchase->no_urut) }}" target="_blank" class="btn-fafa btn-fafa-secondary btn-fafa-sm"><i class="fa fa-print"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada nota pada filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-fafa-pagination :paginator="$purchases" />
</div>
@endsection
