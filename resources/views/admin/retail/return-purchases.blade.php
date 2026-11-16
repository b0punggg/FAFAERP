@extends('layouts.app')
@section('title', 'Retur Pembelian')
@section('content')
@include('components.page-header', [
    'title' => 'Retur Pembelian',
    'description' => 'Pilih supplier, cari barang dari nota beli yang masih ada stok lot, lalu simpan — sama seperti sistem lama.',
])

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Retur beli</div>
    <div class="fafa-card-body">
        <form id="returBeliForm" method="POST" action="{{ route('retail.returns.purchases.store') }}">
            @csrf
            <input type="hidden" name="no_fak" id="no_fak">
            <input type="hidden" name="kd_sup" id="kd_sup">
            <div class="retur-toolbar">
                <div>
                    <label for="tgl_retur">Tanggal</label>
                    <input type="date" id="tgl_retur" name="tgl_retur" value="{{ $businessDate }}" class="form-control" required>
                </div>
                <div>
                    <label for="no_retur">No. transaksi</label>
                    <input id="no_retur" name="no_retur" class="form-control" value="{{ $suggestedNo }}" required>
                </div>
                <div>
                    <button type="button" class="btn-fafa btn-fafa-primary" id="btnCariBrg"><i class="fa fa-search"></i> Cari barang</button>
                </div>
                <div>
                    <button type="button" class="btn-fafa btn-fafa-secondary" id="btnCariRetur"><i class="fa fa-list"></i> List nota retur</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4 pos-suggest">
                    <label for="supSearch">Supplier</label>
                    <input id="supSearch" class="form-control" placeholder="Kode atau nama supplier" autocomplete="off">
                    <div id="supResults" class="pos-results"></div>
                    <small class="text-muted" id="supInfo">Belum dipilih</small>
                </div>
                <div class="col-md-3">
                    <label for="no_fak_asal">Muat satu nota beli</label>
                    <div class="d-flex" style="gap:0.4rem">
                        <input id="no_fak_asal" class="form-control" placeholder="No. faktur">
                        <button type="button" class="btn-fafa btn-fafa-secondary" id="btnLoadBeli">Muat</button>
                    </div>
                </div>
                <div class="col-md-5">
                    <label for="ket">Keterangan</label>
                    <input name="ket" id="ket" class="form-control" placeholder="Alasan retur">
                </div>
            </div>
            <p class="text-muted small mt-2 mb-2" id="returHint">Pilih supplier (opsional) lalu Cari barang dari nota beli yang masih ada stok.</p>
            <div class="table-responsive" style="max-height:420px; overflow:auto;">
                <table class="fafa-table mb-0" id="linesTable">
                    <thead>
                        <tr>
                            <th>Nota beli</th>
                            <th>SKU</th>
                            <th>Nama barang</th>
                            <th>Qty</th>
                            <th>Harga beli</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="js-empty"><td colspan="7" class="text-center text-muted py-4">Belum ada barang retur</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="retur-actions">
                <button type="submit" class="btn-fafa btn-fafa-success"><i class="fa fa-save"></i> Simpan</button>
                <button type="button" class="btn-fafa btn-fafa-secondary" id="btnReset"><i class="fa fa-undo"></i> Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card" id="listRetur">
    <div class="fafa-card-header">Data retur yang sudah disimpan</div>
    <div class="fafa-card-body">
        <form method="GET" class="form-inline mb-3">
            <input name="q" class="form-control mr-2" value="{{ $q }}" placeholder="Cari no. retur / nota / supplier…">
            <button class="btn-fafa btn-fafa-secondary">Cari</button>
        </form>
    </div>
    <div class="fafa-card-body" style="padding:0">
        <div class="table-responsive">
            <table class="fafa-table">
                <thead><tr><th>No retur</th><th>Faktur</th><th>Supplier</th><th>Tanggal</th><th>Total</th></tr></thead>
                <tbody>
                @forelse($items as $i)
                    <tr>
                        <td>{{ $i->no_retur }}</td>
                        <td>{{ $i->no_fak }}</td>
                        <td>{{ $i->kd_sup ?: '—' }}</td>
                        <td>{{ $i->tgl_retur }}</td>
                        <td>Rp {{ number_format($i->total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada retur</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-fafa-pagination :paginator="$items" />
</div>

<div class="fafa-modal" id="modalCariBrg" hidden>
    <div class="fafa-modal-box">
        <div class="fafa-modal-head">
            <span>Barang dari nota beli</span>
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm js-close-modal">Tutup</button>
        </div>
        <div class="fafa-modal-body">
            <div class="row">
                <div class="col-md-4 form-group"><label>Nota beli</label><input id="q_fak" class="form-control" placeholder="No. faktur"></div>
                <div class="col-md-5 form-group"><label>Nama barang</label><input id="q_brg" class="form-control" placeholder="Nama / kode"></div>
                <div class="col-md-3 form-group d-flex align-items-end"><button type="button" class="btn-fafa btn-fafa-primary w-100" id="btnSearchLot">Cari</button></div>
            </div>
            <p class="text-muted small" id="lotFilterHint"></p>
            <div class="table-responsive">
                <table class="fafa-table">
                    <thead><tr><th>Nota</th><th>Supplier</th><th>Barang</th><th>Sisa</th><th></th></tr></thead>
                    <tbody id="lotBody"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted" id="lotMeta"></small>
                <div>
                    <button type="button" class="btn-fafa btn-fafa-sm btn-fafa-secondary" id="lotPrev">Sebelumnya</button>
                    <button type="button" class="btn-fafa btn-fafa-sm btn-fafa-secondary" id="lotNext">Berikutnya</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const lotUrl = @json(route('retail.returns.purchases.search-lots'));
const supUrl = @json(route('retail.returns.purchases.search-suppliers'));
const lookupUrl = @json(route('retail.returns.purchases.lookup'));
let lotPage = 1;
let lotLast = 1;
let supTimer = null;

function rupiah(n) {
    return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
}
function emptyRow() {
    return '<tr class="js-empty"><td colspan="7" class="text-center text-muted py-4">Belum ada barang retur</td></tr>';
}
function lineCount() {
    return $('#linesTable tbody tr[data-line]').length;
}
function knownKeys() {
    const set = {};
    $('#linesTable tbody tr[data-line]').each(function() { set[String($(this).data('key'))] = true; });
    return set;
}
function paintHint() {
    const n = lineCount();
    const fak = $('#no_fak').val();
    $('#returHint').text(n ? (n + ' barang · nota ' + (fak || '-')) : 'Pilih supplier (opsional) lalu Cari barang dari nota beli yang masih ada stok.');
}
function subtotalOf(tr) {
    const qty = parseFloat(tr.find('.js-qty').val()) || 0;
    const hrg = parseFloat(tr.find('.js-hrg').val()) || 0;
    tr.find('.js-sub').text(rupiah(qty * hrg));
}
function addLine(it, replaceAll) {
    const sisa = Number(it.qty_sisa != null ? it.qty_sisa : (it.stok_jual != null ? it.stok_jual : it.jml_brg)) || 0;
    if (sisa <= 0) {
        alert('Stok lot ini sudah tidak bisa diretur.');
        return false;
    }
    const key = String(it.no_urut || (it.no_fak + ':' + it.kd_brg));
    if (knownKeys()[key]) {
        alert('Barang ini sudah ada di daftar retur.');
        return false;
    }
    if (!replaceAll && lineCount() && $('#no_fak').val() && it.no_fak && $('#no_fak').val() !== it.no_fak) {
        if (!confirm('Nota berbeda dari yang sudah dipilih. Ganti nota dan mulai daftar baru?')) return false;
        $('#linesTable tbody').html(emptyRow());
    }
    $('#linesTable tbody .js-empty').remove();
    if (!$('#no_fak').val()) $('#no_fak').val(it.no_fak || '');
    if (it.kd_sup && !$('#kd_sup').val()) {
        $('#kd_sup').val(it.kd_sup);
        $('#supSearch').val(it.nm_sup || it.kd_sup);
        $('#supInfo').text((it.nm_sup || it.kd_sup));
    }
    const idx = lineCount();
    const hrg = Number(it.hrg_beli) || 0;
    const tr = $(`<tr data-line data-key="${key}">
        <td>${it.no_fak || ''}
            <input type="hidden" name="lines[${idx}][no_urut]" value="${it.no_urut || ''}">
            <input type="hidden" name="lines[${idx}][no_fak]" value="${it.no_fak || ''}">
            <input type="hidden" name="lines[${idx}][kd_brg]" value="${it.kd_brg || ''}">
            <input type="hidden" name="lines[${idx}][kd_sat]" value="${it.kd_sat || 1}">
        </td>
        <td>${it.kd_brg || ''}</td>
        <td>${it.nm_brg || '—'}</td>
        <td><input type="number" step="0.01" min="0.01" max="${sisa}" name="lines[${idx}][qty]" value="${sisa}" class="form-control form-control-sm js-qty"></td>
        <td><input type="number" step="0.01" min="0" name="lines[${idx}][hrg_beli]" value="${hrg}" class="form-control form-control-sm js-hrg"></td>
        <td class="js-sub">${rupiah(sisa * hrg)}</td>
        <td><button type="button" class="btn-fafa btn-fafa-sm btn-fafa-danger js-del"><i class="fa fa-trash"></i></button></td>
    </tr>`);
    $('#linesTable tbody').append(tr);
    paintHint();
    return true;
}
function loadLots(page) {
    lotPage = page || 1;
    $('#lotFilterHint').text($('#kd_sup').val() ? 'Disaring supplier ' + ($('#supSearch').val() || $('#kd_sup').val()) : 'Semua supplier gerai ini');
    $('#lotBody').html('<tr><td colspan="5" class="text-muted text-center py-3">Memuat…</td></tr>');
    $.get(lotUrl, {
        page: lotPage,
        kd_sup: $('#kd_sup').val(),
        no_fak: $('#q_fak').val(),
        nm_brg: $('#q_brg').val()
    }).done(function(res) {
        lotLast = res.last_page || 1;
        $('#lotMeta').text((res.total || 0) + ' baris · halaman ' + (res.current_page || 1) + '/' + lotLast);
        const tb = $('#lotBody').empty();
        if (!(res.items || []).length) {
            tb.append('<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data (stok lot 0 atau tidak cocok)</td></tr>');
            return;
        }
        (res.items || []).forEach(function(it) {
            const sisa = Number(it.qty_sisa != null ? it.qty_sisa : it.stok_jual) || 0;
            const btn = sisa > 0
                ? '<button type="button" class="btn-fafa btn-fafa-sm btn-fafa-primary js-pick">Pilih</button>'
                : '<span class="text-muted">Habis</span>';
            const tr = $(`<tr>
                <td>${it.no_fak || ''}</td><td>${it.nm_sup || it.kd_sup || '—'}</td>
                <td>${it.nm_brg || it.kd_brg || ''}</td><td>${sisa}</td><td>${btn}</td>
            </tr>`);
            tr.find('.js-pick').on('click', function() {
                if (addLine(it)) $('#modalCariBrg').attr('hidden', true);
            });
            tb.append(tr);
        });
    }).fail(function() {
        $('#lotBody').html('<tr><td colspan="5" class="text-danger text-center py-3">Gagal memuat nota beli</td></tr>');
    });
}
function renderSuppliers(rows) {
    const box = $('#supResults').empty();
    if (!rows.length) {
        box.html('<button type="button" disabled>Tidak ada supplier</button>').show();
        return;
    }
    rows.forEach(function(s) {
        const btn = $('<button type="button"></button>').text((s.kd_sup || '') + ' — ' + (s.nm_sup || ''));
        btn.on('click', function() {
            $('#kd_sup').val(s.kd_sup);
            $('#supSearch').val(s.nm_sup || s.kd_sup);
            const bits = [s.nm_sup || s.kd_sup, s.al_sup, s.nm_sales, s.telp_sup || s.no_telp].filter(Boolean);
            $('#supInfo').text(bits.join(' · '));
            box.hide().empty();
        });
        box.append(btn);
    });
    box.show();
}

$('#supSearch').on('input', function() {
    const kw = $.trim($(this).val() || '');
    if (!kw) {
        $('#kd_sup').val('');
        $('#supInfo').text('Belum dipilih');
        $('#supResults').hide().empty();
        return;
    }
    clearTimeout(supTimer);
    supTimer = setTimeout(function() {
        $.get(supUrl, { keyword: kw }, renderSuppliers);
    }, 250);
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('.pos-suggest').length) $('#supResults').hide();
});
$('#btnCariBrg').on('click', function() {
    $('#modalCariBrg').removeAttr('hidden');
    loadLots(1);
});
$('#btnSearchLot').on('click', function() { loadLots(1); });
$('#q_fak, #q_brg').on('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); loadLots(1); }
});
$('#lotPrev').on('click', function() { if (lotPage > 1) loadLots(lotPage - 1); });
$('#lotNext').on('click', function() { if (lotPage < lotLast) loadLots(lotPage + 1); });
$('.js-close-modal').on('click', function() { $('#modalCariBrg').attr('hidden', true); });
$('#modalCariBrg').on('click', function(e) {
    if (e.target === this) $(this).attr('hidden', true);
});
$('#btnCariRetur').on('click', function() {
    document.getElementById('listRetur').scrollIntoView({ behavior: 'smooth' });
});
$('#btnLoadBeli').on('click', function() {
    const no = $.trim($('#no_fak_asal').val() || '');
    if (!no) { alert('Isi nomor nota beli'); return; }
    $.get(lookupUrl, { no_fak: no }, function(res) {
        if (!(res.items || []).length) {
            alert(res.message || 'Nota tidak ditemukan');
            return;
        }
        $('#linesTable tbody').html(emptyRow());
        $('#no_fak').val(no);
        if (res.kd_sup) {
            $('#kd_sup').val(res.kd_sup);
            $('#supSearch').val(res.nm_sup || res.kd_sup);
            $('#supInfo').text(res.nm_sup || res.kd_sup);
        }
        let added = 0;
        (res.items || []).forEach(function(it) {
            it.no_fak = it.no_fak || no;
            if (addLine(it, true)) added++;
        });
        if (!added) alert('Semua baris nota ini sudah tidak punya sisa stok lot.');
    });
});
$('#linesTable').on('input', '.js-qty, .js-hrg', function() {
    subtotalOf($(this).closest('tr'));
});
$('#linesTable').on('click', '.js-del', function() {
    $(this).closest('tr').remove();
    if (!lineCount()) {
        $('#linesTable tbody').html(emptyRow());
        $('#no_fak').val('');
    }
    paintHint();
});
$('#btnReset').on('click', function() {
    if (lineCount() && !confirm('Kosongkan daftar retur?')) return;
    $('#linesTable tbody').html(emptyRow());
    $('#no_fak, #kd_sup, #no_fak_asal, #ket, #supSearch').val('');
    $('#supInfo').text('Belum dipilih');
    paintHint();
});
$('#returBeliForm').on('submit', function() {
    if (!lineCount()) {
        alert('Pilih barang retur terlebih dahulu.');
        return false;
    }
});
</script>
@endpush
