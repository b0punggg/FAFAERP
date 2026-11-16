<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SfaVisit extends Model
{
    protected $fillable = ['sfa_outlet_id', 'sales_user_id', 'checked_in_at', 'latitude', 'longitude'];
}
