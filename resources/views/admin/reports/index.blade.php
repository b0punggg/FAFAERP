@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
@include('components.page-header', ['title' => 'Semua laporan', 'description' => 'Pilih laporan, lalu saring periodenya. Setiap hasil bisa dicetak atau diunduh ke Excel.'])

<h2 class="report-group">Penjualan</h2>
<div class="report-dir mb-3">
    <a href="{{ route('reports.sales') }}"><strong>Penjualan</strong><span>Daftar faktur dan total pada gerai ini.</span></a>
    <a href="{{ route('reports.sales-by-product') }}"><strong>Penjualan per barang</strong><span>Barang yang paling laku dan omsetnya.</span></a>
    <a href="{{ route('reports.sales-by-customer') }}"><strong>Penjualan per pelanggan</strong><span>Jumlah transaksi dan omset tiap pelanggan.</span></a>
    <a href="{{ route('reports.sales-profit') }}"><strong>Laba penjualan</strong><span>Omset dan laba per baris faktur.</span></a>
    <a href="{{ route('reports.sales-by-section') }}"><strong>Penjualan per bagian</strong><span>Omset dikelompokkan menurut bagian barang.</span></a>
    <a href="{{ route('reports.sales-by-payment') }}"><strong>Penjualan per pembayaran</strong><span>Omset tunai, tempo, QRIS, BNI, dan COD.</span></a>
    <a href="{{ route('reports.sales-by-brand') }}"><strong>Penjualan per brand</strong><span>Omset dikelompokkan dari nama brand pada barang.</span></a>
    <a href="{{ route('reports.member-sales') }}"><strong>Transaksi member</strong><span>Belanja member beserta poin.</span></a>
    <a href="{{ route('reports.crew-sales') }}"><strong>Transaksi crew</strong><span>Penjualan yang dicatat atas nama crew.</span></a>
    <a href="{{ route('reports.promo-summary') }}"><strong>Diskon dan promo</strong><span>Potongan nota pada periode yang dipilih.</span></a>
</div>

<h2 class="report-group">Pembelian dan stok</h2>
<div class="report-dir mb-3">
    <a href="{{ route('reports.purchases') }}"><strong>Pembelian</strong><span>Faktur supplier yang masuk pada gerai ini.</span></a>
    <a href="{{ route('reports.purchases-by-supplier') }}"><strong>Pembelian per supplier</strong><span>Total belanja tiap supplier.</span></a>
    <a href="{{ route('reports.stock') }}"><strong>Stok barang</strong><span>Sisa stok, termasuk yang menipis atau habis.</span></a>
    <a href="{{ route('reports.inventory-monthly') }}"><strong>Barang masuk</strong><span>Qty dan nilai barang yang masuk dalam sebulan.</span></a>
    <a href="{{ route('reports.activity') }}"><strong>Mutasi, opname, dan retur</strong><span>Pergerakan stok selain jual beli biasa.</span></a>
</div>

<h2 class="report-group">Piutang dan rekap</h2>
<div class="report-dir mb-3">
    <a href="{{ route('reports.ar-ap') }}"><strong>Hutang dan piutang</strong><span>Sisa tagihan pelanggan dan supplier.</span></a>
    <a href="{{ route('reports.receivable-collections') }}"><strong>Pembayaran piutang</strong><span>Uang piutang yang sudah diterima.</span></a>
    <a href="{{ route('reports.recap') }}"><strong>Rekapitulasi bulanan</strong><span>Omset, pembelian, biaya, dan laba estimasi.</span></a>
    @if(\App\Support\StaffAccess::can(auth()->user(), 'pivot'))
        <a href="{{ route('reports.pivot') }}"><strong>Analisis data</strong><span>Kelompokkan omset, stok, atau piutang sesuai kebutuhan.</span></a>
    @endif
</div>
@if(\App\Support\StaffAccess::can(auth()->user(), 'finance'))
<h2 class="report-group">Keuangan per PT</h2>
<div class="report-dir mb-3">
    <a href="{{ route('finance.income-statement') }}"><strong>Laba rugi</strong><span>Pendapatan dan beban dari jurnal.</span></a>
    <a href="{{ route('finance.balance-sheet') }}"><strong>Neraca</strong><span>Aset, kewajiban, dan ekuitas.</span></a>
    <a href="{{ route('finance.trial-balance') }}"><strong>Trial balance</strong><span>Saldo debit dan kredit tiap akun.</span></a>
</div>
@endif
@endsection
