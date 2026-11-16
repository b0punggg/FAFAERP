@extends('layouts.app')
@section('title', 'Detail Paket')
@section('content')
@include('components.page-header', [
    'title' => 'Isi paket: '.$paket->nm_paket,
    'description' => 'Tambah SKU/barcode ke bundel. Tombol Paket di kasir memakai daftar ini.',
])

<div class="mb-3">
    <a href="{{ route('master.paket.index') }}" class="btn-fafa btn-fafa-secondary"><i class="fa fa-arrow-left"></i> Kembali</a>
</div>

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Tambah barang ke paket</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('master.paket.lines.store', $paket) }}" class="form-fafa row">
            @csrf
            <div class="col-md-3 form-group">
                <label>SKU / Barcode</label>
                <input name="kd_brg" id="paketKdBrg" class="form-control" required autocomplete="off">
            </div>
            <div class="col-md-4 form-group">
                <label>Nama (otomatis)</label>
                <input id="paketNmBrg" class="form-control" readonly>
            </div>
            <div class="col-md-2 form-group">
                <label>Qty</label>
                <input type="number" step="0.01" min="0.01" name="qty_brg" class="form-control" value="1" required>
            </div>
            <div class="col-md-1 form-group">
                <label>Sat</label>
                <input type="number" name="kd_sat" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn-fafa btn-fafa-primary w-100">Tambah</button>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>SKU</th><th>Nama barang</th><th>Qty</th><th>Sat</th><th></th></tr></thead>
            <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ $line->kd_brg }}</td>
                    <td>{{ $line->nm_brg ?: '—' }}</td>
                    <td>{{ $line->qty_brg }}</td>
                    <td>{{ $line->kd_sat }}</td>
                    <td>
                        <form method="POST" action="{{ route('master.paket.lines.destroy', $line) }}" onsubmit="return confirm('Hapus baris?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada barang dalam paket.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('paketKdBrg')?.addEventListener('blur', function () {
    const kw = this.value.trim();
    if (!kw) return;
    fetch('{{ route('sales.search-product') }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: new URLSearchParams({keyword: kw})
    }).then(r => r.json()).then(rows => {
        if (rows[0]) {
            document.getElementById('paketKdBrg').value = rows[0].kd_brg;
            document.getElementById('paketNmBrg').value = rows[0].nm_brg || '';
        }
    }).catch(() => {});
});
</script>
@endpush
@endsection
