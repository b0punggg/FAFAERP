<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySettlement extends Model
{
    protected $fillable = [
        'branch_id',
        'settlement_date',
        'pos_cash_total',
        'physical_cash',
        'qris_total',
        'edc_total',
        'status',
        'verified_by',
    ];
}
