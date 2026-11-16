@extends('layouts.app')

@section('title', $purchase ? 'Ubah pembelian' : 'Pembelian')

@push('styles')
<script>document.documentElement.classList.add('pos-lock');</script>
@endpush

@section('content')
<form id="purchaseForm" action="{{ $purchase ? route('purchases.update', $purchase->no_urut) : route('purchases.store') }}" method="POST">
    @csrf
    @if($purchase) @method('PUT') @endif
    <div class="pos-shell">
        <div class="pos-top">
            <strong>{{ $purchase ? 'Ubah pembelian' : 'Pembelian' }}</strong>
            @if(session('nm_toko'))
                <span class="pos-meta"><i class="fa fa-store"></i> {{ session('nm_toko') }}</span>
            @endif
            <a href="{{ route('purchases.index') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm pos-exit">Keluar</a>
        </div>
        @if(session('error'))
            <div class="pos-flash">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="pos-flash">{{ $errors->first() }}</div>
        @endif
        <div class="pos-flash" id="posNotice" hidden></div>

        <div class="pos-body">
            <aside class="pos-side">
                <label for="tgl_fak">Tanggal faktur</label>
                <input type="date" class="form-control" id="tgl_fak" name="tgl_fak" value="{{ old('tgl_fak', optional($purchase)->tgl_fak ?? ($resume->tgl_fak ?? $businessDate)) }}" required>
                <label for="no_fak">No. faktur</label>
                <input type="text" class="form-control" id="no_fak" name="no_fak" value="{{ old('no_fak', optional($purchase)->no_fak ?? $suggestedNo) }}" placeholder="WARDAH 09/10/2026" required @unless($purchase) autofocus @endunless>
                <label for="supSearch">Supplier</label>
                <div class="pos-suggest">
                    <input type="text" class="form-control" id="supSearch" placeholder="Nama atau kode" autocomplete="off">
                    <div id="supResults" class="pos-results"></div>
                </div>
                <input type="hidden" name="kd_sup" id="kd_sup" value="{{ old('kd_sup', optional($purchase)->kd_sup ?? ($resume->kd_sup ?? '')) }}">
                <input type="text" class="form-control" name="nm_sup" id="nm_sup" value="{{ old('nm_sup', optional($purchase)->nm_sup ?? ($resume->nm_sup ?? '')) }}" placeholder="Nama supplier" style="margin-top:0.3rem">
                <label for="ket">Keterangan</label>
                <input type="text" class="form-control" id="ket" name="ket" value="{{ old('ket', optional($purchase)->ket ?? '-') }}">
                <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="tmb-nota" style="margin-top:0.8rem">Cari nota</button>
                <button type="button" class="btn-fafa btn-fafa-warning btn-fafa-sm" id="tmb-hutang" style="margin-top:0.4rem">Nota belum bayar</button>
            </aside>

            <section class="pos-main">
                <div class="pos-total" id="grandTotal">Rp 0</div>
                <div class="pos-subline" id="totalHint">Belum ada barang</div>
                <input type="hidden" id="entryKd">
                <input type="hidden" id="entryName">
                <input type="hidden" id="entryBar">
                <div class="pos-buy-labels">
                    <span>Barcode</span><span>Nama</span><span>Qty</span><span>Satuan</span><span>Harga beli</span><span>Disc %</span><span>Disc Rp</span>
                </div>
                <div class="pos-buy">
                    <input type="text" id="barcodeScan" class="form-control" placeholder="Barcode" autocomplete="off">
                    <input type="text" id="itemSearch" class="form-control" placeholder="Nama barang [F8]" autocomplete="off">
                    <input type="number" id="entryQty" class="form-control" min="0.01" step="0.01" value="1" title="Qty">
                    <select id="entrySat" class="form-control" title="Satuan"><option value="">Satuan</option></select>
                    <input type="number" id="entryBuy" class="form-control" min="0" step="1" value="0" title="Harga beli" placeholder="Harga beli">
                    <input type="number" id="entryDiscPct" class="form-control" min="0" step="0.01" value="0" title="Diskon persen" placeholder="%">
                    <input type="number" id="entryDiscRp" class="form-control" min="0" step="1" value="0" title="Diskon rupiah" placeholder="Rp">
                    <div id="itemResults" class="pos-results"></div>
                </div>
                <div class="pos-sell" id="sellBox">
                    @foreach([1, 2, 3] as $n)
                    <div class="pos-sell-row" id="sellRow{{ $n }}" hidden>
                        <input type="text" id="sellName{{ $n }}" class="form-control" readonly>
                        <input type="text" id="sellIsi{{ $n }}" class="form-control" readonly title="Isi">
                        <input type="number" id="sellMar{{ $n }}" class="form-control sell-mar" data-n="{{ $n }}" step="0.01" placeholder="Mar %" title="Margin">
                        <input type="number" id="sellHrg{{ $n }}" class="form-control sell-hrg" data-n="{{ $n }}" min="0" step="1" placeholder="Harga jual {{ $n }}">
                    </div>
                    @endforeach
                </div>
                <div class="pos-actions">
                    <button type="button" id="tmb-add" class="btn-fafa btn-fafa-primary">Tambah <span class="pos-kbd">F9</span></button>
                    <button type="button" id="tmb-simpan" class="btn-fafa btn-fafa-success">Simpan <span class="pos-kbd">F10</span></button>
                    <button type="button" id="tmb-reset" class="btn-fafa btn-fafa-warning">Reset</button>
                </div>
                <div class="pos-cart">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Barang</th>
                                <th>Satuan</th>
                                <th>Qty</th>
                                <th class="sales-money">Harga beli</th>
                                <th class="sales-money">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="pos-overlay" id="payPanel" hidden>
            <div class="pos-panel">
                <h2>Simpan faktur</h2>
                <input type="hidden" name="kd_bayar" id="kd_bayar" value="{{ old('kd_bayar', optional($purchase)->kd_bayar ?: 'TUNAI') }}">
                <div class="pos-pay-modes">
                    <button type="button" class="btn-fafa btn-fafa-secondary {{ old('kd_bayar', optional($purchase)->kd_bayar) === 'TEMPO' ? '' : 'is-on' }}" data-mode="TUNAI">Tunai</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary {{ old('kd_bayar', optional($purchase)->kd_bayar) === 'TEMPO' ? 'is-on' : '' }}" data-mode="TEMPO">Tempo</button>
                </div>
                <label for="disc_nota">Diskon nota</label>
                <input type="number" class="form-control" id="disc_nota" name="disc_nota" min="0" step="1" value="{{ old('disc_nota', $discNota) }}">
                <div class="pos-change">Total <span id="payTotal">Rp 0</span></div>
                <div id="tempoBox" @if(old('kd_bayar', optional($purchase)->kd_bayar) !== 'TEMPO') hidden @endif>
                    <label for="tgl_jt">Jatuh tempo</label>
                    <input type="date" class="form-control" id="tgl_jt" name="tgl_jt" value="{{ old('tgl_jt', optional($purchase)->tgl_jt ? \Illuminate\Support\Carbon::parse($purchase->tgl_jt)->format('Y-m-d') : '') }}">
                    <label for="byr_tunai">Dibayar sekarang</label>
                    <input type="number" class="form-control" id="byr_tunai" name="byr_tunai" min="0" step="1" value="{{ old('byr_tunai', optional($purchase)->byr_tunai ?? 0) }}">
                    <div class="pos-change">Sisa tempo <span id="sisaTempo">Rp 0</span></div>
                </div>
                <p class="text-muted">{{ $purchase ? 'Perubahan qty memakai selisih. Barang yang sudah terjual tidak bisa dikurangi di bawah jumlah itu.' : 'Tunai lunas saat disimpan. Tempo masuk daftar nota belum bayar.' }}</p>
                <div class="pos-actions">
                    <button type="submit" class="btn-fafa btn-fafa-success">Simpan</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" id="payClose">Tutup</button>
                </div>
            </div>
        </div>

        <div class="pos-overlay" id="notaPanel" hidden>
            <div class="pos-panel">
                <h2>Cari nota pembelian</h2>
                <input type="text" id="notaFilter" class="form-control" placeholder="Saring nomor atau supplier">
                <ul class="mb-0 mt-3" id="notaList">
                    @forelse($recent as $row)
                        <li class="mb-2 nota-item" data-text="{{ strtolower($row->no_fak.' '.$row->nm_sup) }}">
                            <a href="{{ route('purchases.show', $row->no_urut) }}">{{ $row->no_fak }}</a>
                            <span class="text-muted">{{ $row->nm_sup ?: 'Tanpa supplier' }} · Rp {{ number_format($row->tot_bayar, 0, ',', '.') }}</span>
                        </li>
                    @empty
                        <li>Belum ada faktur.</li>
                    @endforelse
                </ul>
                <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm mt-3" id="notaClose">Tutup</button>
            </div>
        </div>

        <div class="pos-overlay" id="hutangPanel" hidden>
            <div class="pos-panel">
                <h2>Nota belum bayar</h2>
                <input type="text" id="hutangFilter" class="form-control" placeholder="Saring nomor atau supplier">
                <ul class="mb-0 mt-3" id="hutangList">
                    @forelse($unpaid as $row)
                        <li class="mb-2 hutang-item" data-text="{{ strtolower($row->no_fak.' '.$row->nm_sup) }}">
                            <a href="{{ route('retail.payables.index', ['q' => $row->no_fak]) }}">{{ $row->no_fak }}</a>
                            <span class="text-muted">{{ $row->nm_sup ?: 'Tanpa supplier' }} · jatuh tempo {{ $row->tgl_jt ? date('d/m/Y', strtotime($row->tgl_jt)) : '—' }} · sisa Rp {{ number_format($row->byr_hutang, 0, ',', '.') }}</span>
                        </li>
                    @empty
                        <li>Tidak ada nota tempo yang masih terbuka.</li>
                    @endforelse
                </ul>
                <a href="{{ route('retail.payables.index') }}" class="btn-fafa btn-fafa-primary btn-fafa-sm mt-3">Buka daftar</a>
                <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm mt-3" id="hutangClose">Tutup</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let itemIndex = 0;
