<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $table = 'beli_brg';

    protected $primaryKey = 'no_urut';

    public $timestamps = false;

    protected $fillable = [
        'no_fak',
        'tgl_fak',
        'kd_brg',
        'kd_bar',
        'kd_sup',
        'kd_sat',
        'jml_brg',
        'hrg_beli',
        'disc1',
        'disc2',
        'ppn',
        'stok_jual',
        'kd_toko',
        'ket',
        'no_item',
        'no_item_del',
        'ket_mut',
        'id_bag',
        'expdate',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'no_fak', 'no_fak');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'kd_brg', 'kd_brg');
    }
}

