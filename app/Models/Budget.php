<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'year',
        'month',
        'period_start',
        'period_end',
        'tempat',
        'status',
        'plan_5w2h',
    ];

    protected $casts = [
        'plan_5w2h' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(BudgetNote::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draf',
            'submitted' => 'Diajukan',
            'validated' => 'Diperiksa',
            'approved' => 'Disetujui',
            default => (string) $this->status,
        };
    }
}
