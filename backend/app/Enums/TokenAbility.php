<?php

namespace App\Enums;

enum TokenAbility: string
{
    case TELEMETRY_WRITE = 'telemetry:write';
    case TELEMETRY_READ = 'telemetry:read';
    case DLQ_READ = 'dlq:read';
    case DLQ_REPLAY = 'dlq:replay';
}