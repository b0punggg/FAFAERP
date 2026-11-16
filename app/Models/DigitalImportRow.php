<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalImportRow extends Model
{
    protected $fillable = [
        'digital_import_id',
        'sku',
        'spu',
        'product_name',
        'kd_brg',
        'orders_qty',
        'orders_net',
        'sku_qty',
        'sku_qty_wh',
        'sku_qty_wh_net',
        'dana_produk',
        'dana_valid',
        'dana_bersih',
        'dana_gudang',
        'cancel_orders',
        'cancel_amount',
        'cancel_rate',
        'refund_orders',
        'refund_amount',
        'refund_qty',
        'refund_rate',
        'subsidi',
        'diskon',
        'voucher',
        'koin',
        'subsidi_penjual',
        'diskon_penjual',
        'voucher_penjual',
        'diskon_marketplace',
        'voucher_marketplace',
        'stock_total',
        'stock_available',
        'stock_transit',
        'stock_allocated',
        'stock_promo',
        'report',
    ];

    protected $casts = [
        'report' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(DigitalImport::class, 'digital_import_id');
    }

    public function potongan(): float
    {
        return (float) $this->subsidi + (float) $this->diskon + (float) $this->voucher + (float) $this->koin;
    }
}
