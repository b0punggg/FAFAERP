<?php

namespace App\Helpers;

class Helper
{
    /**
     * Format number dengan pemisah ribuan
     */
    public static function formatNumber($number)
    {
        return number_format($number, 0, ',', '.');
    }

    /**
     * Format number dengan desimal
     */
    public static function formatDecimal($number, $decimals = 2)
    {
        return number_format($number, $decimals, ',', '.');
    }

    /**
     * Konversi tanggal dari format database ke format Indonesia
     */
    public static function formatDate($date, $format = 'd-m-Y')
    {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }

    /**
     * Konversi tanggal dari format Indonesia ke format database
     */
    public static function parseDate($date)
    {
        if (empty($date)) return null;
        $parts = explode('-', $date);
        if (count($parts) == 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return $date;
    }

    /**
     * Generate barcode code39
     */
    public static function generateBarcode($str)
    {
        $a = [
            "A" => "1", "B" => "2", "C" => "3", "D" => "4", "E" => "5",
            "F" => "6", "G" => "7", "H" => "8", "I" => "9", "J" => "0",
            "K" => "9", "L" => "8", "M" => "7", "N" => "6", "O" => "5",
            "P" => "4", "Q" => "3", "R" => "2", "S" => "1", "T" => "0",
            "U" => "1", "V" => "2", "W" => "3", "X" => "2", "Y" => "1", "Z" => "0"
        ];

        $b = [
            "1" => "A", "2" => "B", "3" => "C", "4" => "D", "5" => "E",
            "6" => "F", "7" => "G", "8" => "H", "9" => "I", "0" => "J"
        ];

        date_default_timezone_set('Asia/Jakarta');
        $maks = 4;
        $kdbar = "";
        $strkon_a = $str;
        $dat = substr(str_replace(":", "", date("H:m:s", time() * rand(1, 100))), 2, 4);
        $pattern = "/[\W_0-9]/i";
        $strkon = preg_replace($pattern, "", trim(strtoupper($str)));

        if (strlen($strkon) >= 1) {
            if (strlen($strkon) > $maks) {
                $kdbar = "";
                for ($x = 0; $x <= $maks - 1; $x++) {
                    $kdbar = $kdbar . $a[substr($strkon, $x, 1)];
                }
            } else {
                $kdbar = "";
                $len = strlen($strkon);
                for ($c = 0; $c <= $len - 1; $c++) {
                    $kdbar = $kdbar . $a[substr($strkon, $c, 1)];
                }
                $kdbar = str_pad($kdbar, 4, rand(0, 9), STR_PAD_LEFT);
            }
            return $dat . $kdbar;
        } else {
            $maks = 4;
            if (strlen($strkon_a) > $maks) {
                $kdbar = "";
                $kdbar1 = "";
                for ($x = 0; $x <= 3; $x++) {
                    $kdbar = $kdbar . $b[substr($strkon_a, $x, 1)];
                }
                for ($x = 0; $x <= 3; $x++) {
                    $kdbar1 = $kdbar1 . $a[substr($kdbar, $x, 1)];
                }
            } else {
                $kdbar = "";
                $kdbar1 = "";
                $len = strlen($strkon_a);
                for ($c = 0; $c <= $len - 1; $c++) {
                    $kdbar = $kdbar . $b[substr($strkon_a, $c, 1)];
                }
                for ($cc = 0; $cc <= $len - 1; $cc++) {
                    $kdbar1 = $kdbar1 . $a[substr($kdbar, $cc, 1)];
                }
            }
            $kdbar1 = str_pad($kdbar1, 6, rand(1, 9), STR_PAD_LEFT);
            return $kdbar1 . $dat;
        }
    }

    /**
     * Terbilang dalam bahasa Indonesia
     */
    public static function terbilang($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        $temp = "";

        if ($nilai < 12) {
            $temp = " " . $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = self::terbilang($nilai - 10) . " Belas";
        } else if ($nilai < 100) {
            $temp = self::terbilang($nilai / 10) . " Puluh" . self::terbilang($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . self::terbilang($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = self::terbilang($nilai / 100) . " Ratus" . self::terbilang($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . self::terbilang($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = self::terbilang($nilai / 1000) . " Ribu" . self::terbilang($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = self::terbilang($nilai / 1000000) . " Juta" . self::terbilang($nilai % 1000000);
        }

        return trim($temp) . " Rupiah";
    }

    /**
     * Nama hari dalam bahasa Indonesia
     */
    public static function namaHari($tanggal)
    {
        $hari = date("D", strtotime($tanggal));
        $hariIndo = [
            'Sun' => 'MINGGU',
            'Mon' => 'SENIN',
            'Tue' => 'SELASA',
            'Wed' => 'RABU',
            'Thu' => 'KAMIS',
            'Fri' => 'JUMAT',
            'Sat' => 'SABTU'
        ];
        return $hariIndo[$hari] ?? '';
    }
}

