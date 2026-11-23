@extends('layouts.app')

@section('title', 'Stok Opname')

@push('styles')
<script>document.documentElement.classList.add('pos-lock');</script>
@endpush

@section('content')
<div class="pos-shell">
    <div class="pos-top">
        <strong>Stok Opname</strong>
        @if(session('nm_toko'))
            <span class="pos-meta"><i class="fa fa-store"></i> {{ session('nm_toko') }}</span>
        @endif
        <a href="{{ route('retail.opname.history') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm pos-exit">Riwayat</a>
    </div>
    <div class="pos-flash" id="opNotice" hidden></div>
    @if(!empty($opnameHint))
        <p class="text-muted" style="margin:8px 16px 0;">{{ $opnameHint }}</p>
    @endif
    @if(!empty($isPusat))
        <p class="text-danger" style="margin:8px 16px 0;">Gunakan menu Opname Pusat di inventory untuk gudang ini.</p>
    @endif

    <div class="op-progress">
        <label>Dari</label>
        <input type="date" id="tgl_awal" class="form-control" value="{{ $from }}">
        <label>s/d</label>
        <input type="date" id="tgl_akhir" class="form-control" value="{{ $to }}">
        <button type="button" class="btn-fafa btn-fafa-primary btn-fafa-sm" id="btn-progress">Tampilkan</button>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-bulan">Bulan ini</button>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="btn-hari">Hari ini</button>
        <div class="op-bar-wrap">
            <div id="opText">Memuat progress...</div>
            <div class="op-bar"><div class="op-bar-fill" id="opFill">0%</div></div>
        </div>
    </div>

    <div class="po-tools">
        <label for="filter_stok">Filter stok</label>
        <select id="filter_stok" class="form-control">
            <option value="semua">Semua barang</option>
            <option value="ada_stok">Hanya ada stok</option>
            <option value="stok_nol">Hanya stok 0</option>
        </select>
        <label for="satuan_input">Satuan input</label>
        <select id="satuan_input" class="form-control">
            <option value="kecil">Satuan terkecil</option>
            <option value="pack">Pack / satuan besar</option>
        </select>
        <input id="q" class="form-control" placeholder="Cari nama atau kode">
        <input id="barcode" class="form-control" placeholder="Barcode">
        <button type="button" class="btn-fafa btn-fafa-primary btn-fafa-sm" id="btn-cari"><i class="fa fa-search"></i></button>
        <span class="po-page">
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="page-prev">Sebelumnya</button>
            <span id="pageLabel"></span>
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="page-next">Berikutnya</button>
        </span>
    </div>

    <div class="po-scroll" id="listWrap">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Barcode</th>
                    <th>Nama barang</th>
                    <th>Stok</th>
                    <th>Adjust</th>
                    <th>Keterangan</th>
                    <th>Cek terakhir</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="opBody"></tbody>
        </table>
    </div>

    <div class="pos-overlay" id="adjustPanel" hidden>
        <div class="pos-panel">
            <h2>Proses penyesuaian</h2>
            <p id="adjName"></p>
            <label>Stok awal</label>
            <input id="stok_awal" class="form-control" readonly>
            <label>Penyesuaian</label>
            <input id="stok_akhir" class="form-control" readonly>
            <div id="rowSmall">
                <label>Setara terkecil</label>
                <input id="stok_kecil" class="form-control" readonly>
            </div>
            <label for="ket_ad">Keterangan</label>
            <textarea id="ket_ad" class="form-control" rows="4"></textarea>
            <div class="pos-actions">
                <button type="button" class="btn-fafa btn-fafa-success" id="adj-save">Lanjutkan</button>
                <button type="button" class="btn-fafa btn-fafa-secondary" id="adj-close">Batal</button>
            </div>
        </div>
    </div>

    <div class="pos-overlay" id="notePanel" hidden>
        <div class="pos-panel">
            <h2>Catatan penyesuaian</h2>
            <ul class="mb-0" id="noteList"></ul>
            <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm mt-3" id="note-close">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let page = 1;
let pages = 1;
let pending = null;

