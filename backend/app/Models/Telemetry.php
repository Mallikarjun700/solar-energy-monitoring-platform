<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    protected $fillable = [
        'device_id',
        'recorded_at',
        'temperature',
        'voltage',
        'current',
        'power',
        'energy_generated',
        'status',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
