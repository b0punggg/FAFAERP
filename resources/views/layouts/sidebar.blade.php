@php
    $can = fn (string $ability) => \App\Support\StaffAccess::can(auth()->user(), $ability);
@endphp
<aside class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="fa fa-home"></i><span>Beranda</span>
            </a>
        </li>

        @if($can('master'))
        <li class="menu-item menu-dropdown {{ request()->routeIs('products.*', 'master.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link" aria-expanded="false">
                <i class="fa fa-database"></i><span>Master Data</span>
            </a>
            <ul class="submenu">
                <li><a href="{{ route('master.customers.index') }}">Pelanggan</a></li>
                <li><a href="{{ route('master.suppliers.index') }}">Supplier</a></li>
                <li><a href="{{ route('master.members.index') }}">Member</a></li>
                <li><a href="{{ route('master.crew.index') }}">Crew</a></li>
                <li><a href="{{ route('master.paket.index') }}">Inisial Perpaket</a></li>
                <li><a href="{{ route('master.discount-fixed.index') }}">Diskon Tetap</a></li>
                <li><a href="{{ route('master.discount-promo.index') }}">Discount Promo</a></li>
                <li><a href="{{ route('master.brand-report.index') }}">Brand Report Penjualan</a></li>
                <li><a href="{{ route('master.users.index') }}">Pengguna gerai</a></li>
            </ul>
        </li>
        @endif

        @if($can('sales') || $can('store') || $can('transfer'))
        <li class="menu-item menu-dropdown {{ request()->routeIs('sales.*', 'purchases.*', 'retail.*', 'inventory.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link" aria-expanded="false">
                <i class="fa fa-exchange"></i><span>Transaksi</span>
            </a>
            <ul class="submenu">
                @if($can('sales'))
                    <li><a href="{{ route('sales.index') }}">Daftar Penjualan</a></li>
                    <li><a href="{{ route('sales.create') }}">Penjualan Baru</a></li>
                @endif
                @if($can('store'))
                    <li><a href="{{ route('purchases.index') }}">Pembelian</a></li>
                    <li><a href="{{ route('retail.orders.create') }}">Pemesanan</a></li>
                    <li><a href="{{ route('retail.kas.index') }}">Kas Masuk / Keluar</a></li>
                    <li><a href="{{ route('retail.expenses.index') }}">Biaya Operasional</a></li>
                    <li><a href="{{ route('retail.receivables.index') }}">Piutang</a></li>
                    <li><a href="{{ route('retail.payables.index') }}">Nota belum bayar</a></li>
                    <li><a href="{{ route('retail.returns.sales') }}">Retur Penjualan</a></li>
                    <li><a href="{{ route('retail.returns.purchases') }}">Retur Pembelian</a></li>
                    <li><a href="{{ route('retail.settlements.index') }}">Settlement Harian</a></li>
                    <li><a href="{{ route('retail.stock-adjustments.index') }}">Penyesuaian Stok</a></li>
                    <li><a href="{{ route('retail.opname.index') }}">Stok Opname</a></li>
                    <li><a href="{{ route('retail.points.index') }}">Tukar Poin</a></li>
                @endif
                @if($can('transfer'))
                    <li><a href="{{ route('inventory.str.index') }}">Transfer Stok (STR)</a></li>
                @endif
                <li><a href="{{ config('inventory.url') }}/pantauan.html">Pantauan gudang</a></li>
            </ul>
        </li>
        @endif

        @if($can('stock') || $can('store_reports') || $can('finance') || $can('pivot') || $can('holding'))
        <li class="menu-item menu-dropdown {{ request()->routeIs('reports.*', 'finance.*', 'executive.dashboard') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link" aria-expanded="false">
                <i class="fa fa-bar-chart"></i><span>Laporan</span>
            </a>
            <ul class="submenu">
                @if($can('store_reports'))
                    <li><a href="{{ route('reports.index') }}">Semua Laporan</a></li>
                    <li><a href="{{ route('reports.sales') }}">Penjualan</a></li>
                    <li><a href="{{ route('reports.sales-by-product') }}">Penjualan per Barang</a></li>
                    <li><a href="{{ route('reports.sales-by-payment') }}">Penjualan per Pembayaran</a></li>
                    <li><a href="{{ route('reports.sales-by-brand') }}">Penjualan per Brand</a></li>
                    <li><a href="{{ route('reports.purchases') }}">Pembelian</a></li>
                @endif
                @if($can('stock'))
                    <li><a href="{{ route('reports.stock') }}">Stok</a></li>
                @endif
                @if($can('store_reports'))
                    <li><a href="{{ route('reports.sales-profit') }}">Laba Penjualan</a></li>
                    <li><a href="{{ route('reports.ar-ap') }}">Hutang / Piutang</a></li>
                    <li><a href="{{ route('reports.recap') }}">Rekapitulasi</a></li>
                    <li><a href="{{ route('reports.member-sales') }}">Transaksi Member</a></li>
                    <li><a href="{{ route('reports.crew-sales') }}">Transaksi Crew</a></li>
                @endif
                @if($can('finance'))
                    <li><a href="{{ route('finance.income-statement') }}">Laba Rugi</a></li>
                    <li><a href="{{ route('finance.balance-sheet') }}">Neraca</a></li>
                    <li><a href="{{ route('finance.trial-balance') }}">Trial Balance</a></li>
                    <li><a href="{{ route('finance.inter-company.index') }}">Jurnal antar-PT</a></li>
                @endif
                @if($can('pivot'))
                    <li><a href="{{ route('reports.pivot') }}">Analisis Data</a></li>
                @endif
                @if($can('holding'))
                    <li><a href="{{ route('executive.dashboard') }}">Dashboard Holding</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if($can('po_pusat') || $can('rop') || $can('digital') || $can('to') || $can('budget'))
        <li class="menu-item menu-dropdown {{ request()->routeIs('procurement.*', 'digital.*', 'fieldsales.*', 'executive.budgets.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link" aria-expanded="false">
                <i class="fa fa-building"></i><span>ERP</span>
            </a>
            <ul class="submenu">
                @if($can('po_pusat'))
                    <li><a href="{{ route('procurement.po.index') }}">Pesanan gudang pusat</a></li>
                @endif
                @if($can('rop'))
                    <li><a href="{{ route('procurement.rop.index') }}">Stok di bawah batas</a></li>
                @endif
                @if($can('digital'))
                    <li><a href="{{ route('digital.orders.index') }}">Order digital</a></li>
                @endif
                @if($can('to'))
                    <li><a href="{{ route('fieldsales.outlets.index') }}">Sales TO</a></li>
                @endif
                @if($can('budget'))
                    <li><a href="{{ route('executive.budgets.index') }}">Program & budget</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if($can('utility'))
        <li class="menu-item {{ request()->routeIs('utility.*') ? 'active' : '' }}">
            <a href="{{ route('utility.index') }}" class="menu-link">
                <i class="fa fa-wrench"></i><span>Pemeliharaan</span>
            </a>
        </li>
        @endif

        <li class="menu-item">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="menu-link">
                    <i class="fa fa-sign-out"></i><span>Keluar</span>
                </button>
            </form>
        </li>
    </ul>
</aside>
