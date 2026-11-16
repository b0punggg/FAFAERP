<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'jual_brg';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'no_fak',
        'tgl_jual',
        'kd_brg',
        'nm_brg',
        'kd_sat',
        'nm_sat',
        'jml_brg',
        'hrg_jual',
        'disc',
        'ppn',
        'tot_jual',
        'kd_toko',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'no_fak', 'no_fak');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'kd_brg', 'kd_brg');
    }
}

