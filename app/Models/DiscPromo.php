<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscPromo extends Model
{
    protected $table = 'disc_promo';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'no_promo',
        'nama_promo',
        'tgl_awal',
        'tgl_akhir',
        'disc_rupiah',
        'disc_persen',
        'kd_bayar',
        'by_nama',
        'kd_toko',
    ];

    protected $casts = [
        'tgl_awal' => 'date',
        'tgl_akhir' => 'date',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(DiscPromoDetail::class, 'no_promo', 'no_promo');
    }
}
