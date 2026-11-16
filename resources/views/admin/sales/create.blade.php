@extends('layouts.app')

@section('title', 'Penjualan')

@push('styles')
<script>document.documentElement.classList.add('pos-lock');</script>
@endpush

@section('content')
<form id="saleForm" action="{{ route('sales.store') }}" method="POST">
    @csrf
    <div class="pos-shell">
        <div class="pos-top">
            <strong>Penjualan</strong>
            @if(session('nm_toko'))
                <span class="pos-meta"><i class="fa fa-store"></i> {{ session('nm_toko') }}</span>
            @endif
            @if(session('nm_user'))
                <span class="pos-meta"><i class="fa fa-user"></i> {{ session('nm_user') }}</span>
            @endif
            <a href="{{ route('dashboard') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm pos-exit">Keluar</a>
        </div>

        @if(session('error'))
            <div class="pos-flash">{{ session('error') }}</div>
        @endif
        <div class="pos-flash" id="posNotice" hidden></div>

        <div class="pos-body">
            <aside class="pos-side">
                <label for="tgl_jual">Tanggal</label>
                <input type="date" class="form-control" id="tgl_jual" name="tgl_jual" value="{{ $businessDate ?? date('Y-m-d') }}" required>

                <label for="no_fak">No. nota</label>
                <input type="text" class="form-control" id="no_fak" name="no_fak" value="{{ $suggestedNo }}" required>

                <label for="barcodeScan">Barcode <span class="pos-kbd">F7</span></label>
                <input type="text" id="barcodeScan" class="form-control" placeholder="Scan lalu Enter" autocomplete="off">

                <label for="customerSearch">Pelanggan</label>
                <div class="pos-suggest">
                    <input type="text" class="form-control" id="customerSearch" placeholder="Nama atau kode" autocomplete="off">
                    <div id="customerResults" class="pos-results"></div>
                </div>
                <input type="text" class="form-control" id="kd_pel" name="kd_pel" placeholder="Kode" style="margin-top:0.3rem">
                <input type="text" class="form-control" id="nm_pel" name="nm_pel" placeholder="Nama pelanggan" style="margin-top:0.3rem">

                <label for="memberSearch">Member</label>
                <div class="pos-suggest">
                    <input type="text" class="form-control" id="memberSearch" placeholder="Cari member" autocomplete="off">
                    <div id="memberResults" class="pos-results"></div>
                </div>
                <input type="hidden" name="kd_member" id="kd_member">
                <input type="text" class="form-control" name="nm_member" id="nm_member" readonly placeholder="Nama member" style="margin-top:0.3rem">
                <small class="text-muted">Poin <span id="memberPoin">0</span></small>

                <label for="crewSearch">Crew</label>
                <div class="pos-suggest">
                    <input type="text" class="form-control" id="crewSearch" placeholder="Cari crew" autocomplete="off">
                    <div id="crewResults" class="pos-results"></div>
                </div>
                <input type="hidden" name="kd_crew" id="kd_crew">
                <input type="text" class="form-control" name="nm_crew" id="nm_crew" readonly placeholder="Nama crew" style="margin-top:0.3rem">
            </aside>

            <section class="pos-main">
                <div class="pos-total" id="grandTotal">Rp 0</div>
                <div class="pos-subline" id="totalHint">Belum ada barang</div>
                <div class="pos-entry">
                    <div class="pos-suggest pos-entry-search">
                        <input type="text" id="itemSearch" class="form-control" placeholder="Nama barang [F8]" autocomplete="off">
                        <div id="itemResults" class="pos-results"></div>
                    </div>
                    <select id="entrySat" class="form-control" title="Satuan"><option value="">Satuan</option></select>
                    <input type="number" id="entryQty" class="form-control" min="0.01" step="0.01" value="1" placeholder="Qty" title="Qty" inputmode="decimal">
                    <input type="number" id="entryDisc" class="form-control" min="0" step="0.01" value="0" placeholder="Disc" title="Diskon" inputmode="decimal">
                    <input type="number" id="entryPrice" class="form-control" min="0" step="0.01" value="0" placeholder="Harga" title="Harga" inputmode="decimal">
                    <input type="hidden" id="entryKd">
                    <input type="hidden" id="entryName">
                </div>
                <div class="pos-actions">
                    <button type="button" id="tmb-add" class="btn-fafa btn-fafa-primary">Tambah <span class="pos-kbd">F9</span></button>
                    <button type="button" id="tmb-bayar" class="btn-fafa btn-fafa-success">Bayar <span class="pos-kbd">F10</span></button>
                    <button type="button" id="tmb-panding" class="btn-fafa btn-fafa-secondary">Panding <span class="pos-kbd">F3</span></button>
                    <button type="button" id="tmb-listpanding" class="btn-fafa btn-fafa-secondary">List <span class="pos-kbd">F4</span></button>
                    @if(isset($pakets) && $pakets->isNotEmpty())
                    <button type="button" id="tmb-paket" class="btn-fafa btn-fafa-secondary">Paket</button>
                    @endif
                    <button type="button" id="tmb-batal" class="btn-fafa btn-fafa-danger">Batal <span class="pos-kbd">F12</span></button>
                    <button type="button" id="tmb-reset" class="btn-fafa btn-fafa-warning" title="Kosongkan isian barang">Reset</button>
                </div>
                <div class="pos-cart">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Diskon</th>
                                <th>Subtotal</th>
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
                <h2>Bayar nota</h2>
                <input type="hidden" name="kd_bayar" id="kd_bayar" value="TUNAI">
                <input type="hidden" name="byr_tunai" id="byr_tunai" value="0">
                <input type="hidden" name="byr_hutang" id="byr_hutang" value="0">
                <div class="pos-pay-modes">
                    <button type="button" class="btn-fafa btn-fafa-secondary is-on" data-mode="TUNAI">Tunai</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" data-mode="TEMPO">Tempo</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" data-mode="COD">COD</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" data-mode="QRIS">QRIS</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" data-mode="BNI">BNI</button>
                </div>
                <div class="pos-pay-grid">
                    <div>
                        <label for="disc_nota">Diskon nota</label>
                        <input type="number" class="form-control" id="disc_nota" name="disc_nota" min="0" step="1" value="0">
                    </div>
                    <div>
                        <label for="disc_item">Diskon item</label>
                        <input type="number" class="form-control" id="disc_item" readonly value="0">
                    </div>
                    <div>
                        <label for="voucher">Voucher umum</label>
                        <input type="number" class="form-control" id="voucher" name="voucher" min="0" step="1" value="0">
                    </div>
                    <div>
                        <label for="voucher_member">Voucher member</label>
                        <input type="number" class="form-control" id="voucher_member" name="voucher_member" readonly value="0">
                        <small class="text-muted">1% untuk member, belanja minimal Rp 300.000</small>
                    </div>
                </div>
                <label for="diterima" id="diterimaLabel">Diterima</label>
                <input type="number" class="form-control" id="diterima" name="diterima" step="1" min="0" value="0">
                <div class="pos-change"><span id="kembaliLabel">Kembali</span> <span id="kembali">Rp 0</span></div>
                <label for="poin_redeem">Tukar poin</label>
                <input type="number" step="0.01" min="0" name="poin_redeem" id="poin_redeem" class="form-control" value="0">
                <label for="ket">Keterangan</label>
                <input type="text" class="form-control" id="ket" name="ket" value="-">
                <label class="mt-2"><input type="checkbox" name="cetak" value="1" checked> Cetak nota</label>
                <div class="pos-actions">
                    <button type="submit" class="btn-fafa btn-fafa-success" id="tmb-simpan">Simpan</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" id="payClose">Tutup</button>
                </div>
            </div>
        </div>

        <div class="pos-overlay" id="draftPanel" hidden>
            <div class="pos-panel">
                <h2>Daftar panding</h2>
                <ul class="mb-3">
                    @forelse($pendingDrafts as $pending)
                        <li class="mb-1">
                            <a href="{{ route('sales.create', ['draft_id' => $pending->id]) }}">{{ $pending->no_fak }}</a>
                            <span class="text-muted">{{ $pending->tgl_jual?->format('d/m/Y') }}</span>
                        </li>
                    @empty
                        <li>Belum ada nota panding.</li>
                    @endforelse
                </ul>
                <a href="{{ route('sales.drafts.index') }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Halaman draft</a>
                <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="draftClose">Tutup</button>
            </div>
        </div>

        @if(isset($pakets) && $pakets->isNotEmpty())
        <div class="pos-overlay" id="paketPanel" hidden>
            <div class="pos-panel">
                <h2>Paket barang</h2>
                <select id="paketSelect" class="form-control">
                    <option value="">Pilih paket</option>
                    @foreach($pakets as $pk)
                        <option value="{{ $pk->no_urut }}">{{ $pk->nm_paket }}</option>
                    @endforeach
                </select>
                <div class="pos-actions">
                    <button type="button" class="btn-fafa btn-fafa-primary" id="btnLoadPaket">Masukkan ke nota</button>
                    <button type="button" class="btn-fafa btn-fafa-secondary" id="paketClose">Tutup</button>
                </div>
            </div>
        </div>
        @endif
    </div>
