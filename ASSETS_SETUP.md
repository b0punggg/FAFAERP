# Setup Assets dan CSS

Dokumen ini menjelaskan setup assets dan CSS yang sudah diterapkan di aplikasi Laravel.

## ✅ Yang Sudah Diterapkan

### 1. Folder Assets
- ✅ Folder `assets` sudah di-copy ke `public/assets`
- ✅ Folder `admin/img` sudah di-copy ke `public/assets/img`
- ✅ Semua CSS dan JS files tersedia

### 2. CSS Files yang Digunakan
- ✅ `w3.css` - W3.CSS framework
- ✅ `bootstrap.min.css` - Bootstrap CSS
- ✅ `font-awesome.min.css` - Font Awesome icons
- ✅ `blue-themes.css` - Theme biru
- ✅ `alertyaz.min.css` - Alert notification
- ✅ `slick.css` - Slick carousel
- ✅ `slick-theme.css` - Slick theme

### 3. JavaScript Files yang Digunakan
- ✅ `jquery-3.3.1.min.js` - jQuery library
- ✅ `jquery.mask.min.js` - Input masking
- ✅ `bootstrap.min.js` - Bootstrap JS
- ✅ `alertyaz.min.js` - Alert notification
- ✅ `slick.min.js` - Slick carousel
- ✅ `Chart.min.js` - Chart.js untuk grafik
- ✅ External: recta.js dan qrious.js (dari CDN)

### 4. Custom Styles
- ✅ Font family: Helvetica, Arial
- ✅ Responsive font sizes
- ✅ Line height dan margin settings
- ✅ Media queries untuk mobile dan desktop

### 5. CSRF Token Setup
- ✅ Meta tag CSRF token di head
- ✅ AJAX setup untuk CSRF token

## 📁 Struktur Folder Assets

```
public/assets/
├── css/
│   ├── w3.css
│   ├── bootstrap.min.css
│   ├── font-awesome.min.css
│   ├── blue-themes.css
│   ├── alertyaz.min.css
│   ├── slick.css
│   └── ... (CSS lainnya)
├── js/
│   ├── jquery-3.3.1.min.js
│   ├── jquery.mask.min.js
│   ├── bootstrap.min.js
│   ├── alertyaz.min.js
│   ├── Chart.min.js
│   └── ... (JS lainnya)
├── fonts/
│   ├── fontawesome-webfont.*
│   ├── 3of9_new.ttf (barcode font)
│   └── ... (fonts lainnya)
└── img/
    ├── keranjang.png (favicon)
    ├── menu.png
    ├── home.png
    └── ... (images lainnya)
```

## 🔧 Penggunaan di View

### Di Layout (app.blade.php)
Semua CSS dan JS sudah di-include di layout utama, jadi semua view yang extend layout ini otomatis mendapatkan semua asset.

### Di View Spesifik
Jika perlu menambahkan CSS/JS tambahan di view tertentu, gunakan:

```blade
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/custom.js') }}"></script>
@endpush
```

## 📝 Catatan Penting

1. **Path Asset**: Semua asset menggunakan helper `asset()` untuk generate URL yang benar
2. **CSRF Token**: Sudah di-setup untuk AJAX requests
3. **jQuery Mask**: Sudah tersedia untuk input masking (format uang, tanggal, dll)
4. **Chart.js**: Sudah tersedia untuk membuat grafik di dashboard
5. **Alertyaz**: Sudah tersedia untuk notifikasi yang lebih menarik

## 🎨 CSS Classes yang Tersedia

- `.hrf_arial` - Font Arial
- `.hrf_times` - Font Times New Roman
- `.hrf_res`, `.hrf_res2`, `.hrf_res3` - Responsive font sizes
- Bootstrap classes (semua class Bootstrap tersedia)
- W3.CSS classes (semua class W3.CSS tersedia)
- Font Awesome icons (semua icon tersedia)

## 🚀 Next Steps

1. Gunakan asset yang sudah tersedia di semua view
2. Tambahkan CSS/JS tambahan jika diperlukan menggunakan `@push`
3. Pastikan semua image menggunakan path `asset('assets/img/...')`
4. Test semua fitur untuk memastikan asset ter-load dengan benar

