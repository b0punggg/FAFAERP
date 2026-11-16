<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SfaOrderLine extends Model
{
    protected $fillable = ['sfa_order_id', 'kd_brg', 'qty'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SfaOrder::class, 'sfa_order_id');
    }
}