</form>
@endsection

@push('scripts')
<script>
let itemIndex = 0;
let searchRows = [];
let payDirty = false;

function rupiah(n) {
    return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
}
function notice(text) {
    const box = document.getElementById('posNotice');
    box.hidden = !text;
    box.textContent = text || '';
}
function cartTotal() {
    let total = 0;
    $('#itemsBody tr').each(function() {
        total += parseFloat($(this).attr('data-subtotal')) || 0;
    });
    return total;
}
function lineDiscSum() {
    let disc = 0;
    $('#itemsBody tr').each(function() {
        disc += parseFloat($(this).find('.disc').val()) || 0;
    });
    return disc;
}
function unitName(name) {
    const text = String(name || '').trim();
    return text && !/^\d+$/.test(text) ? text : 'pcs';
}
function productUnits(product) {
    const units = [];
    const push = (code, name, price) => {
        if (!name && !code) return;
        units.push({ code: code || 1, name: unitName(name), price: Number(price) || 0 });
    };
    push(product.kd_kem1 || product.kd_sat, product.nm_kem1 || product.nm_sat, product.hrg_jum1 || product.hrg_jual);
    if (product.nm_kem2) push(product.kd_kem2, product.nm_kem2, product.hrg_jum2);
    if (product.nm_kem3) push(product.kd_kem3, product.nm_kem3, product.hrg_jum3);
    if (!units.length) units.push({ code: 1, name: 'pcs', price: Number(product.hrg_jum1 || product.hrg_jual) || 0 });
    return units;
}
function payMode() {
    return $('.pos-pay-modes .is-on').data('mode') || 'TUNAI';
}
function amountDue() {
    const lines = cartTotal();
    const nota = Math.max(0, parseFloat($('#disc_nota').val()) || 0);
    const voucher = Math.max(0, parseFloat($('#voucher').val()) || 0);
    const member = ($('#kd_member').val() && lines >= 300000) ? Math.floor(lines * 0.01) : 0;
    const poin = Math.max(0, parseFloat($('#poin_redeem').val()) || 0);
    $('#disc_item').val(lineDiscSum());
    $('#voucher_member').val(member);
    return Math.max(0, lines - nota - voucher - member - poin);
}
function paintTotal() {
    const due = amountDue();
    const lines = cartTotal();
    const mode = payMode();
    $('#kd_bayar').val(mode);
    $('#grandTotal').text(rupiah(due));
    const potongan = Math.max(0, lines - due);
    $('#totalHint').text(lines ? 'Belanja ' + rupiah(lines) + ' · potongan ' + rupiah(potongan) : 'Belum ada barang');
    if (!payDirty) {
        $('#diterima').val(mode === 'TEMPO' ? 0 : due);
    }
    const diterima = parseFloat($('#diterima').val()) || 0;
    if (mode === 'TEMPO') {
        const tunai = Math.min(diterima, due);
        $('#byr_tunai').val(tunai);
        $('#byr_hutang').val(due - tunai);
        $('#diterimaLabel').text('Tunai sekarang');
        $('#kembaliLabel').text('Sisa tempo');
        $('#kembali').text(rupiah(due - tunai));
    } else {
        $('#byr_tunai').val(due);
        $('#byr_hutang').val(0);
        $('#diterimaLabel').text(mode === 'QRIS' ? 'Diterima QRIS' : (mode === 'COD' ? 'Diterima COD' : (mode === 'BNI' ? 'Diterima BNI' : 'Diterima')));
        $('#kembaliLabel').text('Kembali');
        $('#kembali').text(rupiah(diterima - due));
    }
}
function closePanels() {
    $('#payPanel, #draftPanel, #paketPanel').attr('hidden', true);
    $('#itemResults, #customerResults, #memberResults, #crewResults').hide();
}
function openPanel(id) {
    closePanels();
    $('#' + id).removeAttr('hidden');
}
function lastRow() {
    return $('#itemsBody tr').last();
}
function focusBarcode() {
    closePanels();
    $('#barcodeScan').focus();
}

