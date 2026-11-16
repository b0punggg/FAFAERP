<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'budget_id',
        'cost_center',
        'kegiatan',
        'uraian',
        'qty',
        'unit_price',
        'planned_amount',
        'actual_amount',
    ];
}
