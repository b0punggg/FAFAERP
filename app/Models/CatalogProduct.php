<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogProduct extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'kd_kem1',
        'nm_kem1',
        'jum_kem1',
        'kd_kem2',
        'nm_kem2',
        'jum_kem2',
        'kd_kem3',
        'nm_kem3',
        'jum_kem3',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchProductPrice::class, 'product_id');
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'product_id');
    }
}