let entryProduct = null;

function lineBase(qty, unit, product) {
    const u = String(unit || '');
    const jum3 = Number(product.jum_kem3) || 0;
    const jum2 = Number(product.jum_kem2) || 0;
    if (product.kd_kem3 && u === String(product.kd_kem3) && jum3 > 0) return qty * jum3;
    if (product.kd_kem2 && u === String(product.kd_kem2) && jum2 > 0) return qty * jum2;
    return qty;
}
function stockMeta(row) {
    const stok = row.attr('data-stok');
    if (stok === undefined) return null;
    return {
        kd_brg: row.find('.kd_brg').val(),
        nm_brg: row.find('.nm_brg').val(),
        jml_brg: Number(stok),
        kd_kem2: row.attr('data-kd2'),
        jum_kem2: Number(row.attr('data-jum2')) || 0,
        kd_kem3: row.attr('data-kd3'),
        jum_kem3: Number(row.attr('data-jum3')) || 0
    };
}
function usedBase(sku, skipEl) {
    let sum = 0;
    $('#itemsBody tr').each(function() {
        if (skipEl && this === skipEl) return;
        const meta = stockMeta($(this));
        if (!meta || meta.kd_brg !== sku) return;
        sum += lineBase(parseFloat($(this).find('.jml_brg').val()) || 0, $(this).find('.kd_sat').val(), meta);
    });
    return sum;
}
function guardStock(product, qty, unit, skipEl) {
    if (!product || product.jml_brg === undefined || product.jml_brg === null || product.jml_brg === '') return true;
    const need = lineBase(Number(qty) || 0, unit, product) + usedBase(product.kd_brg, skipEl);
    const left = Number(product.jml_brg) || 0;
    if (need - left > 0.0001) {
        const shown = left.toLocaleString('id-ID', { maximumFractionDigits: 2 });
        notice('Stok ' + (product.nm_brg || product.kd_brg) + ' hanya tersisa ' + shown + '.');
        return false;
    }
    return true;
}
function rememberStock(row, product) {
    if (!product || product.stok === undefined || product.stok === null || product.stok === '') return;
    row.attr('data-stok', product.stok);
    row.attr('data-kd2', product.kd_kem2 || '');
    row.attr('data-jum2', product.jum_kem2 || 0);
    row.attr('data-kd3', product.kd_kem3 || '');
    row.attr('data-jum3', product.jum_kem3 || 0);
}
function exactProduct(products, code) {
    return (products || []).find(function(x) {
        return String(x.kd_bar) === code || String(x.kd_brg) === code;
    }) || null;
}

