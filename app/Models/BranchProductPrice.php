<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchProductPrice extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'hrg_jum1',
        'hrg_jum2',
        'hrg_jum3',
        'hrg_beli',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'product_id');
    }

    public function priceForUnit(int $unit = 1): float
    {
        return (float) match ($unit) {
            2 => $this->hrg_jum2,
            3 => $this->hrg_jum3,
            default => $this->hrg_jum1,
        };
    }
}
