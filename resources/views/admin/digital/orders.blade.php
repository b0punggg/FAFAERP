@extends('layouts.app')
@section('title', 'Order digital')
@section('content')
@include('components.page-header', [
    'title' => 'Order digital',
    'description' => 'Order dikirim dari gudang pusat. Rekap BigSeller masuk sebagai penjualan channel dan tidak mengubah stok.',
])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Impor rekap BigSeller</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('digital.orders.import') }}" enctype="multipart/form-data" class="erp-form">
            @csrf
            <div>
                <label>Channel</label>
                <input name="channel" class="form-control" value="BigSeller" placeholder="BigSeller, Shopee, TikTok">
            </div>
            <div>
                <label>Periode dari</label>
                <input type="date" name="period_start" class="form-control">
            </div>
            <div>
                <label>Sampai</label>
                <input type="date" name="period_end" class="form-control">
            </div>
            <div class="span-2">
                <label>File CSV atau Excel</label>
                <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx" required>
            </div>
            <div>
                <label>&nbsp;</label>
                <button class="btn-fafa btn-fafa-primary">Impor rekap</button>
            </div>
        </form>
        <p class="text-muted mt-2">Nomor SKU dicocokkan ke katalog FAFA. Stok BigSeller hanya ditampilkan di samping stok gudang pusat. Modal dan laba di file disimpan sebagai catatan BigSeller.</p>
    </div>
</div>

@if($currentImport)
<div class="fafa-card mb-3">
    <div class="fafa-card-header">
        <span>Rekap {{ $currentImport->channel }} · {{ $currentImport->filename }}</span>
        <span class="sales-sub">{{ $currentImport->rows_count }} baris, {{ $currentImport->matched_count }} cocok</span>
    </div>
    <div class="fafa-card-body">
        <form method="GET" class="sales-toolbar">
            <div>
                <label for="import">Batch</label>
                <select id="import" name="import" class="form-control">
                    @foreach($importList as $batch)
                        <option value="{{ $batch->id }}" @selected($currentImport->id === $batch->id)>{{ $batch->created_at?->format('d/m/Y H:i') }} · {{ $batch->filename }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="q">Cari</label>
                <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Nama atau SKU">
            </div>
            <div>
                <label for="cocok">Katalog</label>
                <select id="cocok" name="cocok" class="form-control">
                    <option value="">Semua</option>
                    <option value="ya" @selected($cocok === 'ya')>Sudah cocok</option>
                    <option value="tidak" @selected($cocok === 'tidak')>Belum cocok</option>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button class="btn-fafa btn-fafa-secondary">Tampilkan</button>
            </div>
        </form>
        @if($sheetTotals)
            <p class="mt-2">Dana bersih Rp {{ number_format($sheetTotals->dana ?? 0, 0, ',', '.') }} · pesanan bersih {{ number_format($sheetTotals->orders ?? 0, 0, ',', '.') }} · batal {{ number_format($sheetTotals->batal ?? 0, 0, ',', '.') }} · retur Rp {{ number_format($sheetTotals->retur ?? 0, 0, ',', '.') }}</p>
        @endif
    </div>
    <div style="overflow:auto">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Pesanan bersih</th>
                    <th>Qty bersih</th>
                    <th>Dana bersih</th>
                    <th>Batal</th>
                    <th>Retur</th>
                    <th>Potongan</th>
                    <th>Laba BigSeller</th>
                    <th>Stok BigSeller</th>
                    <th>Stok pusat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sheetRows as $row)
                    @php $report = $row->report ?? []; @endphp
                    <tr>
                        <td>
                            {{ $row->product_name ?: $row->sku }}
                            <div class="sales-sub">{{ $row->sku }}@if($row->spu) · SPU {{ $row->spu }}@endif</div>
                            <div class="sales-sub">{{ $row->kd_brg ? 'Katalog '.$row->kd_brg : 'SKU belum ada di katalog' }}</div>
                        </td>
                        <td>{{ number_format($row->orders_net, 0, ',', '.') }}</td>
                        <td>{{ number_format($row->sku_qty_wh_net, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row->dana_bersih, 0, ',', '.') }}</td>
                        <td>{{ number_format($row->cancel_orders, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row->refund_amount, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row->potongan(), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($report['laba_kotor'] ?? 0, 0, ',', '.') }}</td>
                        <td>{{ number_format($row->stock_available ?: $row->stock_total, 0, ',', '.') }}</td>
                        <td>{{ $row->kd_brg ? number_format($central[$row->kd_brg] ?? 0, 2, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted">Tidak ada baris pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$sheetRows" />
</div>
@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('digital.orders.store') }}" class="erp-form">
            @csrf
            <div>
                <label>Channel</label>
                <input name="channel" class="form-control" placeholder="Shopee, TikTok, web" required>
            </div>
            <div>
                <label>No. order channel</label>
                <input name="external_order_id" class="form-control">
            </div>
            <div>
                <label>Kode barang</label>
                <input name="kd_brg" class="form-control" required>
            </div>
            <div>
                <label>Qty</label>
                <input name="qty" type="number" step="0.01" min="0.01" class="form-control" required>
            </div>
            <div>
                <label>Total</label>
                <input name="total" type="number" step="0.01" min="0" class="form-control" required>
            </div>
            <div>
                <label>&nbsp;</label>
                <button class="btn-fafa btn-fafa-primary">Catat order</button>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>Channel</th><th>Barang</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $status = match ($order->status) {
                            'pending', 'confirmed' => 'Menunggu kirim',
                            'shipped' => 'Terkirim',
                            'completed' => 'Selesai',
                            default => $order->status,
                        };
                    @endphp
                    <tr>
                        <td>{{ $order->channel }}<div class="sales-sub">{{ $order->external_order_id }}</div></td>
                        <td>@foreach($order->lines as $line){{ $line->kd_brg }} × {{ $line->qty }} @endforeach</td>
                        <td>{{ $status }}</td>
                        <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                        <td>
                            @if(in_array($order->status, ['pending', 'confirmed']))
                                <form method="POST" action="{{ route('digital.orders.ship', $order) }}">@csrf<button class="btn-fafa btn-fafa-warning btn-fafa-sm">Kirim dari pusat</button></form>
                            @endif
                            @if($order->status === 'shipped')
                                <form method="POST" action="{{ route('digital.orders.complete', $order) }}" class="erp-form">
                                    @csrf
                                    <input name="affiliate_code" placeholder="Kode afiliasi" class="form-control">
                                    <button class="btn-fafa btn-fafa-success btn-fafa-sm">Selesai</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada order digital.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$orders" />
</div>
@endsection