function addLine(data) {
    itemIndex++;
    const idx = itemIndex;
    const row = $(`
        <tr data-index="${idx}" data-subtotal="0">
            <td class="line-no"></td>
            <td>
                <input type="text" class="form-control form-control-sm kd_brg" name="items[${idx}][kd_brg]" required readonly>
            </td>
            <td><input type="text" class="form-control form-control-sm nm_brg" name="items[${idx}][nm_brg]" readonly></td>
            <td>
                <select class="form-control form-control-sm kd_sat" name="items[${idx}][kd_sat]" title="Satuan"></select>
                <input type="hidden" class="nm_sat" name="items[${idx}][nm_sat]">
            </td>
            <td><input type="number" class="form-control form-control-sm jml_brg" name="items[${idx}][jml_brg]" step="0.01" min="0.01" required></td>
            <td><input type="number" class="form-control form-control-sm hrg_jual" name="items[${idx}][hrg_jual]" step="0.01" min="0" required></td>
            <td><input type="number" class="form-control form-control-sm disc" name="items[${idx}][disc]" step="0.01" min="0" value="0"></td>
            <td class="subtotal">Rp 0</td>
            <td><button type="button" class="btn btn-sm btn-danger btn-remove"><i class="fa fa-trash"></i></button></td>
        </tr>
    `);
    row.find('.kd_brg').val(data.kd_brg || '');
    row.find('.nm_brg').val(data.nm_brg || '');
    row.find('.jml_brg').val(data.jml_brg || 1);
    row.find('.hrg_jual').val(data.hrg_jual || 0);
    row.attr('data-hrg-awal', row.find('.hrg_jual').val() || 0);
    row.find('.disc').val(data.disc || 0);
    const units = (data.units && data.units.length) ? data.units : productUnits(data);
    const select = row.find('.kd_sat');
    units.forEach(function(unit) {
        select.append($('<option></option>').val(unit.code).text(unit.name).attr('data-price', unit.price));
    });
    select.val(String(data.kd_sat || units[0].code));
    if (!select.val()) select.prop('selectedIndex', 0);
    row.find('.nm_sat').val(select.find(':selected').text());
    rememberStock(row, data);
    row.attr('data-prev-sat', select.val() || '');
    $('#itemsBody').append(row);
    renumber();
    calculateRow(idx);
    return idx;
}
function renumber() {
    $('#itemsBody tr').each(function(i) {
        $(this).find('.line-no').text(i + 1);
    });
}
function applyAutoDiscount(index, callback) {
    const row = $(`tr[data-index="${index}"]`);
    const kdBrg = row.find('.kd_brg').val();
    if (!kdBrg) { if (callback) callback(); return; }
    const basePrice = row.attr('data-hrg-awal') || row.find('.hrg_jual').val() || 0;
    $.post('{{ route("sales.calculate-discount") }}', {
        _token: '{{ csrf_token() }}',
        kd_brg: kdBrg,
        kd_sat: row.find('.kd_sat').val() || 1,
        qty: row.find('.jml_brg').val() || 1,
        price: basePrice,
        tgl_jual: $('#tgl_jual').val(),
        kd_bayar: payMode()
    }, function(res) {
        if (res.discount !== undefined) row.find('.disc').val(res.discount);
        if (callback) callback();
    }).fail(function() { if (callback) callback(); });
}
function recalcCartDiscounts() {
    const rows = $('#itemsBody tr');
    if (!rows.length) {
        paintTotal();
        return;
    }
    rows.each(function() {
        calculateRow($(this).data('index'));
    });
}
function calculateRow(index) {
    applyAutoDiscount(index, function() {
        const row = $(`tr[data-index="${index}"]`);
        const qty = parseFloat(row.find('.jml_brg').val()) || 0;
        const price = parseFloat(row.find('.hrg_jual').val()) || 0;
        const disc = parseFloat(row.find('.disc').val()) || 0;
        const subtotal = (qty * price) - disc;
        row.attr('data-subtotal', subtotal);
        row.find('.subtotal').text(rupiah(subtotal));
        paintTotal();
    });
}
function clearEntry() {
    $('#entryKd, #entryName, #itemSearch').val('');
    $('#entrySat').html('<option value="">Satuan</option>');
    $('#entryQty').val(1);
    $('#entryDisc').val(0);
    $('#entryPrice').val(0);
    $('#itemResults').hide().empty();
    searchRows = [];
    entryProduct = null;
}
function fillEntry(product) {
    const units = productUnits(product);
    const select = $('#entrySat').empty();
    units.forEach(function(unit) {
        select.append($('<option></option>').val(unit.code).text(unit.name).attr('data-price', unit.price));
    });
    $('#entryKd').val(product.kd_brg);
    $('#entryName').val(product.nm_brg);
    $('#itemSearch').val(product.nm_brg);
    $('#entryPrice').val(units[0].price);
    $('#entrySat').data('units', units);
    entryProduct = product;
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
    const qty = $('#entryQty').val() || 1;
    if (entryProduct && !guardStock(entryProduct, qty, chosen.val(), null)) return;
    addLine({
        kd_brg: kd,
        nm_brg: $('#entryName').val() || $('#itemSearch').val(),
        jml_brg: qty,
        hrg_jual: $('#entryPrice').val() || chosen.data('price') || 0,
        disc: $('#entryDisc').val() || 0,
        kd_sat: chosen.val(),
        nm_sat: chosen.text(),
        units: $('#entrySat').data('units') || [],
        stok: entryProduct ? entryProduct.jml_brg : undefined,
        kd_kem2: entryProduct ? entryProduct.kd_kem2 : undefined,
        jum_kem2: entryProduct ? entryProduct.jum_kem2 : undefined,
        kd_kem3: entryProduct ? entryProduct.kd_kem3 : undefined,
        jum_kem3: entryProduct ? entryProduct.jum_kem3 : undefined
    });
    clearEntry();
    notice('');
    $('#barcodeScan').focus();
}
function placeScanned(p) {
    const units = productUnits(p);
    const qty = parseFloat($('#entryQty').val()) || 1;
    if (!guardStock(p, qty, units[0].code, null)) return;
    addLine({
        kd_brg: p.kd_brg,
        nm_brg: p.nm_brg,
        jml_brg: qty,
        hrg_jual: units[0].price,
        disc: $('#entryDisc').val() || 0,
        kd_sat: units[0].code,
        nm_sat: units[0].name,
        units: units,
        stok: p.jml_brg,
        kd_kem2: p.kd_kem2,
        jum_kem2: p.jum_kem2,
        kd_kem3: p.kd_kem3,
        jum_kem3: p.jum_kem3
    });
    $('#barcodeScan').val('');
    notice('');
    $('#barcodeScan').focus();
}
function addFromBarcode(code) {
    $.post('{{ route("sales.search-product") }}', { keyword: code }, function(products) {
        const p = exactProduct(products, code);
        if (p) { placeScanned(p); return; }
        $.post('{{ route("sales.search-product") }}', { keyword: code, any_stock: 1 }, function(all) {
            notice(exactProduct(all, code) ? 'Stok habis' : 'Barang tidak ditemukan');
        });
    });
}
function renderProductList(products) {
    searchRows = products || [];
    if (!searchRows.length) {
        $('#itemResults').html('<div class="p-2 text-muted">Tidak ada barang</div>').show();
        return;
    }
    let html = '';
    searchRows.forEach(function(product, i) {
        const price = product.hrg_jum1 || product.hrg_jual || 0;
        const stok = Number(product.jml_brg) || 0;
        html += `<button type="button" data-i="${i}"><span>${product.kd_brg}</span><span>${product.nm_brg}</span><span>stok ${stok.toLocaleString('id-ID')}</span><span>${rupiah(price)}</span></button>`;
    });
    $('#itemResults').html(html).show();
}
function saveDraft() {
    if (!$('#itemsBody tr').length) {
        notice('Nota masih kosong.');
        return;
    }
    const payload = {
        header: {
            kd_pel: $('#kd_pel').val(), nm_pel: $('#nm_pel').val(),
            kd_bayar: payMode(),
            disc_nota: $('#disc_nota').val(),
            voucher: $('#voucher').val(),
            diterima: $('#diterima').val(),
            poin_redeem: $('#poin_redeem').val(),
            ket: $('#ket').val()
        },
        items: []
    };
    $('#itemsBody tr').each(function() {
        payload.items.push({
            kd_brg: $(this).find('.kd_brg').val(),
            nm_brg: $(this).find('.nm_brg').val(),
            jml_brg: $(this).find('.jml_brg').val(),
            hrg_jual: $(this).find('.hrg_jual').val(),
            disc: $(this).find('.disc').val(),
            kd_sat: $(this).find('.kd_sat').val(),
            nm_sat: $(this).find('.nm_sat').val()
        });
    });
    $.ajax({
        url: '{{ route("sales.drafts.store") }}',
        method: 'POST',
        contentType: 'application/json',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        data: JSON.stringify({
            no_fak: $('#no_fak').val(),
            tgl_jual: $('#tgl_jual').val(),
            payload: payload
        }),
        success: function(res) {
            $('#itemsBody').empty();
            clearEntry();
            payDirty = false;
            if (res && res.next_no) {
                $('#no_fak').val(res.next_no);
                window.notaAwal = res.next_no;
            }
            paintTotal();
            notice('Nota dipending. Tekan F4 untuk membuka daftar.');
            $('#barcodeScan').focus();
        }
    });
}
function cancelNote() {
    if (!$('#itemsBody tr').length) {
        clearEntry();
        return;
    }
    if (!confirm('Nota akan dibatalkan?')) return;
    $('#itemsBody').empty();
    clearEntry();
    payDirty = false;
    $('#no_fak').val(window.notaAwal || $('#no_fak').val());
    paintTotal();
    notice('');
    $('#barcodeScan').focus();
}
function openPay() {
    if (!$('#itemsBody tr').length) {
        notice('Minimal 1 barang.');
        return;
    }
    payDirty = false;
    paintTotal();
    openPanel('payPanel');
    $('#diterima').focus().select();
}

