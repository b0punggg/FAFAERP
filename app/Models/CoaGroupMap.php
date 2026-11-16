<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoaGroupMap extends Model
{
    protected $fillable = ['group_code', 'company_id', 'chart_of_account_id'];
}
