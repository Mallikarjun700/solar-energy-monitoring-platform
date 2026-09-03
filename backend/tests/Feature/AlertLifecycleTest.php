<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\TokenAbility;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlertLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $ability): string
    {
        $user = User::factory()->create();

        return $user->createToken(
            'alert-lifecycle-test',
            [$ability]
        )->plainTextToken;
    }

    public function test_open_alert_can_be_acknowledged(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => AlertStatus::OPEN,
        ]);

        $response = $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_ACKNOWLEDGE->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/acknowledge"
                ."?tenant_id={$tenantId}"
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::ACKNOWLEDGED->value,
        ]);

        $this->assertNotNull(
            $alert->fresh()->acknowledged_at
        );
    }

    public function test_acknowledged_alert_can_be_resolved(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => AlertStatus::ACKNOWLEDGED,
        ]);

        $response = $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_RESOLVE->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/resolve"
                ."?tenant_id={$tenantId}"
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::RESOLVED->value,
        ]);
    }

    public function test_open_alert_can_be_resolved_directly(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => AlertStatus::OPEN,
        ]);

        $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_RESOLVE->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/resolve"
                ."?tenant_id={$tenantId}"
            )
            ->assertStatus(200);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::RESOLVED->value,
        ]);
    }

    public function test_resolved_alert_cannot_be_acknowledged(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => AlertStatus::RESOLVED,
        ]);

        $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_ACKNOWLEDGE->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/acknowledge"
                ."?tenant_id={$tenantId}"
            )
            ->assertStatus(409);
    }

    public function test_resolved_alert_cannot_be_resolved_again(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'status' => AlertStatus::RESOLVED,
        ]);

        $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_RESOLVE->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/resolve"
                ."?tenant_id={$tenantId}"
            )
            ->assertStatus(409);
    }

    public function test_acknowledge_requires_correct_ability(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_READ->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/acknowledge"
                ."?tenant_id={$tenantId}"
            )
            ->assertStatus(403);
    }

    public function test_resolve_requires_correct_ability(): void
    {
        $tenantId = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_READ->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/resolve"
                ."?tenant_id={$tenantId}"
            )
            ->assertStatus(403);
    }

    public function test_alert_from_another_tenant_cannot_be_acknowledged(): void
    {
        $alertTenant = (string) Str::uuid();
        $requestedTenant = (string) Str::uuid();

        $alert = Alert::factory()->create([
            'tenant_id' => $alertTenant,
        ]);

        $this
            ->withToken(
                $this->token(
                    TokenAbility::ALERTS_ACKNOWLEDGE->value
                )
            )
            ->postJson(
                "/api/v1/alerts/{$alert->id}/acknowledge"
                ."?tenant_id={$requestedTenant}"
            )
            ->assertStatus(404);
    }
}
