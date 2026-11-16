<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandReport extends Model
{
    protected $table = 'brand_report';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'nm_brand',
    ];
}
