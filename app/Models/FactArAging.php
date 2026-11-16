<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactArAging extends Model
{
    protected $table = 'fact_ar_aging';

    protected $fillable = ['as_of_date', 'company_id', 'bucket_0_30', 'bucket_31_60', 'bucket_61_plus'];
}
