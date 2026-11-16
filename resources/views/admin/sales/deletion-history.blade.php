@extends('layouts.app')
@section('title', 'History Hapus Nota')
@section('content')
@include('components.page-header', ['title' => 'History void / hapus nota'])

<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Faktur</th><th>Tanggal</th><th>Total</th><th>Alasan</th><th>Waktu</th></tr></thead>
<tbody>
@foreach($rows as $r)
<tr>
<td>{{ $r->no_fak }}</td>
<td>{{ $r->tgl_jual }}</td>
<td>Rp {{ number_format($r->tot_bayar, 0, ',', '.') }}</td>
<td>{{ \Illuminate\Support\Str::limit($r->reason, 80) }}</td>
<td>{{ $r->created_at }}</td>
</tr>
@endforeach
</tbody></table>
</div><x-fafa-pagination :paginator="$rows" /></div>
@endsection
