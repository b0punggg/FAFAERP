<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'kd_member',
        'nm_member',
        'nm_toko',
        'al_member',
        'no_telp',
        'poin',
        'tgl_daftar',
        'kd_toko',
    ];

    protected $casts = [
        'poin' => 'float',
        'tgl_daftar' => 'date',
    ];
}
