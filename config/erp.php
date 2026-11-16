<?php

return [
    /*
    | Kode lokasi gudang pusat di tabel legacy `toko`.
    | Bukan gerai jual: tidak punya baris `branches`, hanya warehouse CENTRAL.
    */
    'central_kd_toko' => env('ERP_CENTRAL_KD_TOKO', 'PUSAT'),

    /*
    | Gudang transit/kanvas untuk Sales TO. Bukan gerai jual.
    */
    'kanvas_kd_toko' => env('ERP_KANVAS_KD_TOKO', 'KANVAS'),

    /*
    | Gudang packing marketplace. Bukan gerai jual: tidak punya branches.
    */
    'online_kd_toko' => env('ERP_ONLINE_KD_TOKO', 'ONLINE'),

    /*
    | Nama printer Windows untuk nota kasir. Dicoba berurutan.
    | Lebar 42 cocok untuk kertas thermal 80 mm.
    */
    'thermal_printers' => [
        'POS80',
        'GP-80220(Cut) Series',
        'GP-80250N Series',
        'POS-80C',
        'POS58 Printer',
        'ZJ-80',
    ],
    'thermal_width' => 42,
];
