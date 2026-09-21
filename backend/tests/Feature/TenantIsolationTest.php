<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Device;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(
        User $user,
        array $abilities
    ): void {
        Sanctum::actingAs($user, $abilities);
    }

    public function test_user_can_only_list_plants_from_own_tenant(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        Plant::factory()->create([
            'tenant_id' => $tenantA,
        ]);

        Plant::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $this->authenticate($user, ['plants:read']);

        $response = $this->getJson('/api/v1/plants');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'tenant_id' => $tenantB,
            ]);
    }

    public function test_user_cannot_read_another_tenant_plant(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $plant = Plant::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $this->authenticate($user, ['plants:read']);

        $this->getJson("/api/v1/plants/{$plant->id}")
            ->assertNotFound();
    }

    public function test_request_tenant_id_cannot_switch_user_tenant(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $this->authenticate($user, ['plants:read']);

        $this->getJson("/api/v1/plants?tenant_id={$tenantB}")
            ->assertForbidden();
    }

    public function test_plant_creation_uses_authenticated_user_tenant(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $this->authenticate($user, ['plants:write']);

        $response = $this->postJson('/api/v1/plants', [
            'tenant_id' => $tenantB,
            'name' => 'Tenant A Solar Plant',
            'code' => 'TENANT-A-PLANT',
            'location' => 'Bengaluru',
            'capacity_kw' => 5000,
            'status' => 'ACTIVE',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('plants', [
            'code' => 'TENANT-A-PLANT',
            'tenant_id' => $tenantA,
        ]);

        $this->assertDatabaseMissing('plants', [
            'code' => 'TENANT-A-PLANT',
            'tenant_id' => $tenantB,
        ]);
    }

    public function test_user_cannot_read_another_tenant_asset(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $asset = Asset::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $this->authenticate($user, ['assets:read']);

        $this->getJson("/api/v1/assets/{$asset->id}")
            ->assertNotFound();
    }

    public function test_user_cannot_read_another_tenant_device(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $device = Device::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $this->authenticate($user, ['devices:read']);

        $this->getJson("/api/v1/devices/{$device->id}")
            ->assertNotFound();
    }

    public function test_asset_cannot_be_created_under_foreign_tenant_plant(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $plant = Plant::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $this->authenticate($user, ['assets:write']);

        $this->postJson('/api/v1/assets', [
            'tenant_id' => $tenantA,
            'plant_id' => $plant->id,
            'name' => 'Cross Tenant Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'CROSS-TENANT-001',
            'status' => 'ACTIVE',
        ])->assertNotFound();
    }

    public function test_device_cannot_be_created_under_foreign_tenant_asset(): void
    {
        $tenantA = fake()->uuid();
        $tenantB = fake()->uuid();

        $user = User::factory()
            ->forTenant($tenantA)
            ->create();

        $asset = Asset::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $this->authenticate($user, ['devices:write']);

        $this->postJson('/api/v1/devices', [
            'tenant_id' => $tenantA,
            'asset_id' => $asset->id,
            'device_type' => 'METER',
            'serial_number' => 'CROSS-TENANT-DEVICE',
            'status' => 'ONLINE',
        ])->assertNotFound();
    }
}
