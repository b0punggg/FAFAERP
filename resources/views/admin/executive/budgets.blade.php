@extends('layouts.app')

@section('title', 'Program & Budget')

@section('content')
@include('components.page-header', [
    'title' => 'Program & budget',
    'description' => 'Rencana kegiatan 5W2H dan rincian biayanya',
    'actionUrl' => \App\Support\StaffAccess::can(auth()->user(), 'budget_write') ? route('executive.budgets.create') : null,
    'actionLabel' => 'Program baru',
])

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="sales-toolbar">
            <div>
                <label for="q">Cari program</label>
                <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Nama program">
            </div>
            <div>
                <label for="company_id">PT</label>
                <select id="company_id" name="company_id" class="form-control">
                    <option value="">Semua</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) $companyId === (string) $company->id)>{{ $company->legal_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Semua</option>
                    @foreach(['draft' => 'Draf', 'submitted' => 'Diajukan', 'validated' => 'Diperiksa', 'approved' => 'Disetujui'] as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button class="btn-fafa btn-fafa-primary">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>PT</th>
                    <th>Periode</th>
                    <th>Total rencana</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgets as $budget)
                    <tr>
                        <td>{{ $budget->title }}<div class="sales-sub">{{ $budget->tempat ?: '—' }}</div></td>
                        <td>{{ $budget->company->legal_name ?? '—' }}</td>
                        <td>{{ optional($budget->period_start)->format('d/m/Y') ?: $budget->year }} – {{ optional($budget->period_end)->format('d/m/Y') ?: '' }}</td>
                        <td>Rp {{ number_format($budget->total_rencana ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $budget->statusLabel() }}</td>
                        <td><a class="btn-fafa btn-fafa-secondary btn-fafa-sm" href="{{ route('executive.budgets.show', $budget) }}">Buka</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada program.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$budgets" />
</div>
@endsection
