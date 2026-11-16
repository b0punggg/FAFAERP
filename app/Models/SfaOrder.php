<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SfaOrder extends Model
{
    protected $fillable = ['sfa_outlet_id', 'status', 'total'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(SfaOutlet::class, 'sfa_outlet_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SfaOrderLine::class);
    }
}
