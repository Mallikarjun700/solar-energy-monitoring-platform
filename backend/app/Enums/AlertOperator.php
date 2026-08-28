<?php

namespace App\Enums;

enum AlertOperator: string
{
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    case LESS_THAN = 'less_than';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case EQUAL = 'equal';
    case NOT_EQUAL = 'not_equal';
}
