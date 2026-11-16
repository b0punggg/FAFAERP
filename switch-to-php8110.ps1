# Script untuk mengaktifkan PHP 8.1.10 di Laragon
# Jalankan sebagai Administrator

Write-Host "=== Mengaktifkan PHP 8.1.10 di Laragon ===" -ForegroundColor Green
Write-Host ""

# Cek apakah script dijalankan sebagai Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "PERINGATAN: Script ini perlu dijalankan sebagai Administrator!" -ForegroundColor Yellow
    Write-Host "Klik kanan PowerShell dan pilih 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Tekan Enter untuk melanjutkan (atau Ctrl+C untuk membatalkan)"
}

# Path PHP yang diinginkan
$php8110Path = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64"
$php8110Exe = "$php8110Path\php.exe"

# Cek apakah PHP 8.1.10 ada
if (-not (Test-Path $php8110Exe)) {
    Write-Host "ERROR: PHP 8.1.10 tidak ditemukan di: $php8110Path" -ForegroundColor Red
    Write-Host "Pastikan Laragon sudah terinstall dengan PHP 8.1.10" -ForegroundColor Yellow
    exit 1
}

Write-Host "PHP 8.1.10 ditemukan di: $php8110Path" -ForegroundColor Green

# Ambil PATH saat ini dari System Environment
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")

# Hapus semua versi PHP lama dari PATH
$phpVersions = @(
    "C:\laragon\bin\php\php-5.6.34-Win32-VC11-x64",
    "C:\laragon\bin\php\php-5.6.4-Win32-VC11-x64",
    "C:\laragon\bin\php\php-8.4.14-Win32-vs17-x64"
)

$newPath = $currentPath
foreach ($phpVersion in $phpVersions) {
    $newPath = $newPath -replace [regex]::Escape($phpVersion) + ";?", ""
    $newPath = $newPath -replace ";" + [regex]::Escape($phpVersion), ""
}

# Tambahkan PHP 8.1.10 di awal PATH (jika belum ada)
if ($newPath -notlike "*$php8110Path*") {
    $newPath = "$php8110Path;$newPath"
    Write-Host "Menambahkan PHP 8.1.10 ke PATH..." -ForegroundColor Yellow
} else {
    Write-Host "PHP 8.1.10 sudah ada di PATH" -ForegroundColor Green
    # Pastikan di posisi pertama
    $newPath = $newPath -replace [regex]::Escape($php8110Path) + ";?", ""
    $newPath = "$php8110Path;$newPath"
}

# Set PATH baru
try {
    [Environment]::SetEnvironmentVariable("Path", $newPath, "Machine")
    Write-Host "PATH berhasil diupdate!" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Gagal mengupdate PATH: $_" -ForegroundColor Red
    exit 1
}

# Update PATH untuk session saat ini juga
$env:Path = "$php8110Path;" + ($env:Path -replace [regex]::Escape($php8110Path) + ";?", "")

Write-Host ""
Write-Host "=== Verifikasi ===" -ForegroundColor Cyan
$phpVersion = & "$php8110Exe" -v 2>&1 | Select-Object -First 1
Write-Host "Versi PHP aktif: $phpVersion" -ForegroundColor Green

Write-Host ""
Write-Host "=== SELESAI ===" -ForegroundColor Green
Write-Host "PHP 8.1.10 telah diaktifkan!" -ForegroundColor Green
Write-Host ""
Write-Host "PENTING:" -ForegroundColor Yellow
Write-Host "1. Tutup semua terminal/PowerShell yang sedang terbuka" -ForegroundColor Yellow
Write-Host "2. Buka terminal/PowerShell baru" -ForegroundColor Yellow
Write-Host "3. Jalankan 'php -v' untuk verifikasi" -ForegroundColor Yellow
Write-Host ""
Write-Host "Atau restart Laragon (Stop All -> Start All)" -ForegroundColor Yellow

