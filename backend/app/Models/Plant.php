<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
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
