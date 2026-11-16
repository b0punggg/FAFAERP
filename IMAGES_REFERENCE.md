# Referensi Icon dan Gambar

Dokumen ini menjelaskan semua icon dan gambar yang tersedia di `public/assets/img/` dan penggunaannya.

## 📁 Lokasi
Semua gambar berada di: `public/assets/img/`

## 🎨 Icon dan Gambar yang Tersedia

### Logo & Branding
- `keranjang.png` - Logo utama (favicon & navbar)
- `keranjang_b.png` - Logo keranjang besar
- `logofafa.png` - Logo FAFA
- `logofafa2.png` - Logo FAFA alternatif
- `logo_login.jpg` - Background login page
- `logo6.png` - Logo alternatif

### Navigation & Menu
- `home.png` - Icon dashboard/home
- `menu.png` - Icon menu
- `master_data.png` - Icon master data
- `master_datak.png` - Icon master data (alternatif)
- `master_brg.png` - Icon master barang
- `master_brgk.png` - Icon master barang (alternatif)

### Transaksi
- `jual_brg.png` - Icon penjualan
- `terima_brg.png` - Icon pembelian/penerimaan barang
- `kasir_brg.png` - Icon kasir
- `gudang.png` - Icon gudang
- `sales.png` - Icon sales

### Utility
- `user3.png` - Icon user
- `login.png` - Icon login
- `logout.png` - Icon logout
- `search.png` - Icon search
- `searchico.png` - Icon search kecil
- `searchicok.png` - Icon search kecil (alternatif)
- `printer.png` - Icon printer
- `cetak_brg.png` - Icon cetak barang

### Action Buttons
- `backup.png` - Icon backup
- `backupk.png` - Icon backup (alternatif)
- `restore.png` - Icon restore
- `kembalik.png` - Icon kembali
- `box.png` - Icon box
- `catat.png` - Icon catat

### Status & Indicators
- `dlr.png` - Icon dollar
- `dlry.png` - Icon dollar (yellow)
- `dmp.png` - Icon dump
- `dmpy.png` - Icon dump (yellow)
- `krj.png` - Icon keranjang
- `krjy.png` - Icon keranjang (yellow)
- `supplierkk.png` - Icon supplier

### Loading & Animation
- `loading.gif` - Loading animation

### Other
- `simbol2.png` - Simbol
- `cloud-arrows.png` - Cloud arrows
- `inst1.png` - Instalasi
- `mel.png` - Mel
- `mmh.png` - MMH
- `NRL.png` - NRL
- `pass1.jpg` - Password
- `ps1.png` - PS1
- `TCGS2.bmp` - TCGS
- `tomexit2.png` - Tombol exit
- `tux.png` - Tux
- `val.jpg` - Val
- `xampp.png` - XAMPP (dan variasi xampp1-7.png)
- `yazlog.bmp` - Yaz log
- `yazlog1.png` - Yaz log PNG

## 💡 Cara Penggunaan

### Di Blade Template
```blade
<!-- Menggunakan asset() helper -->
<img src="{{ asset('assets/img/home.png') }}" alt="Home">

<!-- Dengan style inline -->
<img src="{{ asset('assets/img/user3.png') }}" alt="User" style="height: 20px;">

<!-- Di background CSS -->
<div style="background-image: url('{{ asset('assets/img/logo_login.jpg') }}');">
```

### Di CSS
```css
.icon-home {
    background-image: url('/assets/img/home.png');
    background-repeat: no-repeat;
    background-size: contain;
}
```

### Di JavaScript
```javascript
var iconPath = '/assets/img/search.png';
```

## 📝 Contoh Penggunaan di View

### Navbar dengan Icon
```blade
<a class="nav-link" href="{{ route('dashboard') }}">
    <img src="{{ asset('assets/img/home.png') }}" alt="Dashboard" style="height: 20px; margin-right: 5px;">
    Dashboard
</a>
```

### Button dengan Icon
```blade
<button type="submit" class="btn btn-primary">
    <img src="{{ asset('assets/img/search.png') }}" alt="Search" style="height: 16px; margin-right: 5px;">
    Cari
</button>
```

### Background Image
```blade
<div style="background-image: url('{{ asset('assets/img/logo_login.jpg') }}'); background-size: cover;">
    <!-- Content -->
</div>
```

## 🎯 Icon yang Sudah Digunakan

### Layout & Navigation
- ✅ `keranjang.png` - Favicon (di app.blade.php)
- ✅ `home.png` - Dashboard icon (di navbar)
- ✅ `master_brg.png` - Barang icon (di navbar)
- ✅ `jual_brg.png` - Penjualan icon (di navbar)
- ✅ `terima_brg.png` - Pembelian icon (di navbar)
- ✅ `user3.png` - User icon (di navbar)
- ✅ `logout.png` - Logout icon (di navbar)

### Login Page
- ✅ `logo_login.jpg` - Background login
- ✅ `user3.png` - User input icon
- ✅ `login.png` - Password input icon

## 🔄 Icon yang Bisa Ditambahkan

### Master Data Menu
- `master_data.png` - Untuk menu master data
- `master_brg.png` - Untuk menu master barang
- `supplierkk.png` - Untuk menu supplier

### Transaksi Menu
- `jual_brg.png` - Untuk menu penjualan
- `terima_brg.png` - Untuk menu pembelian
- `kasir_brg.png` - Untuk menu kasir
- `gudang.png` - Untuk menu gudang

### Action Buttons
- `search.png` - Untuk tombol search
- `printer.png` - Untuk tombol print
- `cetak_brg.png` - Untuk tombol cetak
- `backup.png` - Untuk tombol backup
- `restore.png` - Untuk tombol restore

## 📌 Tips

1. **Ukuran Icon**: Gunakan `height: 20px` untuk icon di navbar, `height: 16px` untuk icon di button
2. **Spacing**: Tambahkan `margin-right: 5px` untuk spacing antara icon dan text
3. **Alt Text**: Selalu tambahkan alt text untuk accessibility
4. **Path**: Selalu gunakan `asset('assets/img/...')` untuk generate URL yang benar
5. **Format**: Gunakan PNG untuk icon, JPG untuk foto/background

