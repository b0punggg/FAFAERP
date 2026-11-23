@extends('layouts.app')

@section('title', 'Tutup toko')

@section('content')
@include('components.page-header', [
    'title' => 'Tutup toko / settlement harian',
    'description' => 'Angka POS terisi otomatis dari nota hari itu. Kasir hanya menghitung fisik kas.',
])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
    $selisih = (float) ($today->physical_cash ?? 0) - (float) $pos['pos_cash_total'];
@endphp

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="sales-toolbar mb-3">
            <div>
                <label>Tanggal</label>
                <input type="date" name="settlement_date" class="form-control" value="{{ $date }}">
            </div>
            <div>
                <button class="btn-fafa btn-fafa-secondary">Tampilkan POS</button>
            </div>
        </form>
        <div class="sales-summary">
            <span><strong>{{ number_format($pos['nota'], 0, ',', '.') }}</strong> nota</span>
            <span>Kas POS <strong>Rp {{ number_format($pos['pos_cash_total'], 0, ',', '.') }}</strong></span>
            <span>QRIS <strong>Rp {{ number_format($pos['qris_total'], 0, ',', '.') }}</strong></span>
            <span>EDC <strong>Rp {{ number_format($pos['edc_total'], 0, ',', '.') }}</strong></span>
            <span>Piutang baru <strong>Rp {{ number_format($pos['piutang'], 0, ',', '.') }}</strong></span>
        </div>
        <form method="POST" action="{{ route('retail.settlements.store') }}" class="sales-toolbar mt-3">
            @csrf
            <input type="hidden" name="settlement_date" value="{{ $date }}">
            <div>
                <label>Fisik kas (hitung laci)</label>
                <input name="physical_cash" type="number" step="0.01" class="form-control" value="{{ old('physical_cash', $today->physical_cash ?? '') }}" required>
            </div>
            <div>
                <button class="btn-fafa btn-fafa-primary">Simpan tutup toko</button>
            </div>
        </form>
        @if($today)
            <p class="mt-2 mb-0">Status: <strong>{{ $today->status }}</strong>
                · Selisih fisik − POS:
                <strong>Rp {{ number_format((float) $today->physical_cash - (float) $today->pos_cash_total, 0, ',', '.') }}</strong>
            </p>
        @endif
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="sales-money">Kas POS</th>
                    <th class="sales-money">Fisik</th>
                    <th class="sales-money">QRIS</th>
                    <th class="sales-money">EDC</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $row)
                <tr>
                    <td>{{ $row->settlement_date }}</td>
                    <td class="sales-money">Rp {{ number_format($row->pos_cash_total, 0, ',', '.') }}</td>
                    <td class="sales-money">Rp {{ number_format($row->physical_cash, 0, ',', '.') }}</td>
                    <td class="sales-money">Rp {{ number_format($row->qris_total, 0, ',', '.') }}</td>
                    <td class="sales-money">Rp {{ number_format($row->edc_total, 0, ',', '.') }}</td>
                    <td>{{ $row->status }}</td>
                    <td>
                        @if($row->status !== 'approved' && \App\Support\StaffAccess::can(auth()->user(), 'store'))
                            <form method="POST" action="{{ route('retail.settlements.approve', $row) }}">@csrf
                                <button class="btn-fafa btn-fafa-sm btn-fafa-primary">Setujui</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada tutup toko.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$items" />
</div>
@endsection
