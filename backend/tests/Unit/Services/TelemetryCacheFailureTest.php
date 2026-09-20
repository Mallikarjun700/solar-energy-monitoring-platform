<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Cache\TelemetryCacheService;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class TelemetryCacheFailureTest extends TestCase
{
    public function test_redis_cache_write_failure_does_not_propagate(): void
    {
        $store = Mockery::mock(RedisStore::class);

        $store
            ->shouldReceive('put')
            ->once()
            ->andThrow(new \RuntimeException('Redis connection failed.'));

        Cache::shouldReceive('store')
            ->once()
            ->with('redis')
            ->andReturn($store);

        $service = new TelemetryCacheService;

        $service->putLatest(
            'tenant-1',
            101,
            [
                'event_id' => 'event-1',
                'power_kw' => 42.5,
            ]
        );

        $this->assertTrue(true);
    }

    public function test_redis_cache_read_failure_returns_null(): void
    {
        $store = Mockery::mock(RedisStore::class);

        $store
            ->shouldReceive('get')
            ->once()
            ->andThrow(new \RuntimeException('Redis connection failed.'));

        Cache::shouldReceive('store')
            ->once()
            ->with('redis')
            ->andReturn($store);

        $service = new TelemetryCacheService;

        $result = $service->getLatest('tenant-1', 101);

        $this->assertNull($result);
    }

    public function test_redis_cache_invalidation_failure_does_not_propagate(): void
    {
        $store = Mockery::mock(RedisStore::class);

        $store
            ->shouldReceive('forget')
            ->once()
            ->andThrow(new \RuntimeException('Redis connection failed.'));

        Cache::shouldReceive('store')
            ->once()
            ->with('redis')
            ->andReturn($store);

        $service = new TelemetryCacheService;

        $service->forgetLatest('tenant-1', 101);

        $this->assertTrue(true);
    }
}
