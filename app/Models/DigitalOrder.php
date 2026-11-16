<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalOrder extends Model
{
    protected $fillable = ['company_id', 'channel', 'external_order_id', 'status', 'total'];

    public function lines(): HasMany
    {
        return $this->hasMany(DigitalOrderLine::class);
    }
}
