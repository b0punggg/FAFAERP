@extends('layouts.app')
@section('title', 'Stok di bawah batas')
@section('content')
@include('components.page-header', [
    'title' => 'Stok di bawah batas',
    'description' => 'Barang gerai yang stoknya sudah menyentuh batas pesan ulang',
])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('procurement.rop.refresh') }}" class="mb-3">
    @csrf
    <button class="btn-fafa btn-fafa-secondary">Hitung ulang</button>
</form>
<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>Gerai</th><th>Barang</th><th>Stok</th><th>Batas</th></tr></thead>
            <tbody>
                @forelse($alerts as $alert)
                    <tr>
                        <td>{{ $alert->branch_name ?: $alert->kd_toko ?: '—' }}</td>
                        <td>{{ $alert->product_name ?: '—' }}<div class="sales-sub">{{ $alert->sku }}</div></td>
                        <td>{{ number_format($alert->qty_on_hand, 2, ',', '.') }}</td>
                        <td>{{ number_format($alert->reorder_point, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada barang di bawah batas. Tekan hitung ulang untuk memeriksa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$alerts" />
</div>
@endsection
