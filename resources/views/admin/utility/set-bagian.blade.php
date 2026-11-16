@extends('layouts.app')
@section('title', 'Set Bagian')
@section('content')
@include('components.page-header', ['title' => 'Set bagian barang'])
@if(!$hasColumn)<div class="alert alert-warning">Kolom <code>mas_brg.id_bag</code> belum ada di database.</div>@else
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card"><div class="fafa-card-body">
<form method="POST" action="{{ route('utility.set-bagian.apply') }}">@csrf
<div class="form-group"><label>Bagian</label><select name="id_bag" class="form-control" required>@foreach($bagian as $b)<option value="{{ $b->no_urut }}">{{ $b->nm_bag }}</option>@endforeach</select></div>
<div class="form-group"><label>Daftar SKU (pisah koma/spasi/baris)</label><textarea name="skus" class="form-control" rows="5" required></textarea></div>
<button class="btn-fafa btn-fafa-primary">Terapkan</button>
</form></div></div>@endif
@endsection
