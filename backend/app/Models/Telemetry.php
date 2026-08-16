<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    protected $table = 'telemetry';
    protected $connection = 'mysql'; // important
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
}
