<?php

$cachedConfig = dirname(__DIR__).'/bootstrap/cache/config.php';

if (is_file($cachedConfig)) {
    unlink($cachedConfig);
}

touch(__DIR__.'/../database/testing-telemetry.sqlite');

require dirname(__DIR__).'/vendor/autoload.php';
