<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_read_endpoints_require_telemetry_read_ability(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, []);

        $this->getJson('/api/v1/telemetry/events')
            ->assertForbidden();

        $this->getJson('/api/v1/telemetry/health')
            ->assertForbidden();

        $this->getJson('/api/v1/telemetry/events/cursor')
            ->assertForbidden();

        $this->getJson('/api/v1/telemetry/devices/1/latest')
            ->assertForbidden();
    }

    public function test_telemetry_read_endpoints_allow_telemetry_read_ability(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, [
            TokenAbility::TELEMETRY_READ->value,
        ]);

        $this->getJson('/api/v1/telemetry/health')
            ->assertSuccessful();
    }

    public function test_test_error_endpoint_is_not_exposed(): void
    {
        $routes = app('router')->getRoutes()->getRoutes();

        $this->assertFalse(
            collect($routes)->contains(
                fn ($route) => $route->uri() === 'api/v1/test-error'
            )
        );

        $this->assertFalse(
            collect($routes)->contains(
                fn ($route) => $route->uri() === 'test-error'
            )
        );
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/plants')
            ->assertUnauthorized();

        $this->getJson('/api/v1/assets')
            ->assertUnauthorized();

        $this->getJson('/api/v1/devices')
            ->assertUnauthorized();

        $this->getJson('/api/v1/alerts')
            ->assertUnauthorized();

        $this->getJson('/api/v1/telemetry/events')
            ->assertUnauthorized();

        $this->getJson('/api/v1/dlq')
            ->assertUnauthorized();
    }

    public function test_ready_endpoint_remains_public(): void
    {
        $this->getJson('/api/v1/ready')
            ->assertSuccessful()
            ->assertJsonPath('status', 'ready');
    }
}
