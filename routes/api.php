<?php

use App\Http\Controllers\Api\PosSyncController;
use App\Http\Controllers\Api\WarehouseDocumentController;
use App\Http\Controllers\FieldSales\SfaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/pos/sync', [PosSyncController::class, 'push']);
    Route::post('/sfa/check-in', [SfaController::class, 'checkIn']);
    Route::post('/warehouse/documents', [WarehouseDocumentController::class, 'store'])
        ->middleware('warehouse');
});
