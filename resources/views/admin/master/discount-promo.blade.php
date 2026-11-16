@extends('layouts.app')
@section('title', 'Discount Promo')
@section('content')
@php
    $channelOptions = ['' => 'Semua cara bayar', 'BNI' => 'BNI merchant'];
@endphp
@include('components.page-header', ['title' => 'Discount Promo', 'description' => 'Load barang dari nama/bagian seperti sistem lama. Promo BNI hanya terpakai jika kasir memilih bayar BNI.'])

<div class="fafa-card mb-3"><div class="fafa-card-body">
<form method="GET" class="form-inline"><input name="q" class="form-control mr-2" value="{{ request('q') }}" placeholder="Cari promo…"><button class="btn-fafa btn-fafa-secondary">Cari</button></form>
</div></div>

<div class="fafa-card mb-3">
<div class="fafa-card-header">Promo baru</div>
<div class="fafa-card-body">
<form method="POST" action="{{ route('master.discount-promo.store') }}" class="form-fafa js-promo-form">
@csrf
@include('admin.master.partials.discount-promo-fields', ['promo' => null, 'details' => collect(), 'channelOptions' => $channelOptions, 'bagian' => $bagian])
<button class="btn-fafa btn-fafa-primary">Simpan promo</button>
</form>
</div>
</div>

@if($editPromo)
<div class="fafa-card mb-3 border-primary">
<div class="fafa-card-header">Ubah {{ $editPromo->no_promo }}</div>
<div class="fafa-card-body">
<form method="POST" action="{{ route('master.discount-promo.update', $editPromo) }}" class="form-fafa js-promo-form">
@csrf
@method('PUT')
@include('admin.master.partials.discount-promo-fields', ['promo' => $editPromo, 'details' => $details, 'channelOptions' => $channelOptions, 'bagian' => $bagian])
<button class="btn-fafa btn-fafa-primary">Simpan perubahan</button>
<a href="{{ route('master.discount-promo.index') }}" class="btn-fafa btn-fafa-link">Batal</a>
</form>
</div>
</div>
@endif

<div class="fafa-card"><div class="fafa-card-body" style="padding:0">
<table class="fafa-table"><thead><tr><th>No</th><th>Nama</th><th>Saluran</th><th>Periode</th><th>Rp</th><th>%</th><th>Barang</th><th></th></tr></thead>
<tbody>
@foreach($items as $p)
@php $promoLines = $lineMap->get($p->no_promo, collect()); @endphp
<tr>
<td>{{ $p->no_promo }}</td>
<td>{{ $p->nama_promo }}</td>
<td>{{ ($p->kd_bayar ?: '') === 'BNI' ? 'BNI' : 'Semua' }}</td>
<td>{{ optional($p->tgl_awal)->format('d/m/Y') }} – {{ optional($p->tgl_akhir)->format('d/m/Y') }}</td>
<td>{{ number_format($p->disc_rupiah, 0, ',', '.') }}</td>
<td>{{ $p->disc_persen }}%</td>
<td>
    <button type="button" class="btn-fafa btn-fafa-sm btn-fafa-secondary js-toggle-promo" data-target="promo-{{ $p->no_urut }}">
        {{ $promoLines->count() ? $promoLines->count().' barang' : 'Semua SKU' }}
    </button>
