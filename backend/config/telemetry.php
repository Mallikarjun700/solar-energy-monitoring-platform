<?php

return [
    'retention' => [
        'hot_days' => (int) env('TELEMETRY_HOT_RETENTION_DAYS', 90),
        'archive_days' => (int) env('TELEMETRY_ARCHIVE_RETENTION_DAYS', 365),
    ],
    'rate_limit' => [
        'requests_per_minute' => (int) env('TELEMETRY_RATE_LIMIT',60),
    ],
];