$('#tmb-add').on('click', commitEntry);
$('#tmb-bayar').on('click', openPay);
$('#tmb-panding').on('click', saveDraft);
$('#tmb-listpanding').on('click', function() { openPanel('draftPanel'); });
$('#tmb-batal').on('click', cancelNote);
$('#tmb-reset').on('click', function() { clearEntry(); $('#itemSearch').focus(); });
$('#payClose, #draftClose, #paketClose').on('click', function() { closePanels(); $('#barcodeScan').focus(); });
$('#tmb-paket').on('click', function() { openPanel('paketPanel'); });

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
    $('#entryKd').val('');
    const keyword = $(this).val().trim();
    clearTimeout(window.itemTimer);
    if (keyword.length < 2) { $('#itemResults').hide(); return; }
    window.itemTimer = setTimeout(function() {
        $.post('{{ route("sales.search-product") }}', { keyword: keyword }, renderProductList);
    }, 180);
});
$('#itemSearch').on('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        if ($('#itemResults').is(':visible') && searchRows.length) {
            fillEntry(searchRows[0]);
        } else if ($('#entryKd').val()) {
            commitEntry();
        }
    }
});
$('#itemResults').on('click', 'button', function() {
    fillEntry(searchRows[$(this).data('i')]);
});
$('#entrySat').on('change', function() {
    const price = $(this).find(':selected').data('price');
    if (price !== undefined) $('#entryPrice').val(price);
});
$('#itemsBody').on('change', '.kd_sat', function() {
    const row = $(this).closest('tr');
    const opt = $(this).find(':selected');
    const meta = stockMeta(row);
    if (meta && !guardStock(meta, row.find('.jml_brg').val(), $(this).val(), row[0])) {
        $(this).val(row.attr('data-prev-sat') || $(this).val());
        return;
    }
    row.attr('data-prev-sat', $(this).val());
    row.find('.nm_sat').val(opt.text());
    if (opt.data('price') !== undefined) {
        row.find('.hrg_jual').val(opt.data('price'));
        row.attr('data-hrg-awal', opt.data('price'));
    }
    calculateRow(row.data('index'));
});
$('#itemsBody').on('focus', '.jml_brg', function() {
    $(this).attr('data-prev', $(this).val());
});
$('#itemsBody').on('change', '.jml_brg, .hrg_jual, .disc', function() {
    const row = $(this).closest('tr');
    if ($(this).hasClass('jml_brg')) {
        const meta = stockMeta(row);
        if (meta && !guardStock(meta, $(this).val(), row.find('.kd_sat').val(), row[0])) {
            $(this).val($(this).attr('data-prev') || 1);
            return;
        }
        $(this).attr('data-prev', $(this).val());
    }
    if ($(this).hasClass('hrg_jual')) {
        row.attr('data-hrg-awal', $(this).val());
    }
    calculateRow(row.data('index'));
});
$('#itemsBody').on('click', '.btn-remove', function() {
    $(this).closest('tr').remove();
    renumber();
    paintTotal();
});

