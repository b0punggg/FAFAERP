@extends('layouts.app')

@section('title', $budget ? 'Ubah program' : 'Program baru')

@section('content')
@php
    $plan = $budget->plan_5w2h ?? [];
    $whyRows = $plan['why'] ?? [''];
    if ($whyRows === []) { $whyRows = ['']; }
    $people = $plan['pelaksana'] ?? [['peran' => '', 'nama' => '']];
    if ($people === []) { $people = [['peran' => '', 'nama' => '']]; }
    $howRows = $plan['how'] ?? [['nama' => '', 'langkah' => []]];
    if ($howRows === []) { $howRows = [['nama' => '', 'langkah' => []]]; }
    $costRows = $budget ? $budget->lines : collect();
@endphp

@include('components.page-header', [
    'title' => $budget ? 'Ubah program' : 'Program baru',
    'description' => 'Isi what, why, who, when, where, how, lalu rincian biaya',
])

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ $budget ? route('executive.budgets.update', $budget) : route('executive.budgets.store') }}" class="fafa-card">
    @csrf
    @if($budget) @method('PUT') @endif
    <div class="fafa-card-body">
        <div class="erp-form">
            <div>
                <label>PT</label>
                <select name="company_id" class="form-control" required>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id', $budget->company_id ?? '') == $company->id)>{{ $company->legal_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="span-2">
                <label>Nama program (What)</label>
                <input name="title" class="form-control" required value="{{ old('title', $budget->title ?? '') }}" placeholder="Contoh: Aktivasi Pelanggan Repeat Order">
            </div>
            <div>
                <label>Mulai (When)</label>
                <input type="date" name="period_start" class="form-control" required value="{{ old('period_start', optional($budget)->period_start?->format('Y-m-d')) }}">
            </div>
            <div>
                <label>Selesai</label>
                <input type="date" name="period_end" class="form-control" required value="{{ old('period_end', optional($budget)->period_end?->format('Y-m-d')) }}">
            </div>
            <div>
                <label>Tempat (Where)</label>
                <input name="tempat" class="form-control" value="{{ old('tempat', $budget->tempat ?? '') }}" placeholder="Contoh: Khinco">
            </div>
        </div>

        <h3 class="erp-h">Dasar program (Why)</h3>
        <div id="whyList">
            @foreach($whyRows as $i => $why)
                <input name="why[]" class="form-control mb-2" value="{{ $why }}" placeholder="Alasan {{ $i + 1 }}">
            @endforeach
        </div>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="addWhy">Tambah alasan</button>

        <h3 class="erp-h">Sasaran (Who)</h3>
        <div class="erp-form">
            <div class="span-2">
                <label>Siapa yang dituju</label>
                <input name="sasaran" class="form-control" value="{{ old('sasaran', $plan['sasaran'] ?? '') }}" placeholder="Contoh: Pelanggan yang transaksi tahun 2025">
            </div>
            <div>
                <label>Jumlah sasaran</label>
                <input type="number" min="0" name="sasaran_jumlah" class="form-control" value="{{ old('sasaran_jumlah', $plan['sasaran_jumlah'] ?? '') }}">
            </div>
        </div>

        <h3 class="erp-h">Pelaksana (Who)</h3>
        <div id="peopleList">
            @foreach($people as $i => $person)
                <div class="erp-form mb-2">
                    <input name="pelaksana[{{ $i }}][peran]" class="form-control" value="{{ $person['peran'] ?? '' }}" placeholder="Peran, contoh Monitoring">
                    <input name="pelaksana[{{ $i }}][nama]" class="form-control" value="{{ $person['nama'] ?? '' }}" placeholder="Jabatan atau nama, contoh Manager sales">
                </div>
            @endforeach
        </div>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="addPerson">Tambah pelaksana</button>

        <h3 class="erp-h">Tata cara (How)</h3>
        <div id="howList">
            @foreach($howRows as $i => $block)
                <div class="fafa-card mb-2 how-block">
                    <div class="fafa-card-body">
                        <input name="how[{{ $i }}][nama]" class="form-control mb-2" value="{{ $block['nama'] ?? '' }}" placeholder="Nama kegiatan, contoh CRM">
                        <textarea name="how[{{ $i }}][langkah]" class="form-control" rows="4" placeholder="Satu langkah per baris">{{ implode("\n", $block['langkah'] ?? []) }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="addHow">Tambah kegiatan</button>

        <h3 class="erp-h">Rincian biaya (How much)</h3>
        <table class="fafa-table" id="costTable">
            <thead><tr><th>Kegiatan</th><th>Pengeluaran</th><th>Qty</th><th>Harga</th><th>Jumlah</th></tr></thead>
            <tbody>
                @forelse($costRows as $i => $line)
                    <tr>
                        <td><input name="lines[{{ $i }}][kegiatan]" class="form-control" value="{{ $line->kegiatan }}"></td>
                        <td><input name="lines[{{ $i }}][nama]" class="form-control" value="{{ $line->uraian ?: $line->cost_center }}"></td>
                        <td><input name="lines[{{ $i }}][qty]" type="number" min="0" step="0.01" class="form-control qty" value="{{ $line->qty }}"></td>
                        <td><input name="lines[{{ $i }}][harga]" type="number" min="0" step="0.01" class="form-control harga" value="{{ $line->unit_price }}"></td>
                        <td class="jumlah"></td>
                    </tr>
                @empty
                    <tr>
                        <td><input name="lines[0][kegiatan]" class="form-control" placeholder="CRM"></td>
                        <td><input name="lines[0][nama]" class="form-control" placeholder="Pulsa CRM"></td>
                        <td><input name="lines[0][qty]" type="number" min="0" step="0.01" class="form-control qty" value="0"></td>
                        <td><input name="lines[0][harga]" type="number" min="0" step="0.01" class="form-control harga" value="0"></td>
                        <td class="jumlah"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <p class="mt-2"><strong>Jumlah program: <span id="grand">Rp 0</span></strong></p>
        <button type="button" class="btn-fafa btn-fafa-secondary btn-fafa-sm" id="addLine">Tambah baris biaya</button>
    </div>
    <div class="fafa-card-body" style="border-top:1px solid var(--fafa-border)">
        <button class="btn-fafa btn-fafa-primary">Simpan draf</button>
        <a href="{{ route('executive.budgets.index') }}" class="btn-fafa btn-fafa-secondary">Kembali</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
function rupiah(n) {
    return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
}
function paintMoney() {
    let total = 0;
    document.querySelectorAll('#costTable tbody tr').forEach(function(row) {
        const qty = Number(row.querySelector('.qty')?.value || 0);
        const harga = Number(row.querySelector('.harga')?.value || 0);
        const jumlah = qty * harga;
        total += jumlah;
        const cell = row.querySelector('.jumlah');
        if (cell) cell.textContent = rupiah(jumlah);
    });
    document.getElementById('grand').textContent = rupiah(total);
}
document.getElementById('costTable').addEventListener('input', paintMoney);
document.getElementById('addWhy').addEventListener('click', function() {
    const input = document.createElement('input');
    input.name = 'why[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'Alasan';
    document.getElementById('whyList').appendChild(input);
});
document.getElementById('addPerson').addEventListener('click', function() {
    const i = document.querySelectorAll('#peopleList .erp-form').length;
    const wrap = document.createElement('div');
    wrap.className = 'erp-form mb-2';
    wrap.innerHTML = '<input name="pelaksana[' + i + '][peran]" class="form-control" placeholder="Peran"><input name="pelaksana[' + i + '][nama]" class="form-control" placeholder="Jabatan atau nama">';
    document.getElementById('peopleList').appendChild(wrap);
});
document.getElementById('addHow').addEventListener('click', function() {
    const i = document.querySelectorAll('#howList .how-block').length;
    const wrap = document.createElement('div');
    wrap.className = 'fafa-card mb-2 how-block';
    wrap.innerHTML = '<div class="fafa-card-body"><input name="how[' + i + '][nama]" class="form-control mb-2" placeholder="Nama kegiatan"><textarea name="how[' + i + '][langkah]" class="form-control" rows="4" placeholder="Satu langkah per baris"></textarea></div>';
    document.getElementById('howList').appendChild(wrap);
});
document.getElementById('addLine').addEventListener('click', function() {
    const i = document.querySelectorAll('#costTable tbody tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = '<td><input name="lines[' + i + '][kegiatan]" class="form-control"></td><td><input name="lines[' + i + '][nama]" class="form-control"></td><td><input name="lines[' + i + '][qty]" type="number" min="0" step="0.01" class="form-control qty" value="0"></td><td><input name="lines[' + i + '][harga]" type="number" min="0" step="0.01" class="form-control harga" value="0"></td><td class="jumlah"></td>';
    document.querySelector('#costTable tbody').appendChild(tr);
});
paintMoney();
</script>
@endpush
