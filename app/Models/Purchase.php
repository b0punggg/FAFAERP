<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'beli';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'no_fak',
        'tgl_fak',
        'kd_sup',
        'nm_sup',
        'kd_toko',
        'tot_beli',
        'disc1',
        'disc2',
        'ppn',
        'tot_bayar',
        'kd_bayar',
        'byr_tunai',
        'byr_hutang',
        'tgl_jt',
        'ket',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'no_fak', 'no_fak');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'kd_toko', 'kd_toko');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kd_sup', 'kd_sup');
    }
}

