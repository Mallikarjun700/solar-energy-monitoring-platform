<?php

namespace App\Listeners;

use App\Events\TelemetryEvent;

class HandleTelemetryEvent
{
    public function handle(TelemetryEvent $event): void
    {
        $payload = $event->payload;

        // Existing telemetry processing will go here.
    }
}