<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    protected $table = 'paket_mas';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = ['nm_paket', 'kd_toko'];

    public function lines()
    {
        return $this->hasMany(PaketLine::class, 'kd_paket', 'no_urut');
    }
}
