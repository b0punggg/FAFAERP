@extends('layouts.app')
@section('title', 'Penjualan Pending')
@section('content')
@include('components.page-header', ['title' => 'Draft / Pending Penjualan'])

<div class="mb-3"><a href="{{ route('sales.create') }}" class="btn-fafa btn-fafa-primary">Transaksi baru</a></div>

<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Faktur</th><th>Tanggal</th><th>Diperbarui</th><th></th></tr></thead>
<tbody>
@forelse($drafts as $d)
<tr>
<td>{{ $d->no_fak }}</td>
<td>{{ $d->tgl_jual->format('d/m/Y') }}</td>
<td>{{ $d->updated_at->diffForHumans() }}</td>
<td>
<a href="{{ route('sales.create', ['draft_id' => $d->id]) }}" class="btn-fafa btn-fafa-sm btn-fafa-primary">Lanjutkan</a>
<form method="POST" action="{{ route('sales.drafts.destroy', $d) }}" class="d-inline" onsubmit="return confirm('Hapus draft?')">@csrf @method('DELETE')
<button class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button></form>
</td>
</tr>
@empty
<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada draft.</td></tr>
@endforelse
</tbody></table>
</div><x-fafa-pagination :paginator="$drafts" /></div>
@endsection
