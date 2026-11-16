<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $table = 'toko';

    protected $primaryKey = 'kd_toko';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'kd_toko',
        'nm_toko',
        'al_toko',
        'no_urut',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'kd_toko', 'kd_toko');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'kd_toko', 'kd_toko');
    }
}

