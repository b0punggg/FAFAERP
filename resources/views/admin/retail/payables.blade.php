@extends('layouts.app')
@section('title', 'Nota belum bayar')
@section('content')
@include('components.page-header', ['title' => 'Nota belum bayar'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="GET" class="mb-3 form-inline">
    <input name="q" value="{{ $q }}" class="form-control mr-2" placeholder="Cari no. faktur atau supplier">
    <button class="btn-fafa btn-fafa-secondary">Cari</button>
</form>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0"><table class="fafa-table">
<thead><tr><th>Faktur</th><th>Tanggal</th><th>Jatuh tempo</th><th>Supplier</th><th>Total</th><th>Terbayar</th><th>Sisa</th><th>Bayar</th></tr></thead>
<tbody>
@forelse($purchases as $p)
<tr>
<td>{{ $p->no_fak }}</td>
<td>{{ $p->tgl_fak }}</td>
<td>{{ $p->tgl_jt ? date('d/m/Y', strtotime($p->tgl_jt)) : '—' }}</td>
<td>{{ $p->nm_sup ?: ($p->kd_sup ?: '—') }}</td>
<td>{{ number_format($p->tot_bayar,0,',','.') }}</td>
<td>{{ number_format($p->byr_tunai,0,',','.') }}</td>
<td>{{ number_format($p->byr_hutang,0,',','.') }}</td>
<td><form method="POST" action="{{ route('retail.payables.pay', $p) }}" class="form-inline">@csrf
<input type="date" name="tgl_bayar" value="{{ date('Y-m-d') }}" class="form-control form-control-sm mr-1" required>
<input type="number" step="0.01" name="amount" value="{{ $p->byr_hutang }}" max="{{ $p->byr_hutang }}" class="form-control form-control-sm mr-1" required>
<label class="mr-1 small"><input type="checkbox" name="post_to_gl" value="1" checked> GL</label>
<button class="btn-fafa btn-fafa-sm btn-fafa-primary">Bayar</button>
</form></td>
</tr>
@empty
<tr><td colspan="8">Tidak ada nota tempo yang masih terbuka.</td></tr>
@endforelse
</tbody></table></div><x-fafa-pagination :paginator="$purchases" /></div>
@endsection
