<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Throwable;

class TelemetryCacheService
{
    private const CACHE_STORE = 'redis';

    private const LATEST_TTL_SECONDS = 300;

    public function latestKey(
        string $tenantId,
        int|string $deviceId
    ): string {
        return sprintf(
            'telemetry:tenant:%s:device:%s:latest',
            $tenantId,
            $deviceId
        );
    }

    /**
     * Store the latest accepted telemetry state for a device.
     *
     * Redis is a read-optimization layer, not the source of truth.
     */
    public function putLatest(
        string $tenantId,
        int|string $deviceId,
        array $telemetry
    ): void {
        try {
            Cache::store(self::CACHE_STORE)->put(
                $this->latestKey($tenantId, $deviceId),
                $telemetry,
                now()->addSeconds(self::LATEST_TTL_SECONDS)
            );
        } catch (Throwable $exception) {
            /*
             * Cache failure must not fail telemetry processing.
             */
            logger()->warning('Telemetry cache write failed', [
                'tenant_id' => $tenantId,
                'device_id' => $deviceId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Retrieve the latest cached telemetry state.
     */
    public function getLatest(
        string $tenantId,
        int|string $deviceId
    ): ?array {
        try {
            $value = Cache::store(self::CACHE_STORE)->get(
                $this->latestKey($tenantId, $deviceId)
            );

            return is_array($value) ? $value : null;
        } catch (Throwable $exception) {
            /*
             * Redis failure behaves exactly like a cache miss.
             */
            logger()->warning('Telemetry cache read failed', [
                'tenant_id' => $tenantId,
                'device_id' => $deviceId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Remove the cached latest state.
     */
    public function forgetLatest(
        string $tenantId,
        int|string $deviceId
    ): void {
        try {
            Cache::store(self::CACHE_STORE)->forget(
                $this->latestKey($tenantId, $deviceId)
            );
        } catch (Throwable $exception) {
            logger()->warning('Telemetry cache invalidation failed', [
                'tenant_id' => $tenantId,
                'device_id' => $deviceId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
