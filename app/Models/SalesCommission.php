<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCommission extends Model
{
    protected $fillable = ['ar_settlement_id', 'sales_user_id', 'amount', 'status'];
}
