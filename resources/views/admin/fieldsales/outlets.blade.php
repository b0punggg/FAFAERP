@extends('layouts.app')
@section('title', 'Sales TO')
@section('content')
@include('components.page-header', [
    'title' => 'Sales TO',
    'description' => 'Kredit outlet memakai sisa piutang. Kirim dari kanvas memotong stok dan membuat faktur antar-PT retail ↔ TO. Komisi 2% dari pelunasan (cash-in).',
])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Outlet</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('fieldsales.outlets.store') }}" class="erp-form mb-3">
            @csrf
            <div><label>Kode</label><input name="code" class="form-control" required></div>
            <div><label>Nama</label><input name="name" class="form-control" required></div>
            <div><label>Limit kredit</label><input name="credit_limit" type="number" min="0" class="form-control" value="0"></div>
            <div><label>&nbsp;</label><button class="btn-fafa btn-fafa-primary">Tambah outlet</button></div>
        </form>
        <table class="fafa-table">
            <thead><tr><th>Kode</th><th>Nama</th><th>Limit kredit</th><th>Sisa piutang</th></tr></thead>
            <tbody>
                @forelse($outlets as $outlet)
                    @php
                        $ageRow = collect($aging['outlets'] ?? [])->first(fn ($row) => $row['outlet']->id === $outlet->id);
                    @endphp
                    <tr>
                        <td>{{ $outlet->code }}</td>
                        <td>{{ $outlet->name }}</td>
                        <td>Rp {{ number_format($outlet->credit_limit, 0, ',', '.') }}</td>
                        <td>
                            Rp {{ number_format($ageRow['open'] ?? 0, 0, ',', '.') }}
                            @if(!empty($ageRow['over_limit']))
                                <span class="sales-sub">Melebihi limit</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada outlet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$outlets" />
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Order</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('fieldsales.orders.store') }}" class="erp-form mb-3">
            @csrf
            <div>
                <label>Outlet</label>
                <select name="sfa_outlet_id" class="form-control" required>
                    <option value="">Pilih outlet</option>
                    @foreach($outletChoices as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Kode barang</label><input name="kd_brg" class="form-control" required></div>
            <div><label>Qty</label><input name="qty" type="number" step="0.01" min="0.01" class="form-control" required></div>
            <div><label>Total</label><input name="total" type="number" step="0.01" min="0" class="form-control" required></div>
            <div><label>&nbsp;</label><button class="btn-fafa btn-fafa-primary">Buat order</button></div>
        </form>
        <table class="fafa-table">
            <thead><tr><th>Outlet</th><th>Barang</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $status = match ($order->status) {
                            'approved' => 'Disetujui',
                            'credit_hold' => 'Tertahan kredit',
                            'delivered' => 'Terkirim',
                            default => $order->status,
                        };
                    @endphp
                    <tr>
                        <td>{{ $order->outlet->name ?? '—' }}</td>
                        <td>@foreach($order->lines as $line){{ $line->kd_brg }} × {{ $line->qty }} @endforeach</td>
                        <td>{{ $status }}</td>
                        <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                        <td>
                            @if($order->status === 'approved')
                                <form method="POST" action="{{ route('fieldsales.orders.deliver', $order) }}">@csrf<button class="btn-fafa btn-fafa-warning btn-fafa-sm">Kirim dari kanvas</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada order.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-header">Pembayaran</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('fieldsales.payments.store') }}" class="erp-form">
            @csrf
            <div>
                <label>Outlet</label>
                <select name="sfa_outlet_id" class="form-control" required>
                    <option value="">Pilih outlet</option>
                    @foreach($outletChoices as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Tanggal</label><input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
            <div><label>Jumlah</label><input name="amount" type="number" min="0.01" step="0.01" class="form-control" required></div>
            <div><label>&nbsp;</label><button class="btn-fafa btn-fafa-primary">Catat bayar</button></div>
        </form>
        <p class="text-muted mt-2">Pembayaran tidak boleh melebihi sisa piutang. Stok tidak berubah. Komisi 2% dari jumlah yang masuk.</p>
    </div>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Umur piutang {{ isset($aging['as_of']) ? date('d/m/Y', strtotime($aging['as_of'])) : '' }}</div>
    <div class="fafa-card-body">
        <div class="report-summary" style="margin-top:0">
            <div><span>0–30 hari</span><strong>Rp {{ number_format($aging['totals']['bucket_0_30'] ?? 0, 0, ',', '.') }}</strong></div>
            <div><span>31–60 hari</span><strong>Rp {{ number_format($aging['totals']['bucket_31_60'] ?? 0, 0, ',', '.') }}</strong></div>
            <div><span>61 hari atau lebih</span><strong>Rp {{ number_format($aging['totals']['bucket_61_plus'] ?? 0, 0, ',', '.') }}</strong></div>
        </div>
        <table class="fafa-table mt-3">
            <thead><tr><th>Outlet</th><th>0–30</th><th>31–60</th><th>61+</th></tr></thead>
            <tbody>
                @forelse($aging['outlets'] ?? [] as $row)
                    <tr>
                        <td>{{ $row['outlet']->name }}</td>
                        <td>Rp {{ number_format($row['bucket_0_30'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row['bucket_31_60'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row['bucket_61_plus'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">Belum ada piutang terkirim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-header">Komisi dari cash-in</div>
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>ID pelunasan</th><th>Sales</th><th>Komisi</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($commissions as $row)
                    <tr>
                        <td>{{ $row->ar_settlement_id }}</td>
                        <td>{{ $row->sales_user_id }}</td>
                        <td>Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                        <td>{{ $row->status === 'released' ? 'Dirilis' : $row->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">Belum ada komisi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
