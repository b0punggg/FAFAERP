# FAFA POS System - Laravel Version

Sistem Point of Sale (POS) untuk FAFA Collection & Gallery yang telah dikonversi ke Laravel Framework dengan PHP 8.1.10.

## Persyaratan

- PHP >= 8.1.10
- Composer
- MySQL/MariaDB
- Laravel 9.x

## Instalasi

1. Install dependencies:
```bash
composer install
```

2. Copy file `.env.example` ke `.env`:
```bash
cp .env.example .env
```

3. Generate application key:
```bash
php artisan key:generate
```

4. Konfigurasi database di file `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fafa
DB_USERNAME=root
DB_PASSWORD=
```

5. Jalankan migrations:
```bash
php artisan migrate
```

6. (Opsional) Jalankan seeder untuk data awal:
```bash
php artisan db:seed
```

7. Start development server:
```bash
php artisan serve
```

## Struktur Database

Aplikasi menggunakan database baru dengan struktur yang telah dioptimalkan untuk Laravel. Tabel utama:

- `toko` - Data toko/store
- `pemakai` - Data user/pengguna
- `mas_brg` - Master barang/produk
- `beli` - Header pembelian
- `beli_brg` - Detail pembelian
- `jual` - Header penjualan
- `jual_brg` - Detail penjualan
- `pelanggan` - Data pelanggan
- `supplier` - Data supplier

## Fitur Utama

- ✅ Authentication & Authorization
- ✅ Dashboard dengan statistik
- ✅ Master Barang (CRUD)
- ✅ Penjualan (Sales)
- ✅ Pembelian (Purchases)
- ✅ Manajemen Stok
- ✅ Laporan

## Catatan

- File assets (CSS, JS, images) tetap menggunakan folder `assets/` dan `admin/img/` yang sudah ada
- Session management menggunakan Laravel session
- Helper functions dari aplikasi lama dapat ditemukan di `app/Helpers/`

## License

MIT

