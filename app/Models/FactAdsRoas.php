<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactAdsRoas extends Model
{
    protected $table = 'fact_ads_roas';

    protected $fillable = ['fact_date', 'company_id', 'spend', 'attributed_revenue'];
}
