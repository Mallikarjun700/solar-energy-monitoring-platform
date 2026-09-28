<?php

$cachedConfig = dirname(__DIR__).'/bootstrap/cache/config.php';

if (is_file($cachedConfig)) {
    unlink($cachedConfig);
}

$telemetryDatabase = __DIR__.'/../database/testing-telemetry.sqlite';

if (is_file($telemetryDatabase)) {
    unlink($telemetryDatabase);
}

touch($telemetryDatabase);

require dirname(__DIR__).'/vendor/autoload.php';
