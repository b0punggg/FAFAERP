@extends('layouts.app')
@section('title', 'Sales TO')
@section('content')
@include('components.page-header', [
    'title' => 'Sales TO',
    'description' => 'Order yang disetujui dikirim dari gudang kanvas. Order di atas limit kredit, pembayaran, dan komisi tidak mengurangi stok.',
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
            <thead><tr><th>Kode</th><th>Nama</th><th>Limit kredit</th></tr></thead>
            <tbody>
                @forelse($outlets as $outlet)
                    <tr>
                        <td>{{ $outlet->code }}</td>
                        <td>{{ $outlet->name }}</td>
                        <td>Rp {{ number_format($outlet->credit_limit, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">Belum ada outlet.</td></tr>
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
        <p class="text-muted mt-2">Pembayaran mencatat pelunasan dan komisi. Stok tidak berubah.</p>
    </div>
</div>
@endsection
