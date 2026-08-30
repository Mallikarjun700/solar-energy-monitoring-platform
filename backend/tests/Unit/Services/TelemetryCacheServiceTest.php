<?php

namespace Tests\Unit\Services;

use App\Services\Cache\TelemetryCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TelemetryCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_tenant_isolated_latest_telemetry_key(): void
    {
        $service = new TelemetryCacheService();

        $key = $service->latestKey(
            'tenant-123',
            456
        );

        $this->assertSame(
            'telemetry:tenant:tenant-123:device:456:latest',
            $key
        );
    }

    public function test_it_stores_and_retrieves_latest_telemetry(): void
    {
        $service = new TelemetryCacheService();

        $telemetry = [
            'event_id' => 'event-123',
            'timestamp' => '2026-08-29T10:00:00Z',
            'temperature' => 72.5,
            'power_kw' => 52.4,
        ];

        $service->putLatest(
            'tenant-123',
            456,
            $telemetry
        );

        $this->assertSame(
            $telemetry,
            $service->getLatest('tenant-123', 456)
        );
    }

    public function test_tenant_data_isolation_is_preserved(): void
    {
        $service = new TelemetryCacheService();

        $tenantATelemetry = [
            'event_id' => 'event-a',
            'power_kw' => 50,
        ];

        $tenantBTelemetry = [
            'event_id' => 'event-b',
            'power_kw' => 80,
        ];

        $service->putLatest(
            'tenant-a',
            100,
            $tenantATelemetry
        );

        $service->putLatest(
            'tenant-b',
            100,
            $tenantBTelemetry
        );

        $this->assertSame(
            $tenantATelemetry,
            $service->getLatest('tenant-a', 100)
        );

        $this->assertSame(
            $tenantBTelemetry,
            $service->getLatest('tenant-b', 100)
        );
    }

    public function test_missing_latest_telemetry_returns_null(): void
    {
        $service = new TelemetryCacheService();

        $this->assertNull(
            $service->getLatest('tenant-missing', 999)
        );
    }

    public function test_forget_removes_latest_telemetry(): void
    {
        $service = new TelemetryCacheService();

        $service->putLatest(
            'tenant-123',
            456,
            ['power_kw' => 52]
        );

        $this->assertNotNull(
            $service->getLatest('tenant-123', 456)
        );

        $service->forgetLatest(
            'tenant-123',
            456
        );

        $this->assertNull(
            $service->getLatest('tenant-123', 456)
        );
    }

    public function test_cache_read_failure_returns_null(): void
    {
        Cache::shouldReceive('store')
            ->with('redis')
            ->andThrow(new \RuntimeException('Redis unavailable'));

        $service = new TelemetryCacheService();

        $this->assertNull(
            $service->getLatest('tenant-123', 456)
        );
    }

    public function test_cache_write_failure_does_not_throw(): void
    {
        Cache::shouldReceive('store')
            ->with('redis')
            ->andThrow(new \RuntimeException('Redis unavailable'));

        $service = new TelemetryCacheService();

        $service->putLatest(
            'tenant-123',
            456,
            ['power_kw' => 52]
        );

        $this->assertTrue(true);
    }

    public function test_cache_invalidation_failure_does_not_throw(): void
    {
        Cache::shouldReceive('store')
            ->with('redis')
            ->andThrow(new \RuntimeException('Redis unavailable'));

        $service = new TelemetryCacheService();

        $service->forgetLatest(
            'tenant-123',
            456
        );

        $this->assertTrue(true);
    }
}