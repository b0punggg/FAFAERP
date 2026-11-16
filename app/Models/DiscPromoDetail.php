<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscPromoDetail extends Model
{
    protected $table = 'disc_promo_detail';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'no_promo',
        'kd_brg',
        'disc_rupiah',
        'disc_persen',
        'kd_toko',
    ];
}
