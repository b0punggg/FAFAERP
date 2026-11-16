<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = ['company_id', 'code', 'name', 'type', 'is_postable'];

    protected $casts = ['is_postable' => 'boolean'];
}
