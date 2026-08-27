<?php

namespace Tests\Unit;

use App\Services\ProductionConfigurationValidator;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class ProductionConfigurationValidatorTest extends TestCase
{
    public function test_local_environment_is_not_blocked_by_production_rules(): void
    {
        Config::set('app.env', 'local');
        Config::set('app.debug', true);

        $validator = app(ProductionConfigurationValidator::class);

        $validator->validate();

        $this->assertTrue(true);
    }

    public function test_production_configuration_is_accepted(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', false);
        Config::set('app.key', 'base64:production-test-key');
        Config::set('app.url', 'https://api.example.com');

        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');

        Config::set('logging.channels.single.level', 'warning');
        Config::set('cors.allowed_origins', [
            'https://app.example.com',
        ]);

        $validator = app(ProductionConfigurationValidator::class);

        $validator->validate();

        $this->assertTrue(true);
    }

    public function test_production_debug_mode_is_rejected(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', true);
        Config::set('app.key', 'base64:production-test-key');
        Config::set('app.url', 'https://api.example.com');

        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');

        Config::set('logging.channels.single.level', 'warning');
        Config::set('cors.allowed_origins', [
            'https://app.example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'APP_DEBUG must be false in production.'
        );

        app(ProductionConfigurationValidator::class)->validate();
    }

    public function test_production_insecure_session_cookie_is_rejected(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', false);
        Config::set('app.key', 'base64:production-test-key');
        Config::set('app.url', 'https://api.example.com');

        Config::set('session.secure', false);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');

        Config::set('logging.channels.single.level', 'warning');
        Config::set('cors.allowed_origins', [
            'https://app.example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'SESSION_SECURE_COOKIE must be true in production.'
        );

        app(ProductionConfigurationValidator::class)->validate();
    }

    public function test_production_wildcard_cors_is_rejected(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', false);
        Config::set('app.key', 'base64:production-test-key');
        Config::set('app.url', 'https://api.example.com');

        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');

        Config::set('logging.channels.single.level', 'warning');
        Config::set('cors.allowed_origins', ['*']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'CORS must not allow all origins in production.'
        );

        app(ProductionConfigurationValidator::class)->validate();
    }
}