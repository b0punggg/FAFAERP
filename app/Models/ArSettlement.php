<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArSettlement extends Model
{
    protected $fillable = ['sfa_outlet_id', 'payment_date', 'amount'];
}
