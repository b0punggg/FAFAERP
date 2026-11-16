<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SfaOutlet extends Model
{
    protected $fillable = ['company_id', 'code', 'name', 'credit_limit', 'latitude', 'longitude'];
}
