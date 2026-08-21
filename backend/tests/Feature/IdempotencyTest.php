<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function telemetryPayload(): array
    {
        return [
            'events' => [
                [
                    'event_id' => '770e8400-e29b-41d4-a716-446655440000',
                    'tenant_id' => '770e8400-e29b-41d4-a716-446655440001',
                    'source_id' => '770e8400-e29b-41d4-a716-446655440002',
                    'event_type' => 'telemetry.power',
                    'timestamp' => now()->toISOString(),
                    'schema_version' => 1,
                    'attributes' => [
                        'device_id' => 1,
                    ],
                    'payload' => [
                        'power_kw' => 52.5,
                    ],
                ],
            ],
        ];
    }

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();

        $token = $user->createToken(
            'idempotency-test',
            [\App\Enums\TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;

        return [$user, $token];
    }

    public function test_missing_idempotency_key_returns_bad_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this
            ->withToken($token)
            ->postJson(
                '/api/v1/telemetry/events',
                $this->telemetryPayload()
            );

        $response
            ->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Idempotency-Key header is required.',
            ]);
    }

    public function test_first_request_is_processed(): void
    {
        Queue::fake();

        [$user, $token] = $this->authenticatedUser();

        $response = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'idem-test-001')
            ->postJson(
                '/api/v1/telemetry/events',
                $this->telemetryPayload()
            );

        $response->assertStatus(202);

        Queue::assertPushed(ProcessTelemetryBatchJob::class);
    }

    public function test_same_key_and_same_payload_returns_original_response_without_dispatching_again(): void
    {
        Queue::fake();

        [$user, $token] = $this->authenticatedUser();

        $payload = $this->telemetryPayload();

        $firstResponse = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'idem-test-002')
            ->postJson('/api/v1/telemetry/events', $payload);

        $firstResponse->assertStatus(202);

        Queue::assertPushed(ProcessTelemetryBatchJob::class, 1);

        $secondResponse = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'idem-test-002')
            ->postJson('/api/v1/telemetry/events', $payload);

        $secondResponse
            ->assertStatus(202)
            ->assertJson($firstResponse->json());

        Queue::assertPushed(ProcessTelemetryBatchJob::class, 1);
    }

    public function test_same_key_with_different_payload_returns_conflict(): void
    {
        Queue::fake();

        [$user, $token] = $this->authenticatedUser();

        $firstPayload = $this->telemetryPayload();

        $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'idem-test-003')
            ->postJson('/api/v1/telemetry/events', $firstPayload)
            ->assertStatus(202);

        $secondPayload = $this->telemetryPayload();

        $secondPayload['events'][0]['event_id'] =
            '880e8400-e29b-41d4-a716-446655440000';

        $response = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'idem-test-003')
            ->postJson('/api/v1/telemetry/events', $secondPayload);

        $response
            ->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'message' => 'Idempotency-Key has already been used with a different request.',
            ]);

        Queue::assertPushed(ProcessTelemetryBatchJob::class, 1);
    }

    public function test_idempotency_record_is_created(): void
    {
        Queue::fake();

        [$user, $token] = $this->authenticatedUser();

        $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'idem-test-004')
            ->postJson(
                '/api/v1/telemetry/events',
                $this->telemetryPayload()
            )
            ->assertStatus(202);

        $this->assertDatabaseHas('idempotency_keys', [
            'key' => 'idem-test-004',
            'status_code' => 202,
        ]);
    }

    public function test_duplicate_idempotency_key_is_protected_by_database_constraint(): void
    {
        $first = \App\Models\IdempotencyKey::create([
            'key' => 'race-condition-test',
            'request_hash' => hash('sha256', 'payload-a'),
        ]);

        $this->assertNotNull($first->id);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\IdempotencyKey::create([
            'key' => 'race-condition-test',
            'request_hash' => hash('sha256', 'payload-a'),
        ]);
    }

    public function test_invalid_idempotency_key_format_returns_bad_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'invalid key with spaces')
            ->postJson(
                '/api/v1/telemetry/events',
                $this->telemetryPayload()
            );

        $response
            ->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid Idempotency-Key format.',
            ]);
    }
}