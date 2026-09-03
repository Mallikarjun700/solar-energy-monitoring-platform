<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case OPERATOR = 'operator';
    case VIEWER = 'viewer';
}
