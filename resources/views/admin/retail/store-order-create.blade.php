@extends('layouts.app')

@section('title', 'Pemesanan')

@push('styles')
<script>document.documentElement.classList.add('pos-lock');</script>
@endpush

@section('content')
<div class="pos-shell">
    <div class="pos-top">
        <strong>Pemesanan barang</strong>
        @if(session('nm_toko'))
            <span class="pos-meta"><i class="fa fa-store"></i> {{ session('nm_toko') }}</span>
        @endif
        <a href="{{ route('retail.orders.index') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm pos-exit">Daftar PO</a>
    </div>
    <div class="pos-flash" id="poNotice" hidden></div>

    <div class="po-head">
        <label>Tgl. PO</label>
        <input type="date" id="tgl_po" class="form-control" value="{{ date('Y-m-d') }}">
        <label>No. PO</label>
        <div class="po-po">
            <input type="text" id="no_po" class="form-control" value="{{ $suggestedNo }}">
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-cari-po" title="Cari PO"><i class="fa fa-caret-down"></i></button>
        </div>
        <label>Supplier</label>
        <div class="pos-suggest po-sup">
            <input type="hidden" id="kd_sup">
            <input type="text" id="nm_sup" class="form-control" placeholder="Ketik supplier" autocomplete="off">
            <div id="supResults" class="pos-results"></div>
        </div>
        <label>Ket</label>
        <input type="text" id="ket" class="form-control">
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-baru">PO baru</button>
        <a class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-excel" target="_blank">Excel</a>
        <a class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-pdf" target="_blank">PDF</a>
    </div>

    <div class="po-tools">
        <label for="filter_stok">Filter stok</label>
        <select id="filter_stok" class="form-control">
            <option value="semua">Semua barang</option>
            <option value="ada_stok">Hanya ada stok</option>
            <option value="stok_nol">Hanya stok 0</option>
        </select>
        <input id="cari_nmbrg" class="form-control" placeholder="Cari nama atau kode">
        <button type="button" class="btn-fafa btn-fafa-primary btn-fafa-sm" id="btn-cari-brg"><i class="fa fa-search"></i></button>
        <button type="button" class="btn-fafa btn-fafa-success btn-fafa-sm" id="btn-masuk">Masukkan ke PO</button>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-baru-brg">Barang baru</button>
        <span class="po-page">
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="page-prev">Sebelumnya</button>
            <span id="catalogPage"></span>
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="page-next">Berikutnya</button>
        </span>
    </div>

    <div class="po-scroll" id="catalogWrap">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="cek-all"></th>
                    <th>Nama barang</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <th class="sales-money">Harga beli</th>
                    <th>Qty pesan</th>
                </tr>
            </thead>
            <tbody id="catalogBody"></tbody>
        </table>
    </div>

    <div class="po-caption">Isi PO</div>
    <div class="po-scroll po-lines">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Nama barang</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th class="sales-money">Harga beli</th>
                    <th class="sales-money">Jumlah</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="poBody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="sales-money"><strong>Total</strong></td>
                    <td class="sales-money" id="poTotal"><strong>Rp 0</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="po-find">
        <input id="cari_po_line" class="form-control" placeholder="Cari nama di PO">
    </div>
</div>

<div class="pos-overlay" id="poPanel" hidden>
    <div class="pos-panel">
        <h2>Cari No. PO</h2>
        <input type="text" id="cari_nopo" class="form-control" placeholder="Ketik nomor PO atau supplier">
        <ul class="mb-0 mt-3" id="poList">
            @foreach($recent as $row)
                <li class="mb-2"><button type="button" class="btn btn-link p-0 po-pick" data-id="{{ $row->id }}">{{ $row->no_po }}</button>
                    <span class="text-muted">{{ $row->nm_sup ?: $row->kd_sup ?: 'Tanpa supplier' }} · Rp {{ number_format($row->tot_pesan, 0, ',', '.') }}</span></li>
            @endforeach
        </ul>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm mt-3" id="poClose">Tutup</button>
    </div>
</div>

<div class="pos-overlay" id="newPanel" hidden>
    <div class="pos-panel">
        <h2>Barang baru ke PO</h2>
        <label>Nama barang</label>
        <input type="text" id="baru_nm" class="form-control">
        <label>Satuan</label>
        <select id="baru_sat" class="form-control">
            <option value="">Pilih satuan</option>
            @foreach($units as $unit)
                <option value="{{ $unit->no_urut }}">{{ $unit->nm_sat1 }}</option>
            @endforeach
        </select>
        <label>Qty</label>
        <input type="number" id="baru_qty" class="form-control" min="0" step="any" value="1">
        <label>Harga beli</label>
        <input type="number" id="baru_hrg" class="form-control" min="0" step="1" value="0">
        <div class="pos-actions">
            <button type="button" class="btn-fafa btn-fafa-success" id="baru-simpan">Simpan ke PO</button>
            <button type="button" class="btn-fafa btn-fafa-secondary" id="baru-tutup">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let catalogPage = 1;
