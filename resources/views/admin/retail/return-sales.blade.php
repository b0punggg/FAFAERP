@extends('layouts.app')
@section('title', 'Retur Penjualan')
@section('content')
@include('components.page-header', [
    'title' => 'Retur Penjualan',
    'description' => 'Cari barang dari nota jual, pilih yang dikembalikan, lalu simpan — sama seperti sistem lama.',
])

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Retur jual</div>
    <div class="fafa-card-body">
        <form id="returJualForm" method="POST" action="{{ route('retail.returns.sales.store') }}">
            @csrf
            <input type="hidden" name="no_fak" id="no_fak">
            <div class="retur-toolbar">
                <div>
                    <label for="tgl_retur">Tanggal</label>
                    <input type="date" id="tgl_retur" name="tgl_retur" value="{{ $businessDate }}" class="form-control" required>
                </div>
                <div>
                    <label for="no_retur">No. retur jual</label>
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
                <div class="col-md-4">
                    <label for="no_fak_asal">Muat satu nota (opsional)</label>
                    <div class="d-flex" style="gap:0.4rem">
                        <input id="no_fak_asal" class="form-control" placeholder="No. nota jual">
                        <button type="button" class="btn-fafa btn-fafa-secondary" id="btnLoadFak">Muat</button>
                    </div>
                </div>
                <div class="col-md-8">
                    <label for="ket">Keterangan</label>
                    <input name="ket" id="ket" class="form-control" placeholder="Alasan retur">
                </div>
            </div>
            <p class="text-muted small mt-2 mb-2" id="returHint">Klik Cari barang, lalu pilih baris penjualan yang dikembalikan.</p>
            <div class="table-responsive" style="max-height:420px; overflow:auto;">
                <table class="fafa-table mb-0" id="linesTable">
                    <thead>
                        <tr>
                            <th>Nota jual</th>
                            <th>SKU</th>
                            <th>Nama barang</th>
                            <th>Qty</th>
                            <th>Harga</th>
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
            <input name="q" class="form-control mr-2" value="{{ $q }}" placeholder="Cari no. retur / nota jual…">
            <button class="btn-fafa btn-fafa-secondary">Cari</button>
        </form>
    </div>
    <div class="fafa-card-body" style="padding:0">
        <div class="table-responsive">
            <table class="fafa-table">
                <thead><tr><th>No retur</th><th>Faktur</th><th>Tanggal</th><th>Total</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $i)
                    <tr>
                        <td>{{ $i->no_retur }}</td>
                        <td>{{ $i->no_fak }}</td>
                        <td>{{ $i->tgl_retur }}</td>
                        <td>Rp {{ number_format($i->total, 0, ',', '.') }}</td>
                        <td><a href="{{ route('retail.returns.sales.print', $i->no_retur) }}" target="_blank" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Cetak</a></td>
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
            <span>List penjualan barang</span>
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm js-close-modal">Tutup</button>
        </div>
        <div class="fafa-modal-body">
            <div class="row">
                <div class="col-md-3 form-group"><label>Tgl jual</label><input type="date" id="q_tgl" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Nota jual</label><input id="q_fak" class="form-control" placeholder="No. nota"></div>
                <div class="col-md-3 form-group"><label>Nama barang</label><input id="q_brg" class="form-control" placeholder="Nama / kode"></div>
                <div class="col-md-3 form-group"><label>Pembeli</label><input id="q_pel" class="form-control" placeholder="Nama pelanggan"></div>
            </div>
            <button type="button" class="btn-fafa btn-fafa-primary mb-3" id="btnSearchSold">Cari</button>
            <div class="table-responsive">
                <table class="fafa-table">
                    <thead><tr><th>Tgl</th><th>Nota</th><th>Barang</th><th>Pembeli</th><th>Sisa</th><th></th></tr></thead>
                    <tbody id="soldBody"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted" id="soldMeta"></small>
                <div>
                    <button type="button" class="btn-fafa btn-fafa-sm btn-fafa-secondary" id="soldPrev">Sebelumnya</button>
                    <button type="button" class="btn-fafa btn-fafa-sm btn-fafa-secondary" id="soldNext">Berikutnya</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const searchUrl = @json(route('retail.returns.sales.search-items'));
