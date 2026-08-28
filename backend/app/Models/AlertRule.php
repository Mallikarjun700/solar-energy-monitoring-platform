<?php

namespace App\Models;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'metric',
        'operator',
        'threshold',
        'severity',
        'alert_type',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'operator' => AlertOperator::class,
            'severity' => AlertSeverity::class,
            'threshold' => 'decimal:4',
            'enabled' => 'boolean',
        ];
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'rule_id');
    }
}