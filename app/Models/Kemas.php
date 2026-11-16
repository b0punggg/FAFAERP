<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kemas extends Model
{
    protected $table = 'kemas';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = ['nm_sat1', 'nm_sat2'];
}
