<?php

namespace App\Services;

use App\Enums\TokenAbility;
use App\Enums\UserRole;

class RoleAbilityService
{
    /**
     * @return array<int, string>
     */
    public function abilitiesFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::ADMIN => array_map(
                static fn (TokenAbility $ability): string => $ability->value,
                TokenAbility::cases()
            ),

            UserRole::OPERATOR => [
                TokenAbility::TELEMETRY_READ->value,
                TokenAbility::TELEMETRY_WRITE->value,

                TokenAbility::PLANTS_READ->value,
                TokenAbility::PLANTS_WRITE->value,

                TokenAbility::ASSETS_READ->value,
                TokenAbility::ASSETS_WRITE->value,

                TokenAbility::DEVICES_READ->value,
                TokenAbility::DEVICES_WRITE->value,

                TokenAbility::ALERTS_READ->value,
                TokenAbility::ALERTS_ACKNOWLEDGE->value,
                TokenAbility::ALERTS_RESOLVE->value,
            ],

            UserRole::VIEWER,
            UserRole::USER => [
                TokenAbility::TELEMETRY_READ->value,

                TokenAbility::PLANTS_READ->value,
                TokenAbility::ASSETS_READ->value,
                TokenAbility::DEVICES_READ->value,

                TokenAbility::ALERTS_READ->value,
            ],
        };
    }
}
