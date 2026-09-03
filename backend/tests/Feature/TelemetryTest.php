<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_ingestion_dispatches_async_batch_job(): void
    {
        Queue::fake();

        $payload = [
            'events' => [
                [
                    'event_id' => '550e8400-e29b-41d4-a716-446655440000',
                    'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
                    'source_id' => '550e8400-e29b-41d4-a716-446655440002',
                    'event_type' => 'telemetry.power',
                    'timestamp' => now()->toISOString(),
                    'schema_version' => 1,
                    'attributes' => [
                        'device_id' => 1,
                        'location' => 'Block A',
                    ],
                    'payload' => [
                        'power_kw' => 52.5,
                        'temperature' => 29.5,
                        'voltage' => 230,
                        'current' => 12.8,
                    ],
                ],
            ],
        ];

        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'unique-test-key')
            ->postJson('/api/v1/telemetry/events', $payload);

        $response->assertStatus(202);

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) {
                return count($job->events) === 1
                    && $job->events[0]['event_id'] === '550e8400-e29b-41d4-a716-446655440000';
            }
        );
    }

    public function test_telemetry_batch_accepts_up_to_1000_events(): void
    {
        Queue::fake();

        $events = [];

        for ($i = 0; $i < 1000; $i++) {
            $events[] = [
                'event_id' => Str::uuid()->toString(),
                'tenant_id' => Str::uuid()->toString(),
                'source_id' => Str::uuid()->toString(),
                'event_type' => 'telemetry',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 1,
                    'temperature' => 25.5,
                ],
            ];
        }

        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'telemetry-1000-events')
            ->postJson('/api/v1/telemetry/events', [
                'events' => $events,
            ]);

        $response->assertStatus(202);
    }

    public function test_telemetry_batch_rejects_more_than_1000_events(): void
    {
        $events = [];

        for ($i = 0; $i < 1001; $i++) {
            $events[] = [
                'event_id' => Str::uuid()->toString(),
                'tenant_id' => Str::uuid()->toString(),
                'source_id' => Str::uuid()->toString(),
                'event_type' => 'telemetry',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 1,
                    'temperature' => 25.5,
                ],
            ];
        }

        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'telemetry-1001-events')
            ->postJson('/api/v1/telemetry/events', [
                'events' => $events,
            ]);

        $response->assertStatus(422);
    }

    public function test_telemetry_dispatches_jobs_in_batches_of_250_or_less(): void
    {
        Queue::fake();

        $events = [];

        for ($i = 0; $i < 1000; $i++) {
            $events[] = [
                'event_id' => Str::uuid()->toString(),
                'tenant_id' => Str::uuid()->toString(),
                'source_id' => Str::uuid()->toString(),
                'event_type' => 'telemetry',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 1,
                    'temperature' => 25.5,
                ],
            ];
        }

        $jobs = [];

        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'telemetry-1000-batches')
            ->postJson('/api/v1/telemetry/events', [
                'events' => $events,
            ]);

        $response->assertStatus(202);

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) use (&$jobs) {
                $jobs[] = $job;

                return true;
            }
        );

        $this->assertCount(4, $jobs);

        foreach ($jobs as $job) {
            $this->assertLessThanOrEqual(250, count($job->events));
        }
    }

    public function test_telemetry_dispatches_three_jobs_for_600_events(): void
    {
        Queue::fake();

        $events = [];

        for ($i = 0; $i < 600; $i++) {
            $events[] = [
                'event_id' => Str::uuid()->toString(),
                'tenant_id' => Str::uuid()->toString(),
                'source_id' => Str::uuid()->toString(),
                'event_type' => 'telemetry',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 1,
                    'temperature' => 25.5,
                ],
            ];
        }

        $jobs = [];

        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'telemetry-600-batches')
            ->postJson('/api/v1/telemetry/events', [
                'events' => $events,
            ]);

        $response->assertStatus(202);

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) use (&$jobs) {
                $jobs[] = $job;

                return true;
            }
        );

        $this->assertCount(3, $jobs);
        $this->assertSame(250, count($jobs[0]->events));
        $this->assertSame(250, count($jobs[1]->events));
        $this->assertSame(100, count($jobs[2]->events));
    }

    public function test_telemetry_batch_job_has_expected_retry_configuration(): void
    {
        $job = new ProcessTelemetryBatchJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(60, $job->timeout);
    }

    private function telemetryToken(): string
    {
        $user = User::factory()->create();

        return $user->createToken(
            'telemetry-test',
            [TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;
    }
}
