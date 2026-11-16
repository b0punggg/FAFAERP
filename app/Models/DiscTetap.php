<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscTetap extends Model
{
    protected $table = 'disctetap';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'kd_brg',
        'kd_sat',
        'hrg_jual',
        'lim_jual',
        'kd_toko',
    ];

    protected $casts = [
        'hrg_jual' => 'float',
        'lim_jual' => 'float',
    ];
}
