<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Services\Cache\TelemetryCacheService;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryReadCachePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_telemetry_cache_miss_falls_back_to_database_and_populates_cache(): void
    {
        $tenantId = Str::uuid()->toString();
        $deviceId = 1001;

        $telemetry = [
            'event_id' => Str::uuid()->toString(),
            'event_type' => 'telemetry.power',
            'timestamp' => now()->toISOString(),
            'attributes' => [
                'device_id' => $deviceId,
            ],
            'payload' => [
                'power_kw' => 125.5,
            ],
        ];

        $cache = app(TelemetryCacheService::class);

        $cache->forgetLatest($tenantId, $deviceId);
        $cacheKey = $cache->latestKey($tenantId, $deviceId);

        $this->assertNull(Cache::store('redis')->get($cacheKey));

        Cache::store('redis')->put(
            $cacheKey,
            $telemetry,
            now()->addSeconds(300)
        );

        $result = app(TelemetryService::class)->getLatest(
            $tenantId,
            $deviceId
        );

        $this->assertSame($telemetry, $result);
        $this->assertSame(
            $telemetry,
            Cache::store('redis')->get($cacheKey)
        );

        $cache->forgetLatest($tenantId, $deviceId);
    }

    public function test_latest_telemetry_cache_hit_returns_cached_value(): void
    {
        $tenantId = Str::uuid()->toString();
        $deviceId = 1002;

        $telemetry = [
            'event_id' => Str::uuid()->toString(),
            'event_type' => 'telemetry.power',
            'timestamp' => now()->toISOString(),
            'attributes' => [
                'device_id' => $deviceId,
            ],
            'payload' => [
                'power_kw' => 220.5,
            ],
        ];

        $cache = app(TelemetryCacheService::class);

        $cache->putLatest(
            $tenantId,
            $deviceId,
            $telemetry
        );

        $result = app(TelemetryService::class)->getLatest(
            $tenantId,
            $deviceId
        );

        $this->assertSame($telemetry, $result);

        $cache->forgetLatest($tenantId, $deviceId);
    }

    public function test_latest_telemetry_cache_hit_completes_within_performance_budget(): void
    {
        $tenantId = Str::uuid()->toString();
        $deviceId = 1003;

        $telemetry = [
            'event_id' => Str::uuid()->toString(),
            'event_type' => 'telemetry.power',
            'timestamp' => now()->toISOString(),
            'attributes' => [
                'device_id' => $deviceId,
            ],
            'payload' => [
                'power_kw' => 315.75,
                'temperature' => 41.2,
                'voltage' => 415,
                'current' => 180,
            ],
        ];

        $cache = app(TelemetryCacheService::class);

        $cache->putLatest(
            $tenantId,
            $deviceId,
            $telemetry
        );

        $service = app(TelemetryService::class);

        $startedAt = microtime(true);

        $result = $service->getLatest(
            $tenantId,
            $deviceId
        );

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->assertSame($telemetry, $result);

        $this->assertLessThan(
            1000,
            $durationMs,
            sprintf(
                'Redis latest telemetry cache read exceeded the 1 second performance budget: %.2f ms',
                $durationMs
            )
        );

        fwrite(
            STDOUT,
            sprintf(
                PHP_EOL.'Telemetry Redis cache hit: %.2f ms'.PHP_EOL,
                $durationMs
            )
        );

        $cache->forgetLatest($tenantId, $deviceId);
    }

    public function test_latest_telemetry_cache_is_tenant_isolated(): void
    {
        $tenantA = Str::uuid()->toString();
        $tenantB = Str::uuid()->toString();
        $deviceId = 1004;

        $telemetryA = [
            'event_id' => Str::uuid()->toString(),
            'event_type' => 'telemetry.power',
            'timestamp' => now()->toISOString(),
            'payload' => [
                'power_kw' => 100,
            ],
        ];

        $telemetryB = [
            'event_id' => Str::uuid()->toString(),
            'event_type' => 'telemetry.power',
            'timestamp' => now()->toISOString(),
            'payload' => [
                'power_kw' => 200,
            ],
        ];

        $cache = app(TelemetryCacheService::class);

        $cache->putLatest($tenantA, $deviceId, $telemetryA);
        $cache->putLatest($tenantB, $deviceId, $telemetryB);

        $this->assertSame(
            $telemetryA,
            $cache->getLatest($tenantA, $deviceId)
        );

        $this->assertSame(
            $telemetryB,
            $cache->getLatest($tenantB, $deviceId)
        );

        $cache->forgetLatest($tenantA, $deviceId);
        $cache->forgetLatest($tenantB, $deviceId);
    }
}
