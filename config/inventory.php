<?php

return [
    'url' => rtrim((string) env('INVENTORY_APP_URL', 'http://localhost:3000'), '/'),
    'central_kd_toko' => env('ERP_CENTRAL_KD_TOKO', 'PUSAT'),
];
