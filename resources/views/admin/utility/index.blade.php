@extends('layouts.app')
@section('title', 'Pemeliharaan')
@section('content')
@include('components.page-header', ['title' => 'Utility & Pemeliharaan', 'description' => 'Gelombang G'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="fafa-card mb-3"><div class="fafa-card-header">Tanggal kerja</div><div class="fafa-card-body">
<form method="POST" action="{{ route('utility.business-date') }}" class="form-inline">@csrf
<input type="date" name="business_date" value="{{ $businessDate }}" class="form-control mr-2" required>
<button class="btn-fafa btn-fafa-primary">Set tanggal kerja</button>
</form><p class="text-muted small mb-0 mt-2">Digunakan default di penjualan (legacy <code>tgl_set</code>).</p>
</div></div>
<div class="row">
@foreach([
  ['Cetak barcode', route('utility.barcode'), 'fa-barcode'],
  ['Setting printer', route('utility.settings'), 'fa-print'],
] as $item)
<div class="col-md-4 mb-3"><div class="fafa-card h-100"><div class="fafa-card-body"><a href="{{ $item[1] }}"><i class="fa {{ $item[2] }}"></i> {{ $item[0] }}</a></div></div></div>
@endforeach
<div class="col-md-4 mb-3"><div class="fafa-card h-100"><div class="fafa-card-body">
<form method="POST" action="{{ route('utility.backup') }}">@csrf<button class="btn-fafa btn-fafa-secondary"><i class="fa fa-download"></i> Backup database</button></form>
<p class="small text-muted mb-0">Juga: <code>php artisan erp:backup-database</code></p>
</div></div></div>
</div>
@endsection
