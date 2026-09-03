<?php

namespace App\Enums;

enum DeadLetterStatus: string
{
    case PENDING = 'pending';
    case INVESTIGATING = 'investigating';
    case REPLAYED = 'replayed';
    case RESOLVED = 'resolved';
    case FAILED = 'failed';
}