function notice(text) {
    const box = document.getElementById('opNotice');
    box.hidden = !text;
    box.textContent = text || '';
}
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, function(ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}
function qtyText(n) {
    return (Number(n) || 0).toLocaleString('id-ID');
}
function mode() {
    return $('#satuan_input').val() === 'pack' ? 'pack' : 'kecil';
}
function loadProgress() {
    $.get('{{ route('retail.opname.progress') }}', {
        from: $('#tgl_awal').val(),
        to: $('#tgl_akhir').val(),
        filter: $('#filter_stok').val()
    }).done(function(res) {
        const pct = Math.min(100, Number(res.persen) || 0);
        $('#opText').text(pct + '% — ' + res.sudah + ' dari ' + res.total + ' barang sudah disesuaikan. Belum: ' + res.belum);
        $('#opFill').css('width', Math.max(pct, 2) + '%').text(pct + '%');
    }).fail(function(xhr) {
        notice((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat progress.');
    });
}
function paint(payload) {
    pages = payload.pages || 1;
    page = payload.page || 1;
    $('#pageLabel').text('Halaman ' + page + ' / ' + pages);
    let html = '';
    (payload.items || []).forEach(function(row) {
        const pack = row.has_pack ? '<div class="sales-sub">' + qtyText(row.qty_pack) + ' ' + esc(row.nm_pack) + '</div>' : '';
        const label = mode() === 'pack' && row.has_pack ? row.nm_pack : row.nm_sat;
        html += `<tr>
            <td>${esc(row.kd_bar || '—')}</td>
            <td>${esc(row.nm_brg)}<div class="sales-sub">${esc(row.kd_brg)}</div></td>
            <td>${qtyText(row.qty)} ${esc(row.nm_sat)}${pack}</td>
            <td><input type="number" min="0" step="any" class="form-control form-control-sm adj-input"
                data-kd="${esc(row.kd_brg)}" data-nm="${esc(row.nm_brg)}" data-qty="${row.qty}"
                data-sat="${esc(row.nm_sat)}" data-pack="${row.has_pack ? 1 : 0}" data-factor="${row.factor}"
                data-npack="${esc(row.nm_pack)}" placeholder="${esc(label)}"></td>
            <td>${esc(row.last_ket || '—')}</td>
            <td>${row.last_date ? esc(String(row.last_date).slice(0, 10)) : '—'}</td>
            <td><button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm btn-note" data-kd="${esc(row.kd_brg)}">Catatan${row.note_count ? ' (' + row.note_count + ')' : ''}</button></td>
        </tr>`;
    });
    $('#opBody').html(html || '<tr><td colspan="7">Tidak ada barang.</td></tr>');
}
function loadList(next) {
    page = next || 1;
    $.get('{{ route('retail.opname.catalog') }}', {
        q: $('#q').val(),
        barcode: $('#barcode').val(),
        filter: $('#filter_stok').val(),
        page: page
    }, paint);
}
function openAdjust(input) {
    const qty = input.value;
    if (qty === '') return;
    const n = Number(qty);
    if (isNaN(n) || n < 0) {
        notice('Nilai penyesuaian tidak valid.');
        return;
    }
    const hasPack = input.dataset.pack === '1';
    const factor = Number(input.dataset.factor) || 1;
    const usePack = mode() === 'pack' && hasPack;
    const base = usePack ? n * factor : n;
    pending = { kd_brg: input.dataset.kd, qty: n, mode: usePack ? 'pack' : 'kecil' };
    $('#adjName').text(input.dataset.nm + ' (' + input.dataset.kd + ')');
    $('#stok_awal').val(qtyText(input.dataset.qty) + ' ' + input.dataset.sat);
    $('#stok_akhir').val(qtyText(n) + ' ' + (usePack ? input.dataset.npack : input.dataset.sat));
    if (usePack) {
        $('#rowSmall').show();
        $('#stok_kecil').val(qtyText(base) + ' ' + input.dataset.sat);
    } else {
        $('#rowSmall').hide();
    }
    $('#ket_ad').val('');
    $('#adjustPanel').removeAttr('hidden');
    $('#ket_ad').focus();
}

$('#btn-progress, #filter_stok').on('click change', function() { loadProgress(); if (this.id === 'filter_stok') loadList(1); });
$('#satuan_input').on('change', function() { loadList(page); });
$('#btn-cari').on('click', function() { loadList(1); });
$('#q, #barcode').on('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); loadList(1); } });
$('#page-prev').on('click', function() { if (page > 1) loadList(page - 1); });
$('#page-next').on('click', function() { if (page < pages) loadList(page + 1); });
$('#btn-bulan').on('click', function() {
    const now = new Date();
    const pad = function(n) { return String(n).padStart(2, '0'); };
    $('#tgl_awal').val(now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-01');
    $('#tgl_akhir').val(now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()));
    loadProgress();
});
$('#btn-hari').on('click', function() {
    const now = new Date();
    const pad = function(n) { return String(n).padStart(2, '0'); };
    const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    $('#tgl_awal, #tgl_akhir').val(today);
    loadProgress();
});
$('#opBody').on('keydown', '.adj-input', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); openAdjust(this); }
});
$('#adj-close, #note-close').on('click', function() {
    $('#adjustPanel, #notePanel').attr('hidden', true);
    pending = null;
});
$('#adj-save').on('click', function() {
    if (!pending) return;
    $.post('{{ route('retail.opname.adjust') }}', {
        kd_brg: pending.kd_brg,
        qty: pending.qty,
        mode: pending.mode,
        ket: $('#ket_ad').val()
    }).done(function() {
        $('#adjustPanel').attr('hidden', true);
        pending = null;
        notice('Stok disesuaikan.');
        loadList(page);
        loadProgress();
    }).fail(function(xhr) {
        notice((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyesuaikan stok.');
    });
});
$('#opBody').on('click', '.btn-note', function() {
    const kd = $(this).data('kd');
    $.get('{{ route('retail.opname.notes') }}', { kd_brg: kd }, function(rows) {
        let html = '';
        (rows || []).forEach(function(row) {
            html += `<li class="mb-2">
                <div>${esc(String(row.tgl_opname).slice(0, 10))} · ${qtyText(row.qty_system)} → ${qtyText(row.qty_physical)}</div>
                <textarea class="form-control note-edit" data-id="${row.id}" rows="2">${esc(row.ket || '')}</textarea>
                <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm mt-1 note-save" data-id="${row.id}">Simpan keterangan</button>
            </li>`;
        });
        $('#noteList').html(html || '<li>Belum ada catatan.</li>');
        $('#notePanel').removeAttr('hidden');
    });
});
$('#noteList').on('click', '.note-save', function() {
    const id = $(this).data('id');
    const ket = $(this).closest('li').find('.note-edit').val();
    $.post(@json(route('retail.opname.notes.update', ['lineId' => '__ID__'])).replace('__ID__', id), { ket: ket }).done(function() {
        notice('Keterangan diperbarui.');
        loadList(page);
    });
});
loadList(1);
loadProgress();
</script>
@endpush