function bindLookup(input, box, url, onPick) {
    $('#' + input).on('input', function() {
        const kw = $(this).val().trim();
        if (kw.length < 2) { $('#' + box).hide().empty(); return; }
        $.post(url, { keyword: kw }, function(rows) {
            let html = '';
            (rows || []).forEach(function(row, i) {
                html += `<button type="button" data-i="${i}">${onPick.label(row)}</button>`;
            });
            $('#' + box).html(html || '<div class="p-2 text-muted">Tidak ditemukan</div>').show().data('rows', rows || []);
        });
    });
    $('#' + box).on('click', 'button', function() {
        const rows = $('#' + box).data('rows') || [];
        onPick.pick(rows[$(this).data('i')]);
        $('#' + box).hide();
        $('#' + input).val('');
    });
}
bindLookup('customerSearch', 'customerResults', '{{ route("sales.search-customer") }}', {
    label: c => c.kd_pel + ' — ' + c.nm_pel,
    pick: c => { $('#kd_pel').val(c.kd_pel); $('#nm_pel').val(c.nm_pel); }
});
bindLookup('memberSearch', 'memberResults', '{{ route("sales.search-member") }}', {
    label: m => m.kd_member + ' — ' + m.nm_member,
    pick: m => { $('#kd_member').val(m.kd_member); $('#nm_member').val(m.nm_member); $('#memberPoin').text(m.poin); paintTotal(); }
});
bindLookup('crewSearch', 'crewResults', '{{ route("sales.search-crew") }}', {
    label: c => c.kd_crew + ' — ' + c.nm_crew,
    pick: c => { $('#kd_crew').val(c.kd_crew); $('#nm_crew').val(c.nm_crew); }
});

