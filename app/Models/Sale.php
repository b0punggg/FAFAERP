<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $table = 'jual';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'no_fak',
        'tgl_jual',
        'kd_pel',
        'nm_pel',
        'kd_toko',
        'tot_jual',
        'disc',
        'ppn',
        'tot_bayar',
        'byr_tunai',
        'byr_hutang',
        'kd_bayar',
        'disc_nota',
        'voucher',
        'voucher_member',
        'diterima',
        'ket',
        'kd_member',
        'nm_member',
        'kd_crew',
        'nm_crew',
        'poin_earned',
        'poin_redeem',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'no_fak', 'no_fak');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'kd_toko', 'kd_toko');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'kd_pel', 'kd_pel');
    }
}

