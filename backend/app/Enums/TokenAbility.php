<?php

namespace App\Enums;

enum TokenAbility: string
{
    case TELEMETRY_WRITE = 'telemetry:write';
    case TELEMETRY_READ = 'telemetry:read';

    case PLANTS_READ = 'plants:read';
    case PLANTS_WRITE = 'plants:write';

    case ASSETS_READ = 'assets:read';
    case ASSETS_WRITE = 'assets:write';

    case DEVICES_READ = 'devices:read';
    case DEVICES_WRITE = 'devices:write';

    case DLQ_READ = 'dlq:read';
    case DLQ_REPLAY = 'dlq:replay';

    case ALERTS_READ = 'alerts:read';
    case ALERTS_ACKNOWLEDGE = 'alerts:acknowledge';
    case ALERTS_RESOLVE = 'alerts:resolve';
}
