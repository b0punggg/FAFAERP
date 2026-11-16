@extends('layouts.app')

@section('title', 'Analisis Data')

@section('content')
@include('components.page-header', ['title' => 'Analisis Data', 'description' => 'Pilih kumpulan data, ukuran, dan pengelompokan. Angka dibaca saja.'])
@php
    $money = ['omzet', 'diskon', 'nilai', 'harga_rata', 'tagihan', 'tertagih', 'sisa'];
    $fmt = function ($measure, $value) use ($money) {
        $value = (float) $value;
        if (in_array($measure, $money, true)) {
            return number_format($value, 0, ',', '.');
        }
        return number_format($value, 2, ',', '.');
    };
    $first = $measures[0] ?? null;
    $max = 0;
    foreach ($result['rows'] as $row) {
        $max = max($max, abs((float) ($row[$first] ?? 0)));
    }
@endphp
<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-fafa">
            <div class="row align-items-end">
                <div class="col-md-3 form-group">
                    <label>Kumpulan data</label>
                    <select name="dataset" class="form-control" onchange="this.form.submit()">
                        @foreach($catalog as $key => $item)
                            <option value="{{ $key }}" @selected($dataset === $key)>{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Kelompokkan</label>
                    <select name="group" class="form-control">
                        @foreach($spec['groups'] as $key => $label)
                            <option value="{{ $key }}" @selected($group === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Lokasi</label>
                    <select name="kd_toko" class="form-control">
                        <option value="">Semua</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->kd_toko }}" @selected($kdToko === $store->kd_toko)>{{ $store->nm_toko }}</option>
                        @endforeach
                    </select>
                </div>
                @if($spec['dated'])
                <div class="col-md-3 form-group">
                    <label>Dari</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3 form-group">
                    <label>Sampai</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                @endif
                <div class="col-md-6 form-group">
                    <label>Ukuran</label>
                    <div>
                        @foreach($spec['measures'] as $key => $label)
                            <label class="mr-3"><input type="checkbox" name="measures[]" value="{{ $key }}" @checked(in_array($key, $measures, true))> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">@include('components.report-actions')</div>
            </div>
        </form>
    </div>
</div>
@if($result['note'])
<p>{{ $result['note'] }}</p>
@endif
@if($result['prior'])
<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <strong>Periode ini</strong>
        @foreach($measures as $measure)
            {{ $spec['measures'][$measure] }} {{ $fmt($measure, $result['totals'][$measure] ?? 0) }}
        @endforeach
        <br>
        <strong>Periode sebelumnya ({{ $result['prior']['from'] }} s/d {{ $result['prior']['to'] }})</strong>
        @foreach($measures as $measure)
            {{ $spec['measures'][$measure] }} {{ $fmt($measure, $result['prior']['totals'][$measure] ?? 0) }}
        @endforeach
    </div>
</div>
@elseif($result['totals'])
<p>
    @foreach($measures as $measure)
        <strong>{{ $spec['measures'][$measure] }}:</strong> {{ $fmt($measure, $result['totals'][$measure] ?? 0) }}
    @endforeach
</p>
@endif
@if($first && count($result['rows']))
<div class="fafa-card mb-3">
    <div class="fafa-card-header">{{ $spec['measures'][$first] }}</div>
    <div class="fafa-card-body">
        @foreach(array_slice($result['rows'], 0, 12) as $row)
            @php $width = $max > 0 ? round(abs((float) ($row[$first] ?? 0)) / $max * 100, 1) : 0; @endphp
            <div class="mb-2">
                <div>{{ $row['row_label'] ?: $row['row_key'] }}</div>
                <div style="background:#e9ecef;border-radius:4px;overflow:hidden;">
                    <div style="width:{{ $width }}%;background:#3d6b4f;color:#fff;padding:2px 8px;min-width:2rem;">{{ $fmt($first, $row[$first] ?? 0) }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
<div class="fafa-card mb-3">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>{{ $spec['groups'][$group] }}</th>
                    @foreach($measures as $measure)
                        <th class="text-right">{{ $spec['measures'][$measure] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse($result['rows'] as $row)
                <tr>
                    <td>{{ $row['row_label'] ?: $row['row_key'] }}</td>
                    @foreach($measures as $measure)
                        <td class="text-right">{{ $fmt($measure, $row[$measure] ?? 0) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 1 + count($measures) }}">Tidak ada baris pada filter ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="fafa-card">
            <div class="fafa-card-header">Simpan favorit</div>
            <div class="fafa-card-body">
                <form method="POST" action="{{ route('reports.pivot.favorites.store') }}" class="form-fafa">
                    @csrf
                    <input type="hidden" name="dataset" value="{{ $dataset }}">
                    <input type="hidden" name="group" value="{{ $group }}">
                    <input type="hidden" name="kd_toko" value="{{ $kdToko }}">
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    @foreach($measures as $measure)
                        <input type="hidden" name="measures[]" value="{{ $measure }}">
                    @endforeach
                    <div class="form-group">
                        <label>Nama</label>
                        <input name="name" class="form-control" maxlength="80" required placeholder="Contoh: Omzet gerai bulan ini">
                    </div>
                    <button class="btn-fafa btn-fafa-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="fafa-card">
            <div class="fafa-card-header">Favorit saya</div>
            <div class="fafa-card-body">
                <ul class="mb-0">
                @forelse($favorites as $favorite)
                    @php $filters = $favorite->filters; @endphp
                    <li class="mb-2">
                        <a href="{{ route('reports.pivot', $filters) }}">{{ $favorite->name }}</a>
                        <form method="POST" action="{{ route('reports.pivot.favorites.destroy', $favorite) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link btn-sm">Hapus</button>
                        </form>
                    </li>
                @empty
                    <li>Belum ada favorit.</li>
                @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
