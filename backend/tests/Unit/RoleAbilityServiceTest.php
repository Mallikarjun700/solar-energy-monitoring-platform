<?php

namespace Tests\Unit;

use App\Enums\TokenAbility;
use App\Enums\UserRole;
use App\Services\RoleAbilityService;
use PHPUnit\Framework\TestCase;

class RoleAbilityServiceTest extends TestCase
{
    private RoleAbilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RoleAbilityService;
    }

    public function test_admin_has_all_abilities(): void
    {
        $abilities = $this->service->abilitiesFor(UserRole::ADMIN);

        $this->assertSame(
            array_map(
                static fn (TokenAbility $ability): string => $ability->value,
                TokenAbility::cases()
            ),
            $abilities
        );
    }

    public function test_operator_has_operational_abilities(): void
    {
        $abilities = $this->service->abilitiesFor(UserRole::OPERATOR);

        $this->assertContains(TokenAbility::TELEMETRY_READ->value, $abilities);
        $this->assertContains(TokenAbility::TELEMETRY_WRITE->value, $abilities);
        $this->assertContains(TokenAbility::ALERTS_READ->value, $abilities);
        $this->assertContains(TokenAbility::ALERTS_ACKNOWLEDGE->value, $abilities);
        $this->assertContains(TokenAbility::ALERTS_RESOLVE->value, $abilities);

        $this->assertNotContains(TokenAbility::DLQ_READ->value, $abilities);
        $this->assertNotContains(TokenAbility::DLQ_REPLAY->value, $abilities);
    }

    public function test_viewer_has_read_only_abilities(): void
    {
        $abilities = $this->service->abilitiesFor(UserRole::VIEWER);

        $this->assertSame([
            TokenAbility::TELEMETRY_READ->value,
            TokenAbility::ALERTS_READ->value,
        ], $abilities);
    }
}
