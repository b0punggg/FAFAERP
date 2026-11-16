@extends('layouts.app')
@section('title', 'Pesanan gudang pusat')
@section('content')
@include('components.page-header', [
    'title' => 'Pesanan gudang pusat',
    'description' => 'Rencana beli untuk gudang pusat. Barang masuk lewat pembelian atau aplikasi gudang.',
    'actionUrl' => route('procurement.po.create'),
    'actionLabel' => 'Buat pesanan',
])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>No. pesanan</th><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->po_no }}</td>
                        <td>{{ $order->order_date }}</td>
                        <td>{{ $order->status === 'draft' ? 'Draf' : $order->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$orders" />
</div>
@endsection
