<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    public const TYPE_OPNAME = 'opname';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_SALE_VOID = 'sale_void';

    public const TYPE_SALE_RETURN = 'sale_return';

    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_type',
        'qty_delta',
        'qty_on_hand_after',
        'reference_type',
        'reference_id',
        'sku',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'product_id');
    }
}
