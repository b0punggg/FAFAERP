@extends('layouts.app')
@section('title', 'Riwayat Opname')
@section('content')
@include('components.page-header', ['title' => 'Riwayat stok opname', 'actionUrl' => route('retail.opname.index'), 'actionLabel' => 'Lembar opname'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>ID</th><th>Tanggal</th><th>Status</th><th>Keterangan</th><th></th></tr></thead>
<tbody>@forelse($sessions as $s)<tr>
<td>{{ $s->id }}</td><td>{{ $s->tgl_opname }}</td><td>{{ $s->status }}</td><td>{{ $s->notes ?? '—' }}</td>
<td>@if($s->status==='draft')
<form method="POST" action="{{ route('retail.opname.approve', $s->id) }}">@csrf<button class="btn-fafa btn-fafa-sm btn-fafa-primary">Approve & posting</button></form>
@endif</td>
</tr>@empty<tr><td colspan="5" class="text-muted text-center">Belum ada opname.</td></tr>@endforelse</tbody></table>
</div><x-fafa-pagination :paginator="$sessions" /></div>
@endsection
