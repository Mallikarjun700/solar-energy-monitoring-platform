<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard?tenant_id=11111111-1111-4111-8111-111111111111');

        $response->assertUnauthorized();
    }

    public function test_dashboard_requires_valid_tenant_id(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/dashboard?tenant_id=invalid');

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_dashboard_returns_expected_response_structure(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(
            '/api/v1/dashboard?tenant_id=11111111-1111-4111-8111-111111111111'
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'kpis' => [
                        'totalPlants',
                        'totalDevices',
                        'activeDevices',
                        'currentPowerKw',
                        'todayEnergyKwh',
                    ],
                    'plants',
                    'devices',
                    'energyTrend',
                    'alerts',
                    'recentTelemetry',
                    'recentActivity',
                ],
            ]);
    }

    public function test_dashboard_returns_empty_collections_when_no_dashboard_records_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(
            '/api/v1/dashboard?tenant_id=11111111-1111-4111-8111-111111111111'
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.kpis.totalPlants', 0)
            ->assertJsonPath('data.kpis.totalDevices', 0)
            ->assertJsonPath('data.kpis.activeDevices', 0)
            ->assertJsonPath('data.kpis.currentPowerKw', null)
            ->assertJsonPath('data.kpis.todayEnergyKwh', 0)
            ->assertJsonPath('data.plants', [])
            ->assertJsonPath('data.devices', [])
            ->assertJsonPath('data.energyTrend', [])
            ->assertJsonPath('data.alerts', [])
            ->assertJsonPath('data.recentTelemetry', [])
            ->assertJsonPath('data.recentActivity', []);
    }
}
