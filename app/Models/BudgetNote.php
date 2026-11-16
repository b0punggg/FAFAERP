<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetNote extends Model
{
    protected $fillable = ['budget_id', 'jenis', 'tgl', 'pelanggan', 'hasil'];

    protected $casts = ['tgl' => 'date'];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
