<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Device $device): void {
            if ($device->tenant_id === null && $device->asset_id !== null) {
                $device->tenant_id = Asset::query()
                    ->whereKey($device->asset_id)
                    ->value('tenant_id');
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'asset_id',
        'device_type',
        'serial_number',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function telemetry()
    {
        return $this->hasMany(Telemetry::class);
    }
}
