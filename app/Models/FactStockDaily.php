<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactStockDaily extends Model
{
    protected $table = 'fact_stock_daily';

    protected $fillable = ['fact_date', 'warehouse_id', 'qty_total', 'value_total'];
}
