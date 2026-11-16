@extends('layouts.app')
@section('title', 'Biaya Operasional')
@section('content')
@include('components.page-header', ['title' => 'Biaya Operasional'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card mb-3"><div class="fafa-card-body">
<form method="POST" action="{{ route('retail.expenses.store') }}" class="row align-items-end">@csrf
<div class="col-md-2"><label>Tanggal</label><input type="date" name="tgl_biaya" value="{{ date('Y-m-d') }}" class="form-control" required></div>
<div class="col-md-2"><label>Nominal</label><input type="number" step="0.01" name="nominal" class="form-control" required></div>
<div class="col-md-2"><label>Jenis</label><select name="id_jenis" class="form-control">@foreach($jenis as $id=>$nm)<option value="{{ $id }}">{{ $nm }}</option>@endforeach</select></div>
<div class="col-md-4"><label>Keterangan</label><input name="ket_biaya" class="form-control"></div>
<div class="col-md-1"><label><input type="checkbox" name="post_to_gl" value="1"> GL</label></div>
<div class="col-md-1"><button class="btn-fafa btn-fafa-primary w-100">Simpan</button></div>
</form></div></div>
<form method="GET" class="mb-2"><input name="q" value="{{ $q }}" class="form-control form-control-sm d-inline-block w-auto" placeholder="Cari ket"><button class="btn-fafa btn-fafa-sm btn-fafa-secondary">Cari</button></form>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Tgl</th><th>Jenis</th><th>Nominal</th><th>Ket</th><th></th></tr></thead>
<tbody>@foreach($items as $row)<tr>
<td>{{ $row->tgl_biaya }}</td><td>{{ $row->nm_jenis }}</td><td>{{ number_format($row->nominal,0,',','.') }}</td><td>{{ $row->ket_biaya }}</td>
<td>@if(!$row->journal_entry_id)<form method="POST" action="{{ route('retail.expenses.destroy', $row->id) }}">@csrf @method('DELETE')<button class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button></form>@endif</td>
</tr>@endforeach</tbody></table></div><x-fafa-pagination :paginator="$items" /></div>
@endsection
