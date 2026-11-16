@extends('layouts.app')
@section('title', 'Riwayat Poin')
@section('content')
@include('components.page-header', ['title' => 'Riwayat poin member', 'description' => $kdMember ? 'Filter: '.$kdMember : 'Semua'])

<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Member</th><th>Ref</th><th>Tgl</th><th>Masuk</th><th>Keluar</th><th>Saldo</th><th>Ket</th></tr></thead>
<tbody>@foreach($rows as $r)<tr>
<td>{{ $r->kd_member }}</td><td>{{ $r->no_fakjual }}</td><td>{{ $r->tgl_transaksi }}</td>
<td>{{ $r->poin_masuk }}</td><td>{{ $r->poin_keluar }}</td><td>{{ $r->poin_saldo }}</td><td>{{ $r->keterangan }}</td>
</tr>@endforeach</tbody></table></div><x-fafa-pagination :paginator="$rows" /></div>
@endsection
