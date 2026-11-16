<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FAFA POS') — @yield('subtitle', 'ERP Retail')</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/keranjang.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fafa-modern.css') }}">
    <script src="{{ asset('assets/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/Chart.min.js') }}"></script>
    @stack('styles')
</head>
<body class="fafa-app">
    @auth
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        @include('layouts.header')
        @include('layouts.sidebar')
    @endauth

    <div class="main-wrapper">
        @if(session('success'))
            <div class="alert-fafa alert-fafa-success" role="alert">
                <span><i class="fa fa-check-circle"></i> {{ session('success') }}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="background:none;border:none;font-size:1.25rem;cursor:pointer">&times;</button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert-fafa alert-fafa-error" role="alert">
                <span><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="background:none;border:none;font-size:1.25rem;cursor:pointer">&times;</button>
            </div>
        @endif

        @yield('content')
    </div>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        $(function () {
            function syncDropdownState() {
                $('.menu-dropdown').each(function () {
                    var open = $(this).hasClass('active');
                    $(this).children('.menu-link').attr('aria-expanded', open ? 'true' : 'false');
                });
            }

            $('.menu-dropdown > .menu-link').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $item = $(this).closest('.menu-dropdown');
                $('.menu-dropdown').not($item).removeClass('active');
                $item.toggleClass('active');
                syncDropdownState();
            });

            function setSidebarOpen(open) {
                $('#sidebar').toggleClass('open', open);
                $('#sidebarOverlay').toggleClass('show', open);
                $('body').toggleClass('sidebar-open', open);
                $('#menuToggle').attr('aria-expanded', open ? 'true' : 'false');
            }

            function closeSidebar() {
                setSidebarOpen(false);
            }

            $('#menuToggle').on('click', function (e) {
                e.stopPropagation();
                setSidebarOpen(!$('#sidebar').hasClass('open'));
            });
            $('#sidebarOverlay').on('click', closeSidebar);

            $('#sidebar .submenu a, #sidebar .menu-item:not(.menu-dropdown) > a.menu-link').on('click', function () {
                if (window.matchMedia('(max-width: 992px)').matches) {
                    closeSidebar();
                }
            });

            var here = window.location.pathname.replace(/\/$/, '');
            $('#sidebar .submenu a').each(function () {
                var path = this.pathname.replace(/\/$/, '');
                if (path && path === here) {
                    $(this).addClass('is-current');
                    $(this).closest('.menu-dropdown').addClass('active');
                }
            });
            syncDropdownState();
        });
    </script>
    @stack('scripts')
</body>
</html>
