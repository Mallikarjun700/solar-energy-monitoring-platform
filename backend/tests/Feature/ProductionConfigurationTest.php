<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_debug_configuration_is_environment_driven(): void
    {
        $this->assertSame(
            (bool) config('app.debug'),
            filter_var(env('APP_DEBUG'), FILTER_VALIDATE_BOOL)
        );
    }

    public function test_session_security_configuration_is_environment_driven(): void
    {
        $this->assertSame(
            env('SESSION_HTTP_ONLY', true),
            config('session.http_only')
        );

        $this->assertSame(
            env('SESSION_SAME_SITE', 'lax'),
            config('session.same_site')
        );
    }

    public function test_production_requirements_are_documented(): void
    {
        $this->assertTrue(
            config('session.http_only'),
            'SESSION_HTTP_ONLY must be enabled in production.'
        );

        $this->assertSame(
            'lax',
            config('session.same_site')
        );
    }
}
