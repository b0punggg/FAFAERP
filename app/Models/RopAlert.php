<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RopAlert extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'velocity_daily',
        'qty_on_hand',
        'reorder_point',
        'alerted_at',
    ];

    protected $casts = ['alerted_at' => 'datetime'];
}
