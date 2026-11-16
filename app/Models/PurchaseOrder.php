<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = ['company_id', 'warehouse_id', 'po_no', 'order_date', 'status'];
}
