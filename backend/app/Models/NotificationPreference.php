<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'tenant_id',
        'enabled',
        'channel',
        'email',
        'webhook_url',
        'webhook_secret',
        'severity_levels',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'severity_levels' => 'array',
        ];
    }

    public function isEnabledForSeverity(string $severity): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (empty($this->severity_levels)) {
            return true;
        }

        return in_array($severity, $this->severity_levels, true);
    }
}
