@php
    $promo = $promo ?? null;
    $details = $details ?? collect();
    $bagian = $bagian ?? collect();
    $channelOptions = $channelOptions ?? ['' => 'Semua cara bayar', 'BNI' => 'BNI merchant'];
@endphp
<input type="hidden" name="lines_json" class="js-lines-json" value="">
<div class="row">
    <div class="col-md-4 form-group">
        <label>Nama promo</label>
        <input name="nama_promo" class="form-control" value="{{ old('nama_promo', optional($promo)->nama_promo) }}" required>
    </div>
    <div class="col-md-2 form-group">
        <label>Mulai</label>
        <input type="date" name="tgl_awal" class="form-control" value="{{ old('tgl_awal', optional(optional($promo)->tgl_awal)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-2 form-group">
        <label>Selesai</label>
        <input type="date" name="tgl_akhir" class="form-control" value="{{ old('tgl_akhir', optional(optional($promo)->tgl_akhir)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-2 form-group">
        <label>Disc Rp (default)</label>
        <input type="number" step="0.01" min="0" name="disc_rupiah" class="form-control js-disc-rp" value="{{ old('disc_rupiah', optional($promo)->disc_rupiah ?? 0) }}">
    </div>
    <div class="col-md-2 form-group">
        <label>Disc % (default)</label>
        <input type="number" step="0.01" min="0" max="100" name="disc_persen" class="form-control js-disc-pct" value="{{ old('disc_persen', optional($promo)->disc_persen ?? 0) }}">
    </div>
    <div class="col-md-3 form-group">
        <label>Berlaku untuk</label>
        <select name="kd_bayar" class="form-control">
            @foreach($channelOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('kd_bayar', optional($promo)->kd_bayar ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 form-group">
        <label>By nama</label>
        <input name="by_nama" class="form-control js-by-nama" value="{{ old('by_nama', optional($promo)->by_nama) }}" placeholder="Nama brand / kategori / kode / barang">
    </div>
    <div class="col-md-3 form-group">
        <label>By bagian</label>
        <select class="form-control js-by-bagian">
            <option value="">— Semua bagian —</option>
            @foreach($bagian as $b)
                <option value="{{ $b->no_urut }}">{{ $b->nm_bag }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 form-group d-flex align-items-end">
        <button type="button" class="btn-fafa btn-fafa-secondary js-load-brg mr-2">Load barang</button>
        <button type="button" class="btn-fafa btn-fafa-link js-clear-brg">Kosongkan daftar</button>
    </div>
</div>
<p class="text-muted small mb-2">Sama seperti sistem lama: isi <strong>By nama</strong> dan/atau <strong>By bagian</strong>, lalu Load barang. Barang yang sudah ada di daftar tidak diulang. Kosongkan daftar jika diskon default berlaku untuk semua SKU.</p>
<div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
<table class="fafa-table mb-0">
    <thead>
        <tr>
            <th style="width:48px">No</th>
            <th>SKU</th>
            <th>Nama barang</th>
            <th style="width:140px">Disc Rp</th>
            <th style="width:120px">Disc %</th>
            <th style="width:72px"></th>
        </tr>
    </thead>
    <tbody class="js-sku-body">
        @forelse($details as $i => $line)
            <tr data-sku-row data-kd="{{ $line->kd_brg }}">
                <td class="js-no">{{ $i + 1 }}</td>
                <td>{{ $line->sku ?? $line->kd_brg }}</td>
                <td>{{ $line->nm_brg ?: '—' }}</td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm js-line-rp" value="{{ $line->disc_rupiah }}"></td>
                <td><input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm js-line-pct" value="{{ $line->disc_persen }}"></td>
                <td><button type="button" class="btn-fafa btn-fafa-sm btn-fafa-danger js-del-brg"><i class="fa fa-trash"></i></button></td>
            </tr>
        @empty
            <tr class="js-empty-brg"><td colspan="6" class="text-center text-muted py-4">Klik Load barang untuk memuat daftar, atau biarkan kosong untuk semua SKU.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
<p class="text-muted small mt-2 mb-3"><span class="js-sku-count">{{ $details->count() }}</span> barang di daftar</p>
