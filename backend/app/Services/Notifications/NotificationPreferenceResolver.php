<?php

namespace App\Services\Notifications;

use App\Models\Alert;
use App\Models\NotificationPreference;

class NotificationPreferenceResolver
{
    public function resolve(Alert $alert): ?NotificationPreference
    {
        return NotificationPreference::query()
            ->where('tenant_id', $alert->tenant_id)
            ->first();
    }
}
