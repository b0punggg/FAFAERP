<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crew extends Model
{
    protected $table = 'crew';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'kd_crew',
        'nm_crew',
        'nm_toko',
        'al_crew',
        'no_telp',
        'aktif',
        'kd_toko',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}
