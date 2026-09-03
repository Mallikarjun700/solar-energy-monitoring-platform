<?php

namespace App\Contracts\Notifications;

use App\Models\Alert;

interface NotificationChannel
{
    public function send(Alert $alert): void;
}
