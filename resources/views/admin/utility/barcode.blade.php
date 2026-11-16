@extends('layouts.app')
@section('title', 'Cetak Barcode')
@section('content')
@include('components.page-header', ['title' => 'Cetak Barcode', 'description' => 'Pilih barang gerai ini, tentukan jumlah salinan, lalu cetak barcode atau QR'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

@if(!$branchId)
<div class="alert alert-warning">Pilih gerai dulu sebelum mencetak barcode.</div>
@else
<form method="GET" class="form-inline mb-3">
  <input name="q" value="{{ $q }}" class="form-control mr-2" placeholder="SKU, barcode, atau nama" style="min-width:16rem">
  <button class="btn-fafa btn-fafa-primary mr-2">Cari</button>
  @if($q !== '')<a href="{{ route('utility.barcode') }}" class="btn-fafa btn-fafa-secondary">Semua barang</a>@endif
</form>

<p class="text-muted small">
  <span id="pickCount">{{ count($picks) }}</span> barang dipilih untuk sesi ini.
  Kertas 70 mm mengirim label Code 128 ke printer
  <strong>{{ $settings->printer_name ?: 'XP-360B' }}</strong>.
  Nama printer diubah di <a href="{{ route('utility.settings') }}">pengaturan</a>.
</p>

<div class="fafa-card">
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead>
        <tr>
          <th style="width:2.5rem"><input type="checkbox" id="cekall" title="Pilih halaman ini"></th>
          <th>Barcode</th>
          <th>Nama</th>
          <th>Satuan 1</th>
          <th>Satuan 2</th>
          <th>Satuan 3</th>
          <th style="width:6rem">Salinan</th>
        </tr>
      </thead>
      <tbody>
      @forelse($products as $row)
        @php
          $copies = (int) ($picks[$row->sku] ?? 0);
          $kemasan = function ($qty, $jum, $nama, $harga) {
              $jum = (float) $jum;
              if ($jum <= 0) {
                  return '—';
              }
              $jumlah = rtrim(rtrim(number_format(((float) $qty) / $jum, 2, ',', '.'), '0'), ',');
              $satuan = trim((string) $nama);

              return $jumlah.($satuan !== '' ? ' '.$satuan : '').' · Rp '.number_format((float) $harga, 0, ',', '.');
          };
        @endphp
        <tr class="barcode-row" data-sku="{{ $row->sku }}">
          <td><input type="checkbox" class="pick" @checked($copies > 0)></td>
          <td><code>{{ $row->barcode ?: $row->sku }}</code></td>
          <td>{{ $row->name }}</td>
          <td class="small">{{ $kemasan($row->qty, $row->jum_kem1, $row->nm_kem1, $row->hrg_jum1) }}</td>
          <td class="small">{{ $kemasan($row->qty, $row->jum_kem2, $row->nm_kem2, $row->hrg_jum2) }}</td>
          <td class="small">{{ $kemasan($row->qty, $row->jum_kem3, $row->nm_kem3, $row->hrg_jum3) }}</td>
          <td><input type="number" class="form-control form-control-sm copies" min="0" max="50" value="{{ $copies }}"></td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-muted">Tidak ada barang di gerai ini.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-2">{{ $products->links() }}</div>

<form method="POST" action="{{ route('utility.barcode.print') }}" target="_blank" class="fafa-card mt-3" id="printForm">
  @csrf
  <div class="fafa-card-body">
    <div class="form-group mb-2">
      <label class="mr-3"><input type="radio" name="paper" value="A4" checked> A4</label>
      <label class="mr-3"><input type="radio" name="paper" value="58"> 58 mm</label>
      <label class="mr-3"><input type="radio" name="paper" value="80"> 80 mm</label>
      <label><input type="radio" name="paper" value="70"> 70 mm (label 2 kolom)</label>
    </div>
    <input type="hidden" name="mode" id="printMode" value="barcode">
    <button type="submit" class="btn-fafa btn-fafa-primary mr-2" data-mode="barcode">Cetak barcode</button>
    <button type="submit" class="btn-fafa btn-fafa-secondary" data-mode="qr">Cetak QR</button>
  </div>
</form>
<form method="POST" action="{{ route('utility.barcode.reset') }}" class="mt-2">
  @csrf
  <button class="btn-fafa btn-fafa-secondary">Kosongkan pilihan</button>
</form>
@endif
@endsection

@push('scripts')
<script>
$(function () {
  var url = @json(route('utility.barcode.pick'));

  function send(items) {
    return $.ajax({
      url: url,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ items: items })
    }).done(function (data) {
      $('#pickCount').text(data.count);
    });
  }

  function rowItem($row, selected) {
    var $copies = $row.find('.copies');
    var copies = parseInt($copies.val(), 10) || 0;
    if (selected && copies < 1) {
      copies = $row.find('.pick').is(':checked') && !$row.data('bulk') ? 2 : 1;
      $copies.val(copies);
    }
    if (!selected) {
      copies = 0;
      $copies.val(0);
    }
    return { sku: String($row.attr('data-sku')), selected: selected, copies: copies };
  }

  $('.barcode-row .pick').on('change', function () {
    var $row = $(this).closest('.barcode-row');
    $row.data('bulk', false);
    send([rowItem($row, this.checked)]);
  });

  $('.barcode-row .copies').on('change', function () {
    var $row = $(this).closest('.barcode-row');
    var copies = parseInt($(this).val(), 10) || 0;
    var selected = copies > 0;
    $row.find('.pick').prop('checked', selected);
    send([{ sku: String($row.attr('data-sku')), selected: selected, copies: selected ? copies : 0 }]);
  });

  $('#cekall').on('change', function () {
    var on = this.checked;
    var items = [];
    $('.barcode-row').each(function () {
      var $row = $(this);
      $row.data('bulk', true);
      $row.find('.pick').prop('checked', on);
      if (on && (parseInt($row.find('.copies').val(), 10) || 0) < 1) {
        $row.find('.copies').val(1);
      }
      if (!on) {
        $row.find('.copies').val(0);
      }
      items.push(rowItem($row, on));
    });
    if (items.length) {
      send(items);
    }
  });

  $('#printForm button[type=submit]').on('click', function () {
    $('#printMode').val($(this).data('mode'));
  });
});
</script>
@endpush
