<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlertApiTest extends TestCase
{
    use RefreshDatabase;

    private function alertReadToken(): string
    {
        $user = User::factory()->create();

        return $user->createToken(
            'alert-read-test',
            [TokenAbility::ALERTS_READ->value]
        )->plainTextToken;
    }

    public function test_alerts_can_be_listed(): void
    {
        $tenantId = (string) Str::uuid();

        Alert::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $response = $this
            ->withToken($this->alertReadToken())
            ->getJson(
                '/api/v1/alerts?'.http_build_query([
                    'tenant_id' => $tenantId,
                ])
            );

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_alerts_can_be_filtered_by_status(): void
    {
        $tenantId = (string) Str::uuid();

        Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => 'open',
        ]);

        Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => 'resolved',
        ]);

        $response = $this
            ->withToken($this->alertReadToken())
            ->getJson(
                '/api/v1/alerts?'.http_build_query([
                    'tenant_id' => $tenantId,
                    'status' => 'open',
                ])
            );

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $tenantId = (string) Str::uuid();

        $response = $this
            ->withToken($this->alertReadToken())
            ->getJson(
                '/api/v1/alerts?'.http_build_query([
                    'tenant_id' => $tenantId,
                    'per_page' => 101,
                ])
            );

        $response->assertStatus(422);
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $tenantId = (string) Str::uuid();

        $response = $this
            ->withToken($this->alertReadToken())
            ->getJson(
                '/api/v1/alerts?'.http_build_query([
                    'tenant_id' => $tenantId,
                    'from' => '2026-08-28',
                    'to' => '2026-08-01',
                ])
            );

        $response->assertStatus(422);
    }

    public function test_alert_requires_read_ability(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken(
            'telemetry-only',
            [TokenAbility::TELEMETRY_READ->value]
        )->plainTextToken;

        $tenantId = (string) Str::uuid();

        $response = $this
            ->withToken($token)
            ->getJson(
                '/api/v1/alerts?tenant_id='.$tenantId
            );

        $response->assertStatus(403);
    }

    public function test_alert_can_be_viewed(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $response = $this
            ->withToken($this->alertReadToken())
            ->getJson(
                '/api/v1/alerts/'.$alert->id
                .'?tenant_id='.$tenantId
            );

        $response
            ->assertStatus(200)
            ->assertJsonPath('id', $alert->id);
    }

    public function test_alert_from_different_tenant_is_not_visible(): void
    {
        $alertTenant = (string) Str::uuid();
        $requestedTenant = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $alertTenant,
        ]);

        $response = $this
            ->withToken($this->alertReadToken())
            ->getJson(
                '/api/v1/alerts/'.$alert->id
                .'?tenant_id='.$requestedTenant
            );

        $response->assertStatus(404);
    }
}