let orderId = null;

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, function(ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}
function rupiah(n) {
    return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
}
function notice(text) {
    const box = document.getElementById('poNotice');
    box.hidden = !text;
    box.textContent = text || '';
}
function headerPayload() {
    return {
        no_po: $('#no_po').val().trim(),
        tgl_po: $('#tgl_po').val(),
        kd_sup: $('#kd_sup').val(),
        nm_sup: $('#nm_sup').val(),
        ket: $('#ket').val()
    };
}
function needSupplier() {
    if ($('#kd_sup').val()) return true;
    notice('Pilih supplier dulu.');
    $('#nm_sup').focus();
    return false;
}
function exportLinks() {
    const no = encodeURIComponent($('#no_po').val().trim());
    $('#btn-excel').attr('href', @json(route('retail.orders.excel', ['noPo' => '__PO__'])).replace('__PO__', no));
    $('#btn-pdf').attr('href', @json(route('retail.orders.print', ['noPo' => '__PO__'])).replace('__PO__', no));
}
function paintLines(lines, total) {
    let html = '';
    (lines || []).forEach(function(line) {
        html += `<tr data-name="${esc(line.nm_brg).toLowerCase()}">
            <td>${esc(line.nm_brg || line.kd_brg)}</td>
            <td>${esc(line.qty_pesan)}</td>
            <td>${esc(line.nm_sat || 'pcs')}</td>
            <td class="sales-money">${rupiah(line.hrg_beli)}</td>
            <td class="sales-money">${rupiah(line.jumlah)}</td>
            <td><button type="button" class="btn btn-sm btn-danger po-del" data-id="${line.id}"><i class="fa fa-trash"></i></button></td>
        </tr>`;
    });
    $('#poBody').html(html || '<tr><td colspan="6">Belum ada barang di PO.</td></tr>');
    $('#poTotal').html('<strong>' + rupiah(total || 0) + '</strong>');
}
function paintCatalog(payload) {
    let html = '';
    (payload.items || []).forEach(function(row) {
        const checked = row.qty_pesan !== null && row.qty_pesan !== undefined;
        html += `<tr>
            <td><input type="checkbox" class="cek-brg" value="${esc(row.kd_brg)}" data-nm="${esc(row.nm_brg)}" data-sat="${row.kd_sat}" data-nmsat="${esc(row.nm_sat)}" data-hrg="${row.hrg_beli}" ${checked ? 'checked' : ''}></td>
            <td>${esc(row.nm_brg)}</td>
            <td>${Number(row.qty).toLocaleString('id-ID')} ${esc(row.nm_sat)}</td>
            <td>${esc(row.nm_sat)}</td>
            <td class="sales-money">${rupiah(row.hrg_beli)}</td>
            <td><input type="number" min="0" step="any" class="form-control form-control-sm qty-pesan" value="${checked ? row.qty_pesan : ''}"></td>
        </tr>`;
    });
    $('#catalogBody').html(html || '<tr><td colspan="6">Tidak ada barang.</td></tr>');
    $('#catalogPage').text('Halaman ' + payload.page + ' / ' + payload.pages);
    $('#catalogWrap').data('pages', payload.pages);
}
function loadCatalog(page) {
    catalogPage = page || 1;
    $.get('{{ route('retail.orders.catalog') }}', {
        q: $('#cari_nmbrg').val(),
        filter: $('#filter_stok').val(),
        no_po: $('#no_po').val(),
        page: catalogPage
    }, paintCatalog);
}
function applySheet(payload) {
    if (!payload || !payload.order) return;
    orderId = payload.order.id;
    $('#no_po').val(payload.order.no_po);
    $('#tgl_po').val(String(payload.order.tgl_po).slice(0, 10));
    $('#kd_sup').val(payload.order.kd_sup || '');
    $('#nm_sup').val(payload.order.nm_sup || payload.order.kd_sup || '');
    $('#ket').val(payload.order.ket || '');
    paintLines(payload.lines, payload.order.tot_pesan);
    exportLinks();
    loadCatalog(1);
}
function saveChecked() {
    if (!needSupplier()) return;
    const items = [];
    $('.cek-brg:checked').each(function() {
        const row = $(this).closest('tr');
        items.push({
            kd_brg: $(this).val(),
            nm_brg: $(this).data('nm'),
            kd_sat: $(this).data('sat'),
            nm_sat: $(this).data('nmsat'),
            hrg_beli: $(this).data('hrg'),
            qty: row.find('.qty-pesan').val()
        });
    });
    if (!items.length) {
        notice('Centang barang yang akan dipesan.');
        return;
    }
    $.post('{{ route('retail.orders.lines') }}', Object.assign(headerPayload(), { items: items }))
        .done(function(res) {
            orderId = res.order_id;
            paintLines(res.lines, res.total);
            notice('Barang masuk ke PO.');
            loadCatalog(catalogPage);
            exportLinks();
        })
        .fail(function(xhr) {
            notice((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan PO.');
        });
}

$('#btn-cari-brg, #filter_stok').on('click change', function() { loadCatalog(1); });
$('#cari_nmbrg').on('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); loadCatalog(1); } });
$('#btn-masuk').on('click', saveChecked);
$('#cek-all').on('change', function() {
    const on = this.checked;
    $('.cek-brg').each(function() {
        $(this).prop('checked', on);
        const qty = $(this).closest('tr').find('.qty-pesan');
        if (on && qty.val() === '') qty.val(1);
    });
});
$('#catalogBody').on('change', '.cek-brg', function() {
    const qty = $(this).closest('tr').find('.qty-pesan');
    if (this.checked && qty.val() === '') qty.val(1);
});
$('#catalogBody').on('focus', '.qty-pesan', function() {
    $(this).closest('tr').find('.cek-brg').prop('checked', true);
});
$('#poBody').on('click', '.po-del', function() {
    if (!confirm('Hapus item ini?')) return;
    $.ajax({ url: @json(route('retail.orders.lines.destroy', ['lineId' => '__ID__'])).replace('__ID__', $(this).data('id')), method: 'DELETE' })
        .done(function(res) { paintLines(res.lines, res.total); loadCatalog(catalogPage); });
});
$('#cari_po_line').on('input', function() {
    const q = $(this).val().toLowerCase();
    $('#poBody tr').each(function() {
        const name = String($(this).data('name') || '');
        $(this).toggle(!q || name.indexOf(q) >= 0);
    });
});
$('#btn-cari-po').on('click', function() { $('#poPanel').removeAttr('hidden'); $('#cari_nopo').focus(); });
$('#poClose, #baru-tutup').on('click', function() { $('#poPanel, #newPanel').attr('hidden', true); });
$('#cari_nopo').on('input', function() {
    $.get('{{ route('retail.orders.search') }}', { q: $(this).val() }, function(rows) {
        let html = '';
        (rows || []).forEach(function(row) {
            html += `<li class="mb-2"><button type="button" class="btn btn-link p-0 po-pick" data-id="${row.id}">${row.no_po}</button>
                <span class="text-muted">${row.nm_sup || row.kd_sup || 'Tanpa supplier'} · ${rupiah(row.tot_pesan)}</span></li>`;
        });
        $('#poList').html(html || '<li>Tidak ada PO.</li>');
    });
});
$('#poList').on('click', '.po-pick', function() {
    $.get(@json(route('retail.orders.sheet', ['id' => '__ID__'])).replace('__ID__', $(this).data('id')), function(payload) {
        applySheet(payload);
        $('#poPanel').attr('hidden', true);
        notice('');
    });
});
$('#btn-baru').on('click', function() { window.location = '{{ route('retail.orders.create') }}'; });
$('#btn-baru-brg').on('click', function() {
    if (!needSupplier()) return;
    $('#newPanel').removeAttr('hidden');
    $('#baru_nm').focus();
});
$('#baru-simpan').on('click', function() {
    const sat = $('#baru_sat option:selected');
    if (!$('#baru_nm').val().trim() || !sat.val()) {
        notice('Nama barang dan satuan wajib diisi.');
        return;
    }
    $.post('{{ route('retail.orders.new-item') }}', Object.assign(headerPayload(), {
        nm_brg: $('#baru_nm').val(),
        kd_sat: sat.val(),
        nm_sat: sat.text(),
        qty: $('#baru_qty').val(),
        hrg_beli: $('#baru_hrg').val()
    })).done(function(res) {
        orderId = res.order_id;
        paintLines(res.lines, res.total);
        $('#newPanel').attr('hidden', true);
        $('#baru_nm').val('');
        notice('Barang baru masuk ke PO.');
        loadCatalog(1);
        exportLinks();
    }).fail(function(xhr) {
        notice((xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'Gagal membuat barang.');
    });
});
$('#nm_sup').on('input', function() {
    $('#kd_sup').val('');
    const kw = $(this).val().trim();
    if (kw.length < 2) { $('#supResults').hide(); return; }
    $.post('{{ route('purchases.search-supplier') }}', { keyword: kw }, function(rows) {
        let html = '';
        (rows || []).forEach(function(row, i) {
            html += `<button type="button" data-i="${i}">${row.kd_sup} — ${row.nm_sup}</button>`;
        });
        $('#supResults').html(html || '<div class="p-2 text-muted">Tidak ditemukan</div>').show().data('rows', rows || []);
    });
});
$('#supResults').on('click', 'button', function() {
    const row = ($('#supResults').data('rows') || [])[$(this).data('i')];
    if (!row) return;
    $('#kd_sup').val(row.kd_sup);
    $('#nm_sup').val(row.nm_sup);
    $('#supResults').hide();
});
$('#page-prev').on('click', function() { if (catalogPage > 1) loadCatalog(catalogPage - 1); });
$('#page-next').on('click', function() {
    const pages = Number($('#catalogWrap').data('pages') || 1);
    if (catalogPage < pages) loadCatalog(catalogPage + 1);
});
exportLinks();
loadCatalog(1);
</script>
@endpush
