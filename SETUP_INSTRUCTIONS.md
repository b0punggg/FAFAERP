# Instruksi Setup Laravel - FAFA POS System

## ✅ Yang Sudah Dikerjakan

### 1. Struktur Laravel Lengkap
- ✅ Direktori standar Laravel (app, config, database, resources, routes, dll)
- ✅ File konfigurasi (app.php, database.php, auth.php, dll)
- ✅ Bootstrap dan entry point (artisan, public/index.php)

### 2. Database & Migrations
- ✅ Migration untuk tabel `toko` (stores)
- ✅ Migration untuk tabel `pemakai` (users)
- ✅ Migration untuk tabel `mas_brg` (products)
- ✅ Migration untuk tabel `pelanggan` (customers)
- ✅ Migration untuk tabel `supplier` (suppliers)
- ✅ Migration untuk tabel `beli` dan `beli_brg` (purchases)
- ✅ Migration untuk tabel `jual` dan `jual_brg` (sales)

### 3. Models (Eloquent)
- ✅ User Model
- ✅ Store Model
- ✅ Product Model
- ✅ Sale & SaleItem Models
- ✅ Purchase & PurchaseItem Models
- ✅ Customer Model
- ✅ Supplier Model
- ✅ Relationships antar models

### 4. Controllers
- ✅ LoginController (Authentication)
- ✅ DashboardController
- ✅ ProductController (CRUD Products)
- ✅ SaleController (Sales Management)
- ✅ PurchaseController (Purchase Management)

### 5. Routes
- ✅ Web routes dengan authentication middleware
- ✅ Route untuk login/logout
- ✅ Route untuk dashboard
- ✅ Route untuk products, sales, purchases

### 6. Views (Blade Templates)
- ✅ Layout utama (app.blade.php)
- ✅ Navbar component
- ✅ Login page
- ✅ Dashboard page

### 7. Middleware & Security
- ✅ Authentication middleware
- ✅ CSRF protection
- ✅ Session management
- ✅ Cookie encryption

### 8. Helper Functions
- ✅ Helper class untuk format number, date, terbilang, dll
- ✅ Barcode generator
- ✅ Utility functions

## 📋 Langkah-langkah Setup

### 1. Pastikan PHP 8.1.10 Terinstall
```bash
php -v
```
Harus menampilkan PHP 8.1.10 atau lebih tinggi.

### 2. Install Composer Dependencies
```bash
composer install
```

Jika composer belum terinstall, download dari: https://getcomposer.org/

### 3. Setup Environment
```bash
# Copy file .env.example ke .env
copy .env.example .env

# Atau di Linux/Mac:
# cp .env.example .env
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Konfigurasi Database
Edit file `.env` dan sesuaikan:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fafa
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Buat Database Baru
Buat database baru dengan nama `fafa` di MySQL:
```sql
CREATE DATABASE fafa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Jalankan Migrations
```bash
php artisan migrate
```

Ini akan membuat semua tabel di database.

### 8. (Opsional) Import Data Lama
Jika ada data dari database lama, export dan import ke database baru.

### 9. Setup Storage Link (untuk file uploads)
```bash
php artisan storage:link
```

### 10. Start Development Server
```bash
php artisan serve
```

Aplikasi akan berjalan di: http://localhost:8000

## 🔐 Login Pertama Kali

Setelah migrations selesai, buat user pertama secara manual di database:

```sql
INSERT INTO pemakai (nm_user, pass, otoritas, kd_toko) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'TOKO001');
```

Password default: `password`

**PENTING**: Ganti password setelah login pertama!

## 📁 Struktur File Penting

```
fafa/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Semua controllers
│   │   └── Middleware/      # Middleware
│   ├── Models/              # Eloquent models
│   └── Helpers/             # Helper functions
├── config/                  # Konfigurasi
├── database/
│   └── migrations/          # Database migrations
├── resources/
│   └── views/               # Blade templates
├── routes/
│   └── web.php              # Web routes
├── public/                  # Entry point
│   └── index.php
├── .env                     # Environment config
└── composer.json            # Dependencies
```

## 🎯 Fitur yang Sudah Siap

1. ✅ **Authentication System**
   - Login/Logout
   - Session management
   - Password hashing

2. ✅ **Dashboard**
   - Statistik inventory
   - Penjualan hari ini
   - Stok habis/menipis

3. ✅ **Master Barang**
   - CRUD products
   - Search products
   - Multi-unit packaging

4. ✅ **Penjualan**
   - Create sales
   - Auto stock deduction
   - Discount calculation

5. ✅ **Pembelian**
   - Create purchases
   - Auto stock addition
   - Multi-discount support

## ⚠️ Catatan Penting

1. **Assets**: File CSS, JS, dan images tetap menggunakan folder `assets/` dan `admin/img/` yang sudah ada.

2. **Database Baru**: Aplikasi menggunakan database baru dengan struktur yang dioptimalkan. Data lama perlu di-migrate secara manual.

3. **Session**: Session sekarang dikelola oleh Laravel, bukan PHP native.

4. **Helper Functions**: Fungsi helper dari `admin/config.php` dipindah ke `app/Helpers/Helper.php`.

## 🔄 Migrasi Data dari Aplikasi Lama

Jika ingin memigrasikan data dari aplikasi lama:

1. Export data dari database lama
2. Sesuaikan format jika perlu
3. Import ke database baru
4. Pastikan foreign keys dan relationships benar

## 🐛 Troubleshooting

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "No application encryption key"
```bash
php artisan key:generate
```

### Error: "Table doesn't exist"
```bash
php artisan migrate
```

### Error: "Permission denied" (Linux/Mac)
```bash
chmod -R 775 storage bootstrap/cache
```

## 📞 Support

Jika ada masalah atau pertanyaan, silakan buat issue atau hubungi developer.

---

**Selamat! Aplikasi Laravel sudah siap digunakan! 🎉**

