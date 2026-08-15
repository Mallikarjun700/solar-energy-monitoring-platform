<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemetryEvent extends Model
{    
    protected $connection = 'pgsql_telemetry';

    protected $table = 'telemetry_events';

    protected $fillable = [
        'event_id',
        'tenant_id',
        'source_id',
        'event_type',
        'event_timestamp',
        'received_at',
        'schema_version',
        'attributes',
        'payload',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'received_at' => 'datetime',
        'attributes' => 'array',
        'payload' => 'array',
    ];

    public $timestamps = false;
}