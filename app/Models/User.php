<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'pemakai';

    protected $primaryKey = 'id_user';

    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'id_user';
    }

    protected $fillable = [
        'nm_user',
        'pass',
        'otoritas',
        'no_hp',
        'alamat',
        'foto',
        'kd_toko',
        'company_id',
        'branch_id',
    ];

    protected $hidden = [
        'pass',
    ];

    public function getAuthPassword()
    {
        return $this->pass;
    }

    public function setPassAttribute($value)
    {
        if ($value && ! str_starts_with($value, '$2y$')) {
            $this->attributes['pass'] = Hash::make($value);
        } else {
            $this->attributes['pass'] = $value;
        }
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'kd_toko', 'kd_toko');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('company_id');
    }

    public function hasPermission(string $slug): bool
    {
        if (in_array($this->otoritas, ['admin', 'direksi'], true)) {
            return true;
        }

        return $this->roles->flatMap->permissions->contains('slug', $slug);
    }

    public function canSwitchContext(): bool
    {
        return \App\Support\StaffAccess::can($this, 'switch');
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->canSwitchContext()) {
            return true;
        }

        return (int) $this->branch_id === $branchId;
    }
}