let searchRows = [];
let entryUnits = [];

function rupiah(n) {
    return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
}
function notice(text) {
    const box = document.getElementById('posNotice');
    box.hidden = !text;
    box.textContent = text || '';
}
function unitName(name) {
    const text = String(name || '').trim();
    return text && !/^\d+$/.test(text) ? text : 'pcs';
}
function productUnits(product) {
    const units = [];
    const push = (code, name, qty, price) => {
        if (!name && !code) return;
        units.push({
            code: code || 1,
            name: unitName(name),
            isi: qty || 1,
            sell: Number(price) || 0
        });
    };
    push(product.kd_kem1 || 1, product.nm_kem1, product.jum_kem1, product.hrg_jum1);
    if (product.nm_kem2) push(product.kd_kem2, product.nm_kem2, product.jum_kem2, product.hrg_jum2);
    if (product.nm_kem3) push(product.kd_kem3, product.nm_kem3, product.jum_kem3, product.hrg_jum3);
    if (!units.length) units.push({ code: 1, name: 'pcs', isi: 1, sell: Number(product.hrg_jum1) || 0 });
    return units;
}
function lineNet(qty, buy, pct, rp) {
    const gross = (Number(qty) || 0) * (Number(buy) || 0);
    const disc = gross * ((Number(pct) || 0) / 100) + (Number(rp) || 0);
    return Math.max(0, gross - disc);
}
function cartTotal() {
    let total = 0;
    $('#itemsBody tr').each(function() {
        total += parseFloat($(this).attr('data-subtotal')) || 0;
    });
    return total;
}
function amountDue() {
    return Math.max(0, cartTotal() - (parseFloat($('#disc_nota').val()) || 0));
}
function paintTotal() {
    const lines = cartTotal();
    const due = amountDue();
    $('#grandTotal').text(rupiah(due));
    $('#payTotal').text(rupiah(due));
    const cash = $('#kd_bayar').val() === 'TEMPO' ? Math.min(parseFloat($('#byr_tunai').val()) || 0, due) : due;
    $('#sisaTempo').text(rupiah(Math.max(0, due - cash)));
    const nota = parseFloat($('#disc_nota').val()) || 0;
    $('#totalHint').text(lines ? 'Belanja ' + rupiah(lines) + (nota ? ' · diskon nota ' + rupiah(nota) : '') : 'Belum ada barang');
}
function closePanels() {
    $('#payPanel, #notaPanel, #hutangPanel').attr('hidden', true);
    $('#itemResults, #supResults').hide();
}
function openPanel(id) {
    closePanels();
    $('#' + id).removeAttr('hidden');
}
function sellFromMargin(margin, buy, pct) {
    const net = (Number(buy) || 0) * (1 - (Number(pct) || 0) / 100);
    return Math.round(net * (1 + (Number(margin) || 0) / 100));
}
function showSell(units) {
    entryUnits = units;
    let any = false;
    for (let n = 1; n <= 3; n++) {
        const unit = units[n - 1];
        const row = $('#sellRow' + n);
        if (!unit) {
            row.attr('hidden', true);
            $('#sellHrg' + n).val('');
            continue;
        }
        any = true;
        row.removeAttr('hidden');
        $('#sellName' + n).val(unit.name);
        $('#sellIsi' + n).val('isi ' + unit.isi);
        $('#sellMar' + n).val('');
        $('#sellHrg' + n).val(unit.sell || '');
    }
    $('#sellBox').toggleClass('is-open', any);
}
function clearEntry() {
    $('#entryKd, #entryName, #entryBar, #itemSearch, #barcodeScan').val('');
    $('#entrySat').html('<option value="">Satuan</option>');
    $('#entryQty').val(1);
    $('#entryBuy, #entryDiscPct, #entryDiscRp').val(0);
    $('#sellBox').removeClass('is-open');
    $('.pos-sell-row').attr('hidden', true);
    entryUnits = [];
    $('#itemResults').hide().empty();
    searchRows = [];
}
function fillEntry(product) {
    const units = productUnits(product);
    const select = $('#entrySat').empty();
    units.forEach(function(unit, i) {
        select.append($('<option></option>').val(unit.code).text(unit.name).attr('data-i', i));
    });
    $('#entryKd').val(product.kd_brg);
    $('#entryName').val(product.nm_brg);
    $('#entryBar').val(product.kd_bar || '');
    $('#itemSearch').val(product.nm_brg);
    $('#entryBuy').val(product.hrg_beli || 0);
    showSell(units);
    $('#itemResults').hide();
    $('#entryQty').focus().select();
}
function commitEntry() {
    const kd = $('#entryKd').val();
    const chosen = $('#entrySat option:selected');
    if (!kd || !chosen.val()) {
        notice('Pilih barang dulu.');
        $('#itemSearch').focus();
        return;
    }
    const unit = entryUnits[Number(chosen.attr('data-i')) || 0] || { name: chosen.text(), code: chosen.val() };
    appendLine({
        kd_brg: kd,
        nm_brg: $('#entryName').val(),
        kd_bar: $('#entryBar').val(),
        kd_sat: unit.code,
        nm_sat: unit.name,
        jml_brg: $('#entryQty').val() || 1,
        hrg_beli: $('#entryBuy').val() || 0,
        disc1: $('#entryDiscPct').val() || 0,
        disc2: $('#entryDiscRp').val() || 0,
        hrg_jum1: $('#sellHrg1').val() || 0,
        hrg_jum2: $('#sellHrg2').val() || 0,
        hrg_jum3: $('#sellHrg3').val() || 0
    });
    clearEntry();
    paintTotal();
    notice('');
    $('#barcodeScan').focus();
}
function appendLine(line) {
    itemIndex++;
    const idx = itemIndex;
    const sub = lineNet(line.jml_brg, line.hrg_beli, line.disc1, line.disc2);
    const row = $(`
        <tr data-subtotal="${sub}">
            <td class="line-no"></td>
            <td>
                <input type="hidden" name="items[${idx}][no_urut]">
                <input type="text" class="form-control form-control-sm" name="items[${idx}][kd_brg]" readonly>
                <input type="hidden" name="items[${idx}][nm_brg]">
                <input type="hidden" name="items[${idx}][kd_bar]">
                <span class="sales-sub line-name"></span>
            </td>
            <td>
                <input type="hidden" name="items[${idx}][kd_sat]">
                <span class="line-sat"></span>
            </td>
            <td><input type="number" class="form-control form-control-sm line-qty" name="items[${idx}][jml_brg]" step="0.01" min="0.01"></td>
            <td class="sales-money"><input type="number" class="form-control form-control-sm line-buy" name="items[${idx}][hrg_beli]" step="1" min="0"></td>
            <td class="sales-money line-sub"></td>
            <td>
                <input type="hidden" name="items[${idx}][disc1]">
                <input type="hidden" name="items[${idx}][disc2]">
                <input type="hidden" name="items[${idx}][hrg_jum1]">
                <input type="hidden" name="items[${idx}][hrg_jum2]">
                <input type="hidden" name="items[${idx}][hrg_jum3]">
                <button type="button" class="btn btn-sm btn-danger btn-remove"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
    `);
    row.find('[name$="[no_urut]"]').val(line.no_urut || '');
    row.find('[name$="[kd_brg]"]').val(line.kd_brg);
    row.find('[name$="[nm_brg]"]').val(line.nm_brg || '');
    row.find('[name$="[kd_bar]"]').val(line.kd_bar || '');
    row.find('.line-name').text(line.nm_brg || line.kd_brg);
    row.find('[name$="[kd_sat]"]').val(line.kd_sat);
    row.find('.line-sat').text(line.nm_sat || 'pcs');
    row.find('.line-qty').val(line.jml_brg);
    row.find('.line-buy').val(line.hrg_beli);
    row.find('[name$="[disc1]"]').val(line.disc1 || 0);
    row.find('[name$="[disc2]"]').val(line.disc2 || 0);
    row.find('[name$="[hrg_jum1]"]').val(line.hrg_jum1 || 0);
    row.find('[name$="[hrg_jum2]"]').val(line.hrg_jum2 || 0);
    row.find('[name$="[hrg_jum3]"]').val(line.hrg_jum3 || 0);
    row.find('.line-sub').text(rupiah(sub));
    $('#itemsBody').append(row);
    renumber();
}
function renumber() {
    $('#itemsBody tr').each(function(i) { $(this).find('.line-no').text(i + 1); });
}
function recalcRow(row) {
    const sub = lineNet(row.find('.line-qty').val(), row.find('.line-buy').val(), row.find('[name$="[disc1]"]').val(), row.find('[name$="[disc2]"]').val());
    row.attr('data-subtotal', sub);
    row.find('.line-sub').text(rupiah(sub));
    paintTotal();
}
function renderProducts(products) {
    searchRows = products || [];
    if (!searchRows.length) {
        $('#itemResults').html('<div class="p-2 text-muted">Tidak ada barang</div>').show();
        return;
    }
    let html = '';
    searchRows.forEach(function(product, i) {
        html += `<button type="button" data-i="${i}"><span>${product.kd_brg}</span><span>${product.nm_brg}</span><span>${rupiah(product.hrg_beli || 0)}</span></button>`;
    });
    $('#itemResults').html(html).show();
}
function addFromBarcode(code) {
    $.post('{{ route("purchases.search-product") }}', { keyword: code }, function(products) {
        const p = (products || []).find(x => String(x.kd_bar) === code || String(x.kd_brg) === code) || (products || [])[0];
        if (!p) { notice('Barang tidak ditemukan'); return; }
        fillEntry(p);
        $('#barcodeScan').val('');
        notice('');
    });
}

