# Panduan Migrasi ke Laravel

Dokumen ini menjelaskan perubahan struktur dari aplikasi PHP biasa ke Laravel Framework.

## Perubahan Struktur

### 1. Direktori
- **Sebelum**: File PHP langsung di root dan folder `admin/`
- **Sesudah**: Struktur Laravel standar:
  - `app/` - Models, Controllers, Middleware
  - `config/` - File konfigurasi
  - `database/migrations/` - Migrations database
  - `resources/views/` - Blade templates
  - `routes/` - Route definitions
  - `public/` - Entry point aplikasi

### 2. Database
- Database baru: `fafa` (sama seperti sebelumnya)
- Semua tabel dibuat melalui migrations
- Struktur tabel dioptimalkan untuk Laravel

### 3. Authentication
- **Sebelum**: Session manual dengan `session_start()`
- **Sesudah**: Laravel Authentication dengan middleware

### 4. Routing
- **Sebelum**: File PHP langsung diakses (index.php, login_act.php)
- **Sesudah**: Routes didefinisikan di `routes/web.php`

### 5. Views
- **Sebelum**: HTML dengan PHP inline
- **Sesudah**: Blade templates dengan layout system

### 6. Models
- **Sebelum**: Query langsung dengan mysqli
- **Sesudah**: Eloquent ORM

## Mapping File Lama ke Baru

| File Lama | File Baru |
|-----------|-----------|
| `index.php` | `resources/views/auth/login.blade.php` + `app/Http/Controllers/Auth/LoginController.php` |
| `login_act.php` | `app/Http/Controllers/Auth/LoginController.php` |
| `admin/dasbor.php` | `resources/views/admin/dashboard.blade.php` + `app/Http/Controllers/DashboardController.php` |
| `admin/f_masbrg.php` | `resources/views/admin/products/index.blade.php` + `app/Http/Controllers/ProductController.php` |
| `admin/f_jual.php` | `resources/views/admin/sales/create.blade.php` + `app/Http/Controllers/SaleController.php` |
| `admin/config.php` | `config/database.php` + `.env` |
| Helper functions | `app/Helpers/Helper.php` |

## Langkah-langkah Setup

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Setup Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Konfigurasi Database**
   Edit `.env`:
   ```
   DB_DATABASE=fafa
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan Migrations**
   ```bash
   php artisan migrate
   ```

5. **Migrate Data (Opsional)**
   Jika ada data dari database lama, export dan import ke database baru.

6. **Start Server**
   ```bash
   php artisan serve
   ```

## Catatan Penting

- File assets (CSS, JS, images) tetap menggunakan folder `assets/` dan `admin/img/`
- Session data sekarang dikelola oleh Laravel
- Helper functions dari `admin/config.php` dipindah ke `app/Helpers/Helper.php`
- Semua query database sekarang menggunakan Eloquent ORM

## Fitur yang Sudah Dikonversi

✅ Authentication & Login
✅ Dashboard
✅ Master Barang (Products)
✅ Penjualan (Sales)
✅ Pembelian (Purchases)
✅ Models & Relationships
✅ Migrations Database

## Fitur yang Masih Perlu Dikonversi

- Laporan (Reports)
- Stok Opname
- Retur Jual/Beli
- Hutang/Piutang
- Setting/Configuration
- Print/Export functions

## Bantuan

Jika ada pertanyaan atau masalah, silakan buat issue di repository ini.

