<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';

    protected $primaryKey = 'kd_sup';

    public $incrementing = false;

    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'no_urut';
    }

    protected $fillable = [
        'kd_sup',
        'nm_sup',
        'al_sup',
        'no_telp',
        'nm_sales',
        'telp_sup',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'kd_sup', 'kd_sup');
    }

    public function getTelpSupAttribute(): ?string
    {
        return $this->attributes['telp_sup'] ?? $this->attributes['no_telp'] ?? null;
    }
}

