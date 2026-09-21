<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'location',
        'capacity_kw',
        'status',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
