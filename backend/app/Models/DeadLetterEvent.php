<?php

namespace App\Models;

use App\Enums\DeadLetterStatus;
use Illuminate\Database\Eloquent\Model;

class DeadLetterEvent extends Model
{
    protected $fillable = [
        'event_id',
        'device_id',
        'original_payload',
        'error_type',
        'failure_reason',
        'attempt_count',
        'first_failed_at',
        'last_failed_at',
        'status',
    ];

    protected $casts = [
        'original_payload' => 'array',
        'first_failed_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'status' => DeadLetterStatus::class,
    ];
}
