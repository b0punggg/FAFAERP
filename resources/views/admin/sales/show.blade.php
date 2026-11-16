@extends('layouts.app')
@section('title', 'Detail Penjualan')
@section('content')
@include('components.page-header', [
    'title' => 'Faktur '.$sale->no_fak,
    'description' => $sale->tgl_jual,
])
<div class="mb-3">
    <a href="{{ route('sales.print', $sale->no_urut) }}" target="_blank" class="btn-fafa btn-fafa-primary"><i class="fa fa-print"></i> Cetak</a>
    @if($sale->kd_member)<a href="{{ route('sales.print', ['id' => $sale->no_urut, 'format' => 'member']) }}" target="_blank" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Cetak Member</a>@endif
    @if($sale->kd_crew)<a href="{{ route('sales.print', ['id' => $sale->no_urut, 'format' => 'crew']) }}" target="_blank" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Cetak Crew</a>@endif
    <a href="{{ route('sales.index') }}" class="btn-fafa btn-fafa-secondary">Kembali</a>
</div>
<div class="fafa-card mb-3"><div class="fafa-card-body">
<form method="POST" action="{{ route('sales.void-request', $sale) }}" class="form-fafa row">@csrf
<div class="col-md-8 form-group mb-0"><label>Void / batalkan nota (perlu approval)</label><input name="reason" class="form-control" required placeholder="Alasan void…"></div>
<div class="col-md-4 align-self-end"><button class="btn-fafa btn-fafa-danger w-100">Ajukan void</button></div>
</form>
</div></div>
<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>SKU</th><th>Nama</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
<tbody>@foreach($sale->items as $item)<tr><td>{{ $item->kd_brg }}</td><td>{{ $item->nm_brg }}</td><td>{{ $item->jml_brg }}</td><td>{{ number_format($item->hrg_jual, 0, ',', '.') }}</td><td>{{ number_format($item->tot_jual, 0, ',', '.') }}</td></tr>@endforeach</tbody>
<tfoot><tr><td colspan="4" class="text-right"><strong>Total bayar</strong></td><td><strong>Rp {{ number_format($sale->tot_bayar, 0, ',', '.') }}</strong></td></tr></tfoot>
</table></div></div>
@endsection
