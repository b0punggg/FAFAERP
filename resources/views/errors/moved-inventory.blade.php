@extends('layouts.app')
@section('title', 'Dipindah ke gudang')
@section('content')
@include('components.page-header', [
    'title' => 'Menu ini dipindah ke inventory',
    'description' => 'Pantauan stok, mutasi gudang, ROP, dan master barang pusat dikelola di aplikasi gudang.',
])
<div class="fafa-card">
    <div class="fafa-card-body">
        <p>FAFA tetap untuk kasir gerai: penjualan, pembelian, opname gerai, dan laporan omset.</p>
        <p class="mb-3">Buka aplikasi gudang untuk kartu stok, persediaan, surat jalan, dan pantauan lintas gerai.</p>
        <a class="btn-fafa btn-fafa-primary" href="{{ $inventoryUrl }}">Buka inventory</a>
        <a class="btn-fafa btn-fafa-secondary" href="{{ route('dashboard') }}">Kembali ke beranda</a>
    </div>
</div>
@endsection