</td>
<td class="text-nowrap">
<a href="{{ route('master.discount-promo.index', ['edit' => $p->no_urut]) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Ubah</a>
<form method="POST" action="{{ route('master.discount-promo.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Hapus promo?')">@csrf @method('DELETE')
<button class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button></form>
</td>
</tr>
<tr id="promo-{{ $p->no_urut }}" hidden>
<td colspan="8" style="background:#f8fafc; padding:0.75rem 1rem;">
    @if($promoLines->isEmpty())
        <p class="text-muted mb-0">Tidak ada daftar SKU. Diskon default berlaku untuk semua barang selama periode promo.</p>
    @else
        <div style="max-height:280px; overflow-y:auto;">
        <table class="fafa-table mb-0">
            <thead><tr><th>SKU</th><th>Nama barang</th><th>Disc Rp</th><th>Disc %</th></tr></thead>
            <tbody>
            @foreach($promoLines as $line)
                <tr>
                    <td>{{ $line->sku ?? $line->kd_brg }}</td>
                    <td>{{ $line->nm_brg ?: '—' }}</td>
                    <td>{{ number_format((float) $line->disc_rupiah, 0, ',', '.') }}</td>
                    <td>{{ $line->disc_persen }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    @endif
</td>
</tr>
@endforeach
</tbody></table>
</div><x-fafa-pagination :paginator="$items" /></div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
const searchUrl = @json(route('master.discount-promo.search-products'));

function emptyRow() {
    return '<tr class="js-empty-brg"><td colspan="6" class="text-center text-muted py-4">Klik Load barang untuk memuat daftar, atau biarkan kosong untuk semua SKU.</td></tr>';
}
function formBody($form) {
    return $form.find('.js-sku-body');
}
function renumber($form) {
    formBody($form).find('[data-sku-row]').each(function(i) {
        $(this).find('.js-no').text(i + 1);
    });
    const n = formBody($form).find('[data-sku-row]').length;
    $form.find('.js-sku-count').text(n);
    if (!n && !formBody($form).find('.js-empty-brg').length) {
        formBody($form).html(emptyRow());
    }
}
function knownCodes($form) {
    const set = {};
    formBody($form).find('[data-sku-row]').each(function() {
        set[String($(this).data('kd'))] = true;
    });
    return set;
}
function addItem($form, item, discRp, discPct) {
    const kd = String(item.kd_brg || '');
    if (!kd) return false;
    formBody($form).find('.js-empty-brg').remove();
    const tr = $('<tr data-sku-row></tr>').attr('data-kd', kd);
    tr.append($('<td class="js-no"></td>'));
    tr.append($('<td></td>').text(item.sku || kd));
    tr.append($('<td></td>').text(item.nm_brg || '—'));
    tr.append($('<td></td>').append($('<input type="number" step="0.01" min="0" class="form-control form-control-sm js-line-rp">').val(discRp)));
    tr.append($('<td></td>').append($('<input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm js-line-pct">').val(discPct)));
    tr.append('<td><button type="button" class="btn-fafa btn-fafa-sm btn-fafa-danger js-del-brg"><i class="fa fa-trash"></i></button></td>');
    formBody($form).append(tr);
    return true;
}
function collectLines($form) {
    const lines = [];
    formBody($form).find('[data-sku-row]').each(function() {
        const kd = String($(this).data('kd') || '');
        if (!kd) return;
        lines.push({
            kd_brg: kd,
            disc_rupiah: $(this).find('.js-line-rp').val() || 0,
            disc_persen: $(this).find('.js-line-pct').val() || 0
        });
    });
    return lines;
}
function loadBarang($form) {
    const byNama = $.trim($form.find('.js-by-nama').val() || '');
    const idBag = $form.find('.js-by-bagian').val() || '';
    if (!byNama && !idBag) {
        alert('Masukkan nama brand/kategori atau pilih bagian terlebih dahulu');
        return;
    }
    const discRp = $form.find('.js-disc-rp').val() || 0;
    const discPct = $form.find('.js-disc-pct').val() || 0;
    const btn = $form.find('.js-load-brg').prop('disabled', true);
    $.post(searchUrl, { by_nama: byNama, id_bag: idBag })
        .done(function(res) {
            const have = knownCodes($form);
            let added = 0;
            (res.items || []).forEach(function(item) {
                if (have[String(item.kd_brg)]) return;
                if (addItem($form, item, discRp, discPct)) {
                    have[String(item.kd_brg)] = true;
                    added++;
                }
            });
            renumber($form);
            if (!(res.items || []).length) {
                alert('Tidak ada barang ditemukan');
            } else if (!added) {
                alert('Semua barang hasil pencarian sudah ada di daftar');
            } else if (res.truncated) {
                alert(added + ' barang ditambah (hasil dipotong 3000). Persempit pencarian jika masih kurang.');
            }
        })
        .fail(function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memuat barang';
            alert(msg);
        })
        .always(function() { btn.prop('disabled', false); });
}

$(document).on('click', '.js-load-brg', function() {
    loadBarang($(this).closest('form'));
});
$(document).on('keydown', '.js-by-nama', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        loadBarang($(this).closest('form'));
    }
});
$(document).on('click', '.js-clear-brg', function() {
    const $form = $(this).closest('form');
    if (!formBody($form).find('[data-sku-row]').length) return;
    if (!confirm('Kosongkan daftar barang?')) return;
    formBody($form).html(emptyRow());
    $form.find('.js-sku-count').text('0');
});
$(document).on('click', '.js-del-brg', function() {
    const $form = $(this).closest('form');
    $(this).closest('tr').remove();
    renumber($form);
});
$(document).on('submit', '.js-promo-form', function() {
    $(this).find('.js-lines-json').val(JSON.stringify(collectLines($(this))));
});
$(document).on('click', '.js-toggle-promo', function() {
    const row = document.getElementById($(this).data('target'));
    if (!row) return;
    row.hidden = !row.hidden;
});
</script>
@endpush
