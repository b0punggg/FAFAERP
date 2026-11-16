<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportFavorite extends Model
{
    protected $fillable = ['user_id', 'name', 'dataset', 'filters'];

    protected $casts = [
        'filters' => 'array',
    ];
}
