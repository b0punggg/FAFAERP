@extends('layouts.app')
@section('title', 'History Hapus Nota')
@section('content')
@include('components.page-header', ['title' => 'History void / hapus nota'])

<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>Faktur</th><th>Tanggal</th><th>Total</th><th>Alasan</th><th>Waktu</th><th></th></tr></thead>
<tbody>
@forelse($rows as $r)
    @php
        $snap = is_string($r->snapshot ?? null) ? json_decode($r->snapshot, true) : (array) ($r->snapshot ?? []);
        $header = $snap['header'] ?? [];
        $items = $snap['items'] ?? [];
    @endphp
<tr>
<td>{{ $r->no_fak }}</td>
<td>{{ $r->tgl_jual }}</td>
<td>Rp {{ number_format($r->tot_bayar, 0, ',', '.') }}</td>
<td>{{ \Illuminate\Support\Str::limit($r->reason, 80) }}</td>
<td>{{ $r->created_at }}</td>
<td>
    @if($items)
        <details>
            <summary>Isi nota</summary>
            <div class="small">
                Pelanggan: {{ $header['nm_pel'] ?? '-' }}
                · Bayar: {{ $header['kd_bayar'] ?? '-' }}
                <ul>
                    @foreach($items as $item)
                        <li>{{ $item['kd_brg'] ?? '' }} {{ $item['nm_brg'] ?? '' }} × {{ $item['jml_brg'] ?? '' }}</li>
                    @endforeach
                </ul>
            </div>
        </details>
    @endif
</td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted py-4">Belum ada nota yang di-void.</td></tr>
@endforelse
</tbody></table>
</div><x-fafa-pagination :paginator="$rows" /></div>
@endsection
