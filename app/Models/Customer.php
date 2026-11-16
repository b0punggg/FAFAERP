<?php

namespace App\Models;

use App\Support\LegacyMasterSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';

    protected $primaryKey = 'kd_pel';

    public $incrementing = false;

    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'no_urut';
    }

    protected $fillable = [
        'kd_pel',
        'nm_pel',
        'al_pel',
        'telp_pel',
        'no_telp',
        'kd_toko',
    ];

    public function getTelpPelAttribute(?string $value): string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return (string) ($this->attributes['no_telp'] ?? '');
    }

    public function setTelpPelAttribute(?string $value): void
    {
        $value = $value ?? '';
        if (LegacyMasterSchema::hasColumn('pelanggan', 'telp_pel')) {
            $this->attributes['telp_pel'] = $value;
        }
        if (LegacyMasterSchema::hasColumn('pelanggan', 'no_telp')) {
            $this->attributes['no_telp'] = $value;
        }
    }

    public static function phoneColumn(): string
    {
        return LegacyMasterSchema::phoneColumn('pelanggan', 'telp_pel', 'no_telp');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'kd_pel', 'kd_pel');
    }
}

