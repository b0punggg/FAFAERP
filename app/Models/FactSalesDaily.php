<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactSalesDaily extends Model
{
    protected $table = 'fact_sales_daily';

    protected $fillable = ['fact_date', 'company_id', 'branch_id', 'revenue', 'cogs'];
}
