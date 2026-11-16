<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    protected $fillable = ['digital_order_id', 'affiliate_code', 'amount', 'status'];
}
