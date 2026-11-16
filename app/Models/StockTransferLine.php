<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['stock_transfer_request_id', 'product_id', 'qty'];
}
