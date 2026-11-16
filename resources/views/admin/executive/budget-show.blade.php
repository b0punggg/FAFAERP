@extends('layouts.app')

@section('title', $budget->title)

@section('content')
@php
    $plan = $budget->plan_5w2h ?? [];
    $groups = $budget->lines->groupBy(fn ($line) => $line->kegiatan ?: 'Lainnya');
    $total = $budget->lines->sum('planned_amount');
    $actual = $budget->lines->sum('actual_amount');
@endphp

@include('components.page-header', [
    'title' => $budget->title,
    'description' => ($budget->company->legal_name ?? '').' · '.$budget->statusLabel(),
    'actionUrl' => route('executive.budgets.print', $budget),
    'actionLabel' => 'Cetak',
])

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="pos-actions mb-3">
    <a href="{{ route('executive.budgets.index') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Daftar</a>
    @if(\App\Support\StaffAccess::can(auth()->user(), 'budget_write') && $budget->status === 'draft')
        <a href="{{ route('executive.budgets.edit', $budget) }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Ubah</a>
        <form method="POST" action="{{ route('executive.budgets.submit', $budget) }}" class="d-inline">@csrf<button class="btn-fafa btn-fafa-primary btn-fafa-sm">Ajukan</button></form>
    @endif
    @if(\App\Support\StaffAccess::can(auth()->user(), 'budget_write') && $budget->status === 'submitted')
        <form method="POST" action="{{ route('executive.budgets.validate', $budget) }}" class="d-inline">@csrf<button class="btn-fafa btn-fafa-primary btn-fafa-sm">Periksa</button></form>
    @endif
    @if(\App\Support\StaffAccess::can(auth()->user(), 'budget_approve') && $budget->status === 'validated')
        <form method="POST" action="{{ route('executive.budgets.approve', $budget) }}" class="d-inline">@csrf<button class="btn-fafa btn-fafa-success btn-fafa-sm">Setujui</button></form>
    @endif
</div>

<div class="fafa-card mb-3"><div class="fafa-card-body">
    <p><strong>When.</strong> {{ optional($budget->period_start)->format('d/m/Y') }} – {{ optional($budget->period_end)->format('d/m/Y') }}</p>
    <p><strong>Where.</strong> {{ $budget->tempat ?: '—' }}</p>
    <p><strong>Why.</strong></p>
    <ol>@foreach($plan['why'] ?? [] as $why)<li>{{ $why }}</li>@endforeach</ol>
    <p><strong>Sasaran.</strong> {{ $plan['sasaran'] ?? '—' }} @if(!empty($plan['sasaran_jumlah'])) ({{ $plan['sasaran_jumlah'] }} orang) @endif</p>
    <p><strong>Pelaksana.</strong></p>
    <ul>@foreach($plan['pelaksana'] ?? [] as $person)<li>{{ $person['peran'] ?? '' }} — {{ $person['nama'] ?? '' }}</li>@endforeach</ul>
    @foreach($plan['how'] ?? [] as $block)
        <p><strong>{{ $block['nama'] ?: 'Kegiatan' }}</strong></p>
        <ol>@foreach($block['langkah'] ?? [] as $step)<li>{{ $step }}</li>@endforeach</ol>
    @endforeach
</div></div>

<div class="fafa-card mb-3"><div class="fafa-card-body" style="padding:0">
    <table class="fafa-table">
        <thead><tr><th>Kegiatan</th><th>Pengeluaran</th><th>Qty</th><th>Harga</th><th>Jumlah</th><th>Realisasi</th></tr></thead>
        <tbody>
            @forelse($groups as $name => $lines)
                @foreach($lines as $line)
                    <tr>
                        <td>{{ $loop->first ? $name : '' }}</td>
                        <td>{{ $line->uraian ?: $line->cost_center }}</td>
                        <td>{{ number_format($line->qty, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($line->unit_price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($line->planned_amount, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($line->actual_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6">Belum ada rincian biaya.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="fafa-card-body">Jumlah rencana Rp {{ number_format($total, 0, ',', '.') }}. Realisasi Rp {{ number_format($actual, 0, ',', '.') }}. Realisasi belum masuk laba rugi.</div>
</div>

@if(\App\Support\StaffAccess::can(auth()->user(), 'budget_write') && $budget->status === 'approved' && $budget->lines->isNotEmpty())
<form method="POST" action="{{ route('executive.budgets.actual', $budget) }}" class="fafa-card mb-3">
    @csrf
    <div class="fafa-card-body">
        <h3 class="erp-h">Isi realisasi</h3>
        @foreach($budget->lines as $line)
            <div class="erp-form mb-2">
                <div class="span-2"><label>{{ $line->uraian ?: $line->cost_center }}</label></div>
                <input type="number" min="0" step="0.01" name="actual[{{ $line->id }}]" class="form-control" value="{{ $line->actual_amount }}">
            </div>
        @endforeach
        <button class="btn-fafa btn-fafa-primary btn-fafa-sm">Simpan realisasi</button>
    </div>
</form>
@endif

<div class="fafa-card">
    <div class="fafa-card-body">
        <h3 class="erp-h">Hasil kegiatan</h3>
        <form method="POST" action="{{ route('executive.budgets.notes', $budget) }}" class="erp-form mb-3">
            @csrf
            <div>
                <label>Jenis</label>
                <select name="jenis" class="form-control">
                    <option value="crm">CRM</option>
                    <option value="zoom">Zoom</option>
                    <option value="visit">Visit</option>
                </select>
            </div>
            <div>
                <label>Tanggal</label>
                <input type="date" name="tgl" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div>
                <label>Pelanggan</label>
                <input name="pelanggan" class="form-control">
            </div>
            <div class="span-2">
                <label>Hasil</label>
                <input name="hasil" class="form-control" required placeholder="Ringkasan hasil CRM, zoom, atau kunjungan">
            </div>
            <div><label>&nbsp;</label><button class="btn-fafa btn-fafa-primary">Catat</button></div>
        </form>
        <ul>
            @forelse($budget->notes as $note)
                <li>{{ optional($note->tgl)->format('d/m/Y') }} · {{ strtoupper($note->jenis) }} · {{ $note->pelanggan ?: '—' }} — {{ $note->hasil }}</li>
            @empty
                <li>Belum ada catatan hasil.</li>
            @endforelse
        </ul>
        <p class="text-muted">Yang membuat: {{ $plan['dibuat'] ?? '—' }}. Mengetahui: {{ $plan['mengetahui'] ?? '—' }}. Menyetujui: {{ $plan['menyetujui'] ?? '—' }}.</p>
    </div>
</div>
@endsection
