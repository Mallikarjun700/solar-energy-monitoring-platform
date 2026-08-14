<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'name',
        'asset_type',
        'serial_number',
        'status',
        'location',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
