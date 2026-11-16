<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDraft extends Model
{
    protected $fillable = [
        'kd_toko',
        'user_id',
        'no_fak',
        'tgl_jual',
        'payload',
    ];

    protected $casts = [
        'tgl_jual' => 'date',
        'payload' => 'array',
    ];
}
