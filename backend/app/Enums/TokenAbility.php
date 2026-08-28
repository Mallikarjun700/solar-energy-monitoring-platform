<?php

namespace App\Enums;

enum TokenAbility: string
{
    case TELEMETRY_WRITE = 'telemetry:write';
    case TELEMETRY_READ = 'telemetry:read';
    case DLQ_READ = 'dlq:read';
    case DLQ_REPLAY = 'dlq:replay';
    case ALERTS_READ = 'alerts:read';
    case ALERTS_ACKNOWLEDGE = 'alerts:acknowledge';
    case ALERTS_RESOLVE = 'alerts:resolve';
}