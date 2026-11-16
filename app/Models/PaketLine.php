<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaketLine extends Model
{
    protected $table = 'paket_brg';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'kd_paket',
        'kd_brg',
        'qty_brg',
        'kd_sat',
        'kd_toko',
    ];

    public function paket(): BelongsTo
    {
        return $this->belongsTo(Paket::class, 'kd_paket', 'no_urut');
    }
}
