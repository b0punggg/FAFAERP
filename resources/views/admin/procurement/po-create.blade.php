@extends('layouts.app')
@section('title', 'Buat pesanan pusat')
@section('content')
@include('components.page-header', [
    'title' => 'Buat pesanan gudang pusat',
    'description' => $warehouse ? 'Gudang: '.$warehouse->code : 'Gudang pusat belum tersedia.',
])
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('procurement.po.store') }}" class="fafa-card">
    @csrf
    <input type="hidden" name="company_id" value="{{ $companyId }}">
    <input type="hidden" name="warehouse_id" value="{{ $warehouse?->id }}">
    <div class="fafa-card-body">
        <div class="erp-form mb-3">
            <div class="span-2">
                <label for="find">Cari barang</label>
                <input id="find" class="form-control" placeholder="Nama atau kode barang">
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="button" class="btn-fafa btn-fafa-secondary" id="btn-find">Cari</button>
            </div>
        </div>
        <div id="found" class="mb-3"></div>
        <table class="fafa-table">
            <thead><tr><th>Barang</th><th>Qty</th><th>Harga</th><th></th></tr></thead>
            <tbody id="poLines"></tbody>
        </table>
    </div>
    <div class="fafa-card-body" style="border-top:1px solid var(--fafa-border)">
        <button class="btn-fafa btn-fafa-primary" @disabled(! $warehouse)>Simpan draf</button>
        <a href="{{ route('procurement.po.index') }}" class="btn-fafa btn-fafa-secondary">Kembali</a>
    </div>
</form>
@endsection
@push('scripts')
<script>
let n = 0;
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, function(ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}
$('#btn-find').on('click', function() {
    $.get('{{ route('procurement.po.search') }}', { q: $('#find').val() }, function(rows) {
        let html = '';
        (rows || []).forEach(function(row) {
            html += '<button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm mr-1 mb-1 pick" data-id="' + row.id + '" data-name="' + esc(row.name) + '" data-sku="' + esc(row.sku) + '">' + esc(row.name) + '</button>';
        });
        $('#found').html(html || '<span class="text-muted">Barang tidak ketemu.</span>');
    });
});
$('#found').on('click', '.pick', function() {
    const i = n++;
    $('#poLines').append('<tr><td>' + $(this).data('name') + '<div class="sales-sub">' + $(this).data('sku') + '</div><input type="hidden" name="lines[' + i + '][product_id]" value="' + $(this).data('id') + '"></td><td><input name="lines[' + i + '][qty]" type="number" min="0.01" step="0.01" value="1" class="form-control" required></td><td><input name="lines[' + i + '][unit_cost]" type="number" min="0" step="0.01" value="0" class="form-control"></td><td><button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm drop">Hapus</button></td></tr>');
});
$('#poLines').on('click', '.drop', function() { $(this).closest('tr').remove(); });
</script>
@endpush
