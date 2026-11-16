@extends('layouts.app')

@section('title', 'Master Barang')

@section('content')
@include('components.page-header', [
    'title' => 'Master Barang',
    'description' => 'Kelola SKU, harga, dan stok legacy per gerai',
    'actionUrl' => route('products.create'),
    'actionLabel' => 'Tambah Barang',
])

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" action="{{ route('products.index') }}" class="form-fafa row align-items-end">
            <div class="col-md-6 form-group mb-0">
                <label>Cari barang</label>
                <input type="text" name="search" class="form-control" placeholder="Kode, nama, atau barcode…" value="{{ $search }}">
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn-fafa btn-fafa-primary"><i class="fa fa-search"></i> Cari</button>
                @if($search)
                    <a href="{{ route('products.index') }}" class="btn-fafa btn-fafa-secondary">Reset</a>
                @endif
                <a href="{{ route('reports.stock') }}" class="btn-fafa btn-fafa-secondary">Laporan stok</a>
                <a href="{{ route('products.export', request()->only('search')) }}" class="btn-fafa btn-fafa-secondary"><i class="fa fa-download"></i> Export CSV</a>
            </div>
        </form>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Barcode</th>
                    <th>Nama</th>
                    <th>Satuan</th>
                    <th>Harga jual</th>
                    <th>Stok</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td><code>{{ $product->kd_brg }}</code></td>
                    <td>{{ $product->kd_bar ?? '—' }}</td>
                    <td>{{ $product->nm_brg }}</td>
                    <td>{{ $product->nm_kem1 }}</td>
                    <td>Rp {{ number_format($product->hrg_jum1, 0, ',', '.') }}</td>
                    <td>
                        @if($product->jml_brg <= 0)
                            <span class="badge-fafa badge-danger">{{ number_format($product->jml_brg, 0) }}</span>
                        @elseif($product->jml_brg <= 5)
                            <span class="badge-fafa badge-warning">{{ number_format($product->jml_brg, 0) }}</span>
                        @else
                            {{ number_format($product->jml_brg, 0) }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('products.edit', $product->kd_brg) }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada barang.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$products" />
</div>
@endsection
