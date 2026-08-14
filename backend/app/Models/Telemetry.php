<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    protected $table = 'telemetries';

    public $timestamps = false;

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
        'temperature' => 'float',
        'voltage' => 'float',
        'current' => 'float',
        'power' => 'float',
        'energy_generated' => 'float',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