const lookupUrl = @json(route('retail.returns.sales.lookup'));
let soldPage = 1;
let soldLast = 1;

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
    $('#linesTable tbody tr[data-line]').each(function() {
        set[String($(this).data('key'))] = true;
    });
    return set;
}
function paintHint() {
    const n = lineCount();
    const fak = $('#no_fak').val();
    $('#returHint').text(n ? (n + ' barang · nota ' + (fak || '-')) : 'Klik Cari barang, lalu pilih baris penjualan yang dikembalikan.');
}
function subtotalOf(tr) {
    const qty = parseFloat(tr.find('.js-qty').val()) || 0;
    const hrg = parseFloat(tr.find('.js-hrg').val()) || 0;
    tr.find('.js-sub').text(rupiah(qty * hrg));
}
function addLine(it, replaceAll) {
    const sisa = Number(it.qty_sisa != null ? it.qty_sisa : it.jml_brg) || 0;
    if (sisa <= 0) {
        alert('Qty nota ini sudah diretur semua.');
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
    const idx = lineCount();
    const qty = sisa;
    const hrg = Number(it.hrg_jual) || 0;
    const tr = $(`<tr data-line data-key="${key}">
        <td>${it.no_fak || ''}
            <input type="hidden" name="lines[${idx}][no_urut]" value="${it.no_urut || ''}">
            <input type="hidden" name="lines[${idx}][no_fak]" value="${it.no_fak || ''}">
            <input type="hidden" name="lines[${idx}][kd_brg]" value="${it.kd_brg || ''}">
            <input type="hidden" name="lines[${idx}][nm_brg]" value="${(it.nm_brg || '').replace(/"/g, '&quot;')}">
            <input type="hidden" name="lines[${idx}][kd_sat]" value="${it.kd_sat || 1}">
        </td>
        <td>${it.kd_brg || ''}</td>
        <td>${it.nm_brg || '—'}</td>
        <td><input type="number" step="0.01" min="0.01" max="${sisa}" name="lines[${idx}][qty]" value="${qty}" class="form-control form-control-sm js-qty"></td>
        <td><input type="number" step="0.01" min="0" name="lines[${idx}][hrg_jual]" value="${hrg}" class="form-control form-control-sm js-hrg"></td>
        <td class="js-sub">${rupiah(qty * hrg)}</td>
        <td><button type="button" class="btn-fafa btn-fafa-sm btn-fafa-danger js-del"><i class="fa fa-trash"></i></button></td>
    </tr>`);
    $('#linesTable tbody').append(tr);
    paintHint();
    return true;
}
function loadSold(page) {
    soldPage = page || 1;
    $('#soldBody').html('<tr><td colspan="6" class="text-muted text-center py-3">Memuat…</td></tr>');
    $.get(searchUrl, {
        page: soldPage,
        tgl: $('#q_tgl').val(),
        no_fak: $('#q_fak').val(),
        nm_brg: $('#q_brg').val(),
        nm_pel: $('#q_pel').val()
    }).done(function(res) {
        soldLast = res.last_page || 1;
        $('#soldMeta').text((res.total || 0) + ' baris · halaman ' + (res.current_page || 1) + '/' + soldLast);
        const tb = $('#soldBody').empty();
        if (!(res.items || []).length) {
            tb.append('<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data</td></tr>');
            return;
        }
        (res.items || []).forEach(function(it) {
            const sisa = Number(it.qty_sisa != null ? it.qty_sisa : it.jml_brg) || 0;
            const tgl = it.tgl_jual ? String(it.tgl_jual).slice(0, 10) : '';
            const btn = sisa > 0
                ? `<button type="button" class="btn-fafa btn-fafa-sm btn-fafa-primary js-pick">Pilih</button>`
                : '<span class="text-muted">Habis</span>';
            const tr = $(`<tr>
                <td>${tgl}</td><td>${it.no_fak || ''}</td><td>${it.nm_brg || it.kd_brg || ''}</td>
                <td>${it.nm_pel || '—'}</td><td>${sisa}</td><td>${btn}</td>
            </tr>`);
            tr.find('.js-pick').on('click', function() {
                if (addLine(it)) {
                    $('#modalCariBrg').attr('hidden', true);
                }
            });
            tb.append(tr);
        });
    }).fail(function() {
        $('#soldBody').html('<tr><td colspan="6" class="text-danger text-center py-3">Gagal memuat list penjualan</td></tr>');
    });
}

$('#btnCariBrg').on('click', function() {
    $('#modalCariBrg').removeAttr('hidden');
    loadSold(1);
});
$('#btnSearchSold').on('click', function() { loadSold(1); });
$('#q_fak, #q_brg, #q_pel').on('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); loadSold(1); }
});
$('#soldPrev').on('click', function() { if (soldPage > 1) loadSold(soldPage - 1); });
$('#soldNext').on('click', function() { if (soldPage < soldLast) loadSold(soldPage + 1); });
$('.js-close-modal').on('click', function() { $('#modalCariBrg').attr('hidden', true); });
$('#modalCariBrg').on('click', function(e) {
    if (e.target === this) $(this).attr('hidden', true);
});
$('#btnCariRetur').on('click', function() {
    document.getElementById('listRetur').scrollIntoView({ behavior: 'smooth' });
});
$('#btnLoadFak').on('click', function() {
    const no = $.trim($('#no_fak_asal').val() || '');
    if (!no) { alert('Isi nomor nota jual'); return; }
    $.get(lookupUrl, { no_fak: no }, function(res) {
        if (!(res.items || []).length) {
            alert(res.message || 'Nota tidak ditemukan');
            return;
        }
        $('#linesTable tbody').html(emptyRow());
        $('#no_fak').val(no);
        let added = 0;
        (res.items || []).forEach(function(it) {
            it.no_fak = it.no_fak || no;
            if (addLine(it, true)) added++;
        });
        if (!added) alert('Semua baris nota ini sudah diretur.');
    });
});
$('#linesTable').on('input', '.js-qty, .js-hrg', function() {
    subtotalOf($(this).closest('tr'));
});
$('#linesTable').on('click', '.js-del', function() {
    $(this).closest('tr').remove();
    if (!lineCount()) $('#linesTable tbody').html(emptyRow());
    if (!lineCount()) $('#no_fak').val('');
    paintHint();
});
$('#btnReset').on('click', function() {
    if (lineCount() && !confirm('Kosongkan daftar retur?')) return;
    $('#linesTable tbody').html(emptyRow());
    $('#no_fak').val('');
    $('#no_fak_asal').val('');
    $('#ket').val('');
    paintHint();
});
$('#returJualForm').on('submit', function() {
    if (!lineCount()) {
        alert('Pilih barang retur terlebih dahulu.');
        return false;
    }
});
</script>
@endpush
