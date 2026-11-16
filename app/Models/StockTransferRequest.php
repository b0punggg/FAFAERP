<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferRequest extends Model
{
    protected $fillable = [
        'str_no',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'requested_by',
        'approved_by',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
    }
}