$('.pos-pay-modes').on('click', 'button', function() {
    payDirty = false;
    $('.pos-pay-modes button').removeClass('is-on');
    $(this).addClass('is-on');
    recalcCartDiscounts();
});
$('#disc_nota, #voucher, #poin_redeem').on('input', paintTotal);
$('#diterima').on('input', function() {
    payDirty = true;
    paintTotal();
});

$('#btnLoadPaket').on('click', function() {
    const id = $('#paketSelect').val();
    if (!id) return;
    $.get('{{ url('/sales/paket') }}/' + id + '/lines', function(lines) {
        (lines || []).forEach(function(line) { addLine(line); });
        closePanels();
        $('#barcodeScan').focus();
    });
});

$('#saleForm').on('submit', function(e) {
    if (!$('#itemsBody tr').length) {
        e.preventDefault();
        notice('Minimal harus ada 1 barang.');
        return false;
    }
    paintTotal();
    const due = amountDue();
    const mode = payMode();
    const diterima = parseFloat($('#diterima').val()) || 0;
    if (mode !== 'TEMPO' && diterima + 0.009 < due) {
        e.preventDefault();
        notice('Pembayaran kurang dari total nota.');
        openPanel('payPanel');
        return false;
    }
});
$('#saleForm').on('keydown', 'input', function(e) {
    if (e.key !== 'Enter') return;
    if (this.id === 'barcodeScan' || this.id === 'entryQty' || this.id === 'itemSearch' || this.id === 'diterima') return;
    e.preventDefault();
});

