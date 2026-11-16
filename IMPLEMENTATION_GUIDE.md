# Panduan Implementasi Fitur Admin ke Laravel

Dokumen ini berisi panduan untuk mengkonversi semua fitur dari folder `admin` ke Laravel.

## Struktur yang Sudah Dibuat

### Migration
- ✅ `kemas` - Master kemasan
- ✅ `bag_brg` - Master bagian
- ✅ `paket_mas` - Master paket
- ✅ `paket_brg` - Detail paket barang

### Model (Perlu Dibuat)
- `Kemas` - untuk tabel kemas
- `Bagian` - untuk tabel bag_brg
- `Paket` - untuk tabel paket_mas
- `PaketItem` - untuk tabel paket_brg

## Langkah Implementasi

### 1. Buat Model
```bash
php artisan make:model Kemas
php artisan make:model Bagian
php artisan make:model Paket
php artisan make:model PaketItem
```

### 2. Buat Controller
```bash
php artisan make:controller KemasController
php artisan make:controller BagianController
php artisan make:controller PaketController
```

### 3. Buat Route
Tambahkan di `routes/web.php`:
```php
Route::prefix('master')->name('master.')->group(function () {
    Route::resource('kemas', KemasController::class);
    Route::resource('bagian', BagianController::class);
    Route::resource('paket', PaketController::class);
});
```

### 4. Buat View
- `resources/views/admin/master/kemas/index.blade.php`
- `resources/views/admin/master/bagian/index.blade.php`
- `resources/views/admin/master/paket/index.blade.php`

## Contoh Implementasi: KemasController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Kemas;
use Illuminate\Http\Request;

class KemasController extends Controller
{
    public function index()
    {
        $kemas = Kemas::orderBy('no_urut')->paginate(20);
        return view('admin.master.kemas.index', compact('kemas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nm_sat1' => 'required|string|max:50',
            'nm_sat2' => 'nullable|string|max:50',
        ]);

        Kemas::create([
            'nm_sat1' => strtoupper($request->nm_sat1),
            'nm_sat2' => $request->nm_sat2 ? strtoupper($request->nm_sat2) : null,
        ]);

        return redirect()->route('master.kemas.index')->with('success', 'Data berhasil disimpan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nm_sat1' => 'required|string|max:50',
            'nm_sat2' => 'nullable|string|max:50',
        ]);

        $kemas = Kemas::findOrFail($id);
        $kemas->update([
            'nm_sat1' => strtoupper($request->nm_sat1),
            'nm_sat2' => $request->nm_sat2 ? strtoupper($request->nm_sat2) : null,
        ]);

        return redirect()->route('master.kemas.index')->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        $kemas = Kemas::findOrFail($id);
        $kemas->delete();

        return redirect()->route('master.kemas.index')->with('success', 'Data berhasil dihapus');
    }
}
```

## Fitur yang Perlu Dikonversi (Prioritas)

### High Priority
1. ✅ Dashboard - Tingkatkan dengan chart dan top 10
2. ✅ Master Barang - Lengkapi dengan fitur cari, edit harga, barcode
3. ✅ Penjualan - Lengkapi dengan discount, paket, cetak nota
4. ✅ Pembelian - Lengkapi dengan semua fitur
5. Master Data - Kemasan, Bagian, Paket, Supplier, Pelanggan, Toko

### Medium Priority
6. Stok - Stok barang, stok opname, mutasi
7. Discount/Promo - Discount promo dan set discount
8. Hutang/Piutang - Pembayaran hutang dan piutang
9. Retur - Retur penjualan dan pembelian

### Low Priority
10. Kas - Kas masuk dan keluar
11. Laporan - Berbagai laporan
12. Utility - Backup, barcode, expired date, biaya

## Catatan Penting

1. **Struktur Database**: Beberapa tabel sudah ada di database lama, pastikan migration menggunakan `Schema::hasTable()` untuk mengecek apakah tabel sudah ada.

2. **Session**: Aplikasi lama menggunakan `$_SESSION`, di Laravel gunakan `session()` helper atau `Session` facade.

3. **Database Connection**: Aplikasi lama menggunakan `mysqli`, di Laravel gunakan Eloquent atau Query Builder.

4. **AJAX**: Banyak fitur menggunakan AJAX untuk pencarian dan pagination. Konversi ke Laravel menggunakan route API atau controller method yang return JSON.

5. **File Upload**: Pastikan konfigurasi `filesystems.php` dan `storage` sudah benar untuk upload file.

6. **Print/Export**: Fitur cetak dan export perlu dikonversi menggunakan library seperti DomPDF, Maatwebsite Excel, dll.

## Next Steps

1. Jalankan migration untuk tabel baru
2. Buat model untuk semua tabel
3. Buat controller untuk setiap fitur
4. Buat view menggunakan Blade template
5. Update route dan navbar
6. Test setiap fitur

