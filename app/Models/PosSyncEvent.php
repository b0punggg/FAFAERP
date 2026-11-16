<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSyncEvent extends Model
{
    protected $fillable = [
        'branch_id',
        'idempotency_key',
        'event_type',
        'payload',
        'status',
        'error_message',
    ];

    protected $casts = ['payload' => 'array'];
}
