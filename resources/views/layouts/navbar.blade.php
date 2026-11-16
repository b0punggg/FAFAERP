<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/img/keranjang.png') }}" alt="FAFA POS" style="height: 30px; margin-right: 10px;">
            FAFA POS
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <img src="{{ asset('assets/img/home.png') }}" alt="Dashboard" style="height: 20px; margin-right: 5px;">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('products.index') }}">
                        <img src="{{ asset('assets/img/master_brg.png') }}" alt="Barang" style="height: 20px; margin-right: 5px;">
                        Barang
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('sales.index') }}">
                        <img src="{{ asset('assets/img/jual_brg.png') }}" alt="Penjualan" style="height: 20px; margin-right: 5px;">
                        Penjualan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('purchases.index') }}">
                        <img src="{{ asset('assets/img/terima_brg.png') }}" alt="Pembelian" style="height: 20px; margin-right: 5px;">
                        Pembelian
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                        <img src="{{ asset('assets/img/user3.png') }}" alt="User" style="height: 20px; margin-right: 5px; border-radius: 50%;">
                        {{ session('nm_user', 'User') }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <img src="{{ asset('assets/img/logout.png') }}" alt="Logout" style="height: 16px; margin-right: 5px;">
                                Logout
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

