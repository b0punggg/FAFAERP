# Cara Mengaktifkan PHP 8.1.10 di Laragon

## Metode 1: Melalui Menu Laragon (Paling Mudah)

1. **Klik kanan pada icon Laragon** di system tray (bawah kanan layar)
2. Pilih **"PHP"** → **"php-8.1.10-Win32-vs16-x64"**
3. Laragon akan restart otomatis
4. Verifikasi dengan: `php -v`

## Metode 2: Melalui Laragon Menu

1. Buka **Laragon**
2. Klik menu **"Menu"** di toolbar
3. Pilih **"PHP"** → **"php-8.1.10-Win32-vs16-x64"**
4. Klik **"Stop All"** lalu **"Start All"**

## Metode 3: Manual (Jika metode di atas tidak bekerja)

### A. Ubah PATH Environment Variable

1. Buka **System Properties** → **Environment Variables**
2. Di **System Variables**, cari **"Path"**
3. Edit dan pastikan path ini ada di paling atas:
   ```
   C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64
   ```
4. Hapus atau pindah ke bawah path PHP 5.6.34:
   ```
   C:\laragon\bin\php\php-5.6.34-Win32-VC11-x64
   ```
5. Klik **OK** dan restart terminal/PowerShell

### B. Atau gunakan script PowerShell

Jalankan di PowerShell sebagai Administrator:

```powershell
# Backup PATH saat ini
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")

# Hapus PHP 5.6.34 dari PATH
$newPath = $currentPath -replace "C:\\laragon\\bin\\php\\php-5.6.34-Win32-VC11-x64;?", ""

# Tambahkan PHP 8.1.10 di awal PATH
$php8110Path = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64"
if ($newPath -notlike "*$php8110Path*") {
    $newPath = "$php8110Path;$newPath"
}

# Set PATH baru
[Environment]::SetEnvironmentVariable("Path", $newPath, "Machine")

Write-Host "PHP 8.1.10 telah diaktifkan. Silakan restart terminal."
```

## Verifikasi

Setelah mengaktifkan, verifikasi dengan:

```powershell
php -v
```

Harus menampilkan:
```
PHP 8.1.10 (cli) ...
```

## Catatan

- Setelah mengubah versi PHP, **restart terminal/PowerShell** yang sedang digunakan
- Jika masih menampilkan versi lama, tutup semua terminal dan buka yang baru
- Pastikan Laragon sudah **Start All** setelah mengubah versi PHP

