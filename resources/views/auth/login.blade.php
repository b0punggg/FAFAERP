<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — FAFA ERP</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/login.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fafa-modern.css') }}">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div style="text-align:center;margin-bottom:1.5rem">
                <div class="logo-mark" style="margin:0 auto 1rem;width:56px;height:56px;font-size:1.5rem;border-radius:14px">F</div>
                <h1>Selamat datang</h1>
                <p class="subtitle">Masuk ke FAFA Collection &amp; Gallery ERP</p>
            </div>

            @if(session('error'))
                <div class="alert-fafa alert-fafa-error" style="margin-bottom:1rem">{{ session('error') }}</div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="form-fafa">
                @csrf
                <div class="input-group-fafa">
                    <i class="fa fa-user"></i>
                    <input type="text" name="nm_user" placeholder="Nama pengguna" required autocomplete="username">
                </div>
                <div class="input-group-fafa">
                    <i class="fa fa-lock"></i>
                    <input type="password" name="pass" placeholder="Password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-login">Masuk ke sistem</button>
            </form>
            <p class="subtitle" style="margin-top:1.25rem;text-align:center">
                Gudang / packing:
                <a href="{{ config('inventory.url') }}/login.html">masuk aplikasi gudang</a>
            </p>
        </div>
    </div>
</body>
</html>
