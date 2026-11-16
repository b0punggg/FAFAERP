@extends('layouts.app')
@section('title', 'Approval Void')
@section('content')
@include('components.page-header', ['title' => 'Permintaan void penjualan'])

<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>ID</th><th>Sale ID</th><th>Alasan</th><th></th></tr></thead>
<tbody>
@forelse($pending as $row)
<tr>
<td>{{ $row->id }}</td>
<td><a href="{{ route('sales.show', $row->sale_id) }}">#{{ $row->sale_id }}</a></td>
<td>{{ $row->reason }}</td>
<td>
<form method="POST" action="{{ route('sales.void-approve', $row->id) }}">@csrf
<button class="btn-fafa btn-fafa-sm btn-fafa-danger" onclick="return confirm('Setujui void? Stok akan dikembalikan.')">Setujui void</button>
</form>
</td>
</tr>
@empty
<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada permintaan pending.</td></tr>
@endforelse
</tbody></table>
</div><x-fafa-pagination :paginator="$pending" /></div>
@endsection