$('#tmb-add').on('click', commitEntry);
$('#tmb-simpan').on('click', function() {
    if (!$('#itemsBody tr').length) { notice('Minimal 1 barang.'); return; }
    paintTotal();
    openPanel('payPanel');
    $('#disc_nota').focus().select();
});
$('#tmb-reset').on('click', function() { clearEntry(); $('#barcodeScan').focus(); });
$('#tmb-nota').on('click', function() { openPanel('notaPanel'); $('#notaFilter').focus(); });
$('#tmb-hutang').on('click', function() { openPanel('hutangPanel'); $('#hutangFilter').focus(); });
$('#payClose, #notaClose, #hutangClose').on('click', function() { closePanels(); $('#barcodeScan').focus(); });
$('.pos-pay-modes').on('click', 'button', function() {
    $('#kd_bayar').val($(this).data('mode'));
    $('.pos-pay-modes button').removeClass('is-on');
    $(this).addClass('is-on');
    if ($(this).data('mode') === 'TEMPO') {
        $('#tempoBox').removeAttr('hidden');
        if (!$('#tgl_jt').val()) {
            const base = new Date(($('#tgl_fak').val() || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
            base.setDate(base.getDate() + 30);
            const pad = function(n) { return String(n).padStart(2, '0'); };
            $('#tgl_jt').val(base.getFullYear() + '-' + pad(base.getMonth() + 1) + '-' + pad(base.getDate()));
        }
        $('#tgl_jt').focus();
    } else {
        $('#tempoBox').attr('hidden', true);
    }
    paintTotal();
});
$('#disc_nota, #byr_tunai').on('input', paintTotal);
$('#barcodeScan').on('keydown', function(e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const code = $(this).val().trim();
    if (code) addFromBarcode(code);
});
$('#entryQty').on('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); commitEntry(); }
});
$('#itemSearch').on('input', function() {
    $('#entryKd, #entryBar').val('');
    const keyword = $(this).val().trim();
    clearTimeout(window.itemTimer);
    if (keyword.length < 2) { $('#itemResults').hide(); return; }
    window.itemTimer = setTimeout(function() {
        $.post('{{ route("purchases.search-product") }}', { keyword: keyword }, renderProducts);
    }, 180);
});
$('#itemSearch').on('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        if ($('#itemResults').is(':visible') && searchRows.length) fillEntry(searchRows[0]);
        else if ($('#entryKd').val()) commitEntry();
    }
});
$('#itemResults').on('click', 'button', function() { fillEntry(searchRows[$(this).data('i')]); });
$('.sell-mar').on('input', function() {
    const n = $(this).data('n');
    $('#sellHrg' + n).val(sellFromMargin($(this).val(), $('#entryBuy').val(), $('#entryDiscPct').val()));
});
$('.sell-hrg').on('input', function() { $('#sellMar' + $(this).data('n')).val(''); });
$('#entryBuy, #entryDiscPct').on('input', function() {
    for (let n = 1; n <= 3; n++) {
        const margin = $('#sellMar' + n).val();
        if (margin !== '') $('#sellHrg' + n).val(sellFromMargin(margin, $('#entryBuy').val(), $('#entryDiscPct').val()));
    }
});
$('#itemsBody').on('change', '.line-qty, .line-buy', function() { recalcRow($(this).closest('tr')); });
$('#itemsBody').on('click', '.btn-remove', function() {
    $(this).closest('tr').remove();
    renumber();
    paintTotal();
});
$('#supSearch').on('input', function() {
    const kw = $(this).val().trim();
    if (kw.length < 2) { $('#supResults').hide().empty(); return; }
    $.post('{{ route("purchases.search-supplier") }}', { keyword: kw }, function(rows) {
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
    $('#supSearch').val('');
    $('#supResults').hide();
});
$('#notaFilter').on('input', function() {
    const q = $(this).val().toLowerCase();
    $('#notaList .nota-item').each(function() {
        $(this).toggle(!q || String($(this).data('text')).indexOf(q) >= 0);
    });
});
$('#hutangFilter').on('input', function() {
    const q = $(this).val().toLowerCase();
    $('#hutangList .hutang-item').each(function() {
        $(this).toggle(!q || String($(this).data('text')).indexOf(q) >= 0);
    });
});
$('#purchaseForm').on('submit', function(e) {
    if (!$('#itemsBody tr').length) {
        e.preventDefault();
        notice('Minimal harus ada 1 barang.');
        return;
    }
    if ($('#kd_bayar').val() === 'TEMPO' && !$('#tgl_jt').val()) {
        e.preventDefault();
        openPanel('payPanel');
        notice('Isi tanggal jatuh tempo.');
        $('#tgl_jt').focus();
    }
});
$('#purchaseForm').on('keydown', 'input', function(e) {
    if (e.key !== 'Enter') return;
    if (this.id === 'barcodeScan' || this.id === 'entryQty' || this.id === 'itemSearch') return;
    e.preventDefault();
});
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.altKey || e.metaKey) return;
    const map = {
        F8: function() { closePanels(); $('#itemSearch').focus(); },
        F9: commitEntry,
        F10: function() { $('#tmb-simpan').click(); },
        Escape: function() { closePanels(); $('#barcodeScan').focus(); }
    };
    if (!map[e.key]) return;
    e.preventDefault();
    map[e.key]();
}, true);
$('#barcodeScan').focus();
@json($editLines).forEach(appendLine);
paintTotal();
</script>
@endpush
