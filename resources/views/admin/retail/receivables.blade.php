@extends('layouts.app')
@section('title', 'Piutang')
@section('content')
@include('components.page-header', ['title' => 'Pembayaran Piutang'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="GET" class="mb-3 form-inline"><input name="q" value="{{ $q }}" class="form-control mr-2" placeholder="Cari no. faktur"><button class="btn-fafa btn-fafa-secondary">Cari</button></form>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0"><table class="fafa-table">
<thead><tr><th>Faktur</th><th>Tanggal</th><th>Sisa Piutang</th><th>Bayar (partial)</th></tr></thead>
<tbody>@foreach($sales as $sale)<tr>
<td>{{ $sale->no_fak }}</td><td>{{ $sale->tgl_jual }}</td><td>Rp {{ number_format($sale->byr_hutang, 0, ',', '.') }}</td>
<td><form method="POST" action="{{ route('retail.receivables.pay', $sale) }}" class="form-inline">@csrf
<input type="date" name="tgl_bayar" value="{{ date('Y-m-d') }}" class="form-control form-control-sm mr-1" required>
<input type="number" step="0.01" name="amount" max="{{ $sale->byr_hutang }}" class="form-control form-control-sm mr-1" placeholder="Nominal" required>
<label class="mr-1 small"><input type="checkbox" name="post_to_gl" value="1"> GL</label>
<button class="btn-fafa btn-fafa-sm btn-fafa-primary">Bayar & cetak</button>
</form></td></tr>@endforeach</tbody></table></div><x-fafa-pagination :paginator="$sales" /></div>
@endsection
