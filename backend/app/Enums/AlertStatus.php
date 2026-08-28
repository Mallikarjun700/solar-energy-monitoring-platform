<?php

namespace App\Enums;

enum AlertStatus: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
