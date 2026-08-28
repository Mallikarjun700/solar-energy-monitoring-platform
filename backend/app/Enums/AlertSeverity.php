<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case INFO = 'info';
    case HIGH = 'high';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case EMERGENCY = 'emergency';
}
