<?php

namespace App\Services;

use RuntimeException;

class ProductionConfigurationValidator
{
    /**
     * Validate configuration that is required for a production deployment.
     *
     * Local/test environments are intentionally not blocked.
     */
    public function validate(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $errors = [];

        if (config('app.debug')) {
            $errors[] = 'APP_DEBUG must be false in production.';
        }

        if (blank(config('app.key'))) {
            $errors[] = 'APP_KEY must be configured in production.';
        }

        if (blank(config('app.url'))) {
            $errors[] = 'APP_URL must be configured in production.';
        }

        if (config('session.secure') !== true) {
            $errors[] = 'SESSION_SECURE_COOKIE must be true in production.';
        }

        if (config('session.http_only') !== true) {
            $errors[] = 'SESSION_HTTP_ONLY must be true in production.';
        }

        if (config('session.same_site') !== 'lax') {
            $errors[] = 'SESSION_SAME_SITE must be lax in production.';
        }

        if (config('logging.channels.single.level') === 'debug') {
            $errors[] = 'LOG_LEVEL must not be debug in production.';
        }

        // if (config('cors.allowed_origins') === ['*']) {
        //     $errors[] = 'CORS must not allow all origins in production.';
        // }

        if ($errors !== []) {
            throw new RuntimeException(
                'Production configuration validation failed: '.implode(' ', $errors)
            );
        }
    }
}