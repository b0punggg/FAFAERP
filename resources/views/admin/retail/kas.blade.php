@extends('layouts.app')
@section('title', 'Kas')
@section('content')
@include('components.page-header', ['title' => 'Kas Masuk / Keluar', 'description' => 'Saldo kumulatif: Rp '.number_format($saldo, 0, ',', '.')])

<div class="fafa-card mb-3"><div class="fafa-card-body">
<form method="GET" class="form-inline mb-3">
<select name="month" class="form-control mr-2">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month===$m)>{{ $m }}</option>@endfor</select>
<input type="number" name="year" value="{{ $year }}" class="form-control mr-2" style="width:100px">
<button class="btn-fafa btn-fafa-secondary mr-2">Filter</button>
<a href="{{ route('retail.kas.print', ['month'=>$month,'year'=>$year]) }}" target="_blank" class="btn-fafa btn-fafa-primary">Cetak bulan ini</a>
</form>
<form method="POST" action="{{ route('retail.kas.store') }}" class="form-fafa row align-items-end">@csrf
<div class="col-md-2 form-group"><label>Tanggal</label><input type="date" name="tgl" value="{{ date('Y-m-d') }}" class="form-control" required></div>
<div class="col-md-2 form-group"><label>Jenis</label><select name="direction" class="form-control"><option value="in">Masuk</option><option value="out">Keluar</option></select></div>
<div class="col-md-2 form-group"><label>Nominal</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Keterangan</label><input name="description" class="form-control"></div>
<div class="col-md-2 form-group"><label>Jurnal GL selalu tercatat</label></div>
<div class="col-md-1"><button class="btn-fafa btn-fafa-primary">Simpan</button></div>
</form></div></div>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Nominal</th><th>Keterangan</th><th>GL</th></tr></thead>
<tbody>@foreach($entries as $e)<tr><td>{{ $e->tgl }}</td><td>@if($e->direction==='in')<span class="badge-fafa badge-success">Masuk</span>@else<span class="badge-fafa badge-danger">Keluar</span>@endif</td><td>Rp {{ number_format($e->amount, 0, ',', '.') }}</td><td>{{ $e->description }}</td><td>@if(!empty($e->journal_entry_id))✓@elseif(!empty($e->post_to_gl))—@endif</td></tr>@endforeach</tbody></table>
</div><x-fafa-pagination :paginator="$entries" /></div>
@endsection