document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.altKey || e.metaKey) return;
    const map = {
        F7: function() { focusBarcode(); },
        F8: function() { closePanels(); $('#itemSearch').focus(); },
        F9: commitEntry,
        F10: openPay,
        F3: saveDraft,
        F4: function() { openPanel('draftPanel'); },
        F12: cancelNote,
        F2: function() {
            const row = lastRow();
            if (row.length) row.find('.jml_brg').focus().select();
        },
        F1: function() {
            const row = lastRow();
            if (row.length) row.find('.kd_sat').focus();
            else $('#entrySat').focus();
        },
        Escape: function() { closePanels(); $('#barcodeScan').focus(); }
    };
    if (!map[e.key]) return;
    e.preventDefault();
    e.stopPropagation();
    map[e.key]();
}, true);

@if(!empty($draft))
(function restoreDraft() {
    const p = @json($draft->payload);
    $('#no_fak').val(@json($draft->no_fak));
    $('#tgl_jual').val(@json(optional($draft->tgl_jual)->format('Y-m-d')));
    if (p.header) {
        $('#kd_pel').val(p.header.kd_pel || '');
        $('#nm_pel').val(p.header.nm_pel || '');
        $('#disc_nota').val(p.header.disc_nota || 0);
        $('#voucher').val(p.header.voucher || 0);
        $('#diterima').val(p.header.diterima || 0);
        $('#poin_redeem').val(p.header.poin_redeem || 0);
        $('#ket').val(p.header.ket || '');
        if (p.header.kd_bayar) {
            $('.pos-pay-modes button').removeClass('is-on');
            $('.pos-pay-modes button[data-mode="' + p.header.kd_bayar + '"]').addClass('is-on');
        }
        payDirty = true;
    }
    (p.items || []).forEach(function(it) { addLine(it); });
})();
@endif
window.notaAwal = $('#no_fak').val();
$('#barcodeScan').focus();
</script>
@endpush
