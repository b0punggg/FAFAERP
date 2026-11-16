<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'mas_brg';

    protected $primaryKey = 'kd_brg';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'kd_brg',
        'kd_bar',
        'nm_brg',
        'kd_toko',
        'kd_kem1',
        'nm_kem1',
        'jum_kem1',
        'hrg_jum1',
        'kd_kem2',
        'nm_kem2',
        'jum_kem2',
        'hrg_jum2',
        'kd_kem3',
        'nm_kem3',
        'jum_kem3',
        'hrg_jum3',
        'jml_brg',
        'brg_msk',
        'brg_klr',
        'cetak',
        'pilih',
        'copy',
        'id_user',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'kd_toko', 'kd_toko');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'kd_brg', 'kd_brg');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'kd_brg', 'kd_brg');
    }
}

