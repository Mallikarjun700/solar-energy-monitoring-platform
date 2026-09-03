<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    protected $table = 'telemetry';

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
    ];

    public function setRecordedAtAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['recorded_at'] = null;

            return;
        }

        $this->attributes['recorded_at'] = Carbon::parse($value)
            ->setMicrosecond(0)
            ->format('Y-m-d H:i:s');
    }
}
