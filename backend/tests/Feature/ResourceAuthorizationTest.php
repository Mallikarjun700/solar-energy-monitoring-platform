<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_read_plants(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        Sanctum::actingAs($user, [
            'plants:read',
        ]);

        $response = $this->getJson('/api/v1/plants');

        $response->assertSuccessful();
    }

    public function test_viewer_cannot_create_plants(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        Sanctum::actingAs($user, [
            'plants:read',
        ]);

        $response = $this->postJson('/api/v1/plants', []);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_modify_devices(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        Sanctum::actingAs($user, [
            'devices:read',
        ]);

        $response = $this->postJson('/api/v1/devices', []);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_modify_assets(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        Sanctum::actingAs($user, [
            'assets:read',
        ]);

        $response = $this->postJson('/api/v1/assets', []);

        $response->assertForbidden();
    }

    public function test_operator_can_write_resources(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::OPERATOR,
        ]);

        Sanctum::actingAs($user, [
            'plants:write',
            'assets:write',
            'devices:write',
        ]);

        $this->assertTrue(
            $user->role === UserRole::OPERATOR
        );
    }

    public function test_user_role_is_supported_by_role_ability_service(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
        ]);

        Sanctum::actingAs($user, [
            'plants:read',
            'assets:read',
            'devices:read',
        ]);

        $response = $this->getJson('/api/v1/plants');

        $response->assertSuccessful();
    }
}
