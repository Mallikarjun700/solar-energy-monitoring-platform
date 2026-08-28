<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Enums\TokenAbility;

class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateForApi([
            TokenAbility::TELEMETRY_WRITE->value,
        ]);
    }

    /**
     * A basic feature test example.
     */

    public function test_validation_error_has_standard_structure(): void
    {
        $correlationId = 'test-error-correlation-001';

        $response = $this
            ->withHeader('X-Correlation-ID', $correlationId)
            ->withHeader('Idempotency-Key', 'api-error-validation-001')
            ->postJson('/api/v1/telemetry/events', []);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'correlation_id',
                'errors',
            ])
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed.',
                'correlation_id' => $correlationId,
            ]);
    }

    public function test_validation_error_contains_field_errors(): void
    {
        $response = $this
            ->withHeader('Idempotency-Key', 'api-error-validation-002')
            ->postJson('/api/v1/telemetry/events', []);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'events',
                ],
            ]);
    }
    
    public function test_not_found_error_has_standard_structure(): void
    {
        $correlationId = 'test-not-found-001';

        $response = $this
            ->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/devices/999999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Resource not found.',
                'correlation_id' => $correlationId,
            ]);
    }

    public function test_unexpected_api_exception_has_standard_500_response(): void
    {
        $correlationId = 'test-500-correlation-001';

        $response = $this
            ->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/test-error');

        $response
            ->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Internal server error.',
                'correlation_id' => $correlationId,
            ]);

        $this->assertStringNotContainsString(
            'Intentional test exception.',
            $response->getContent()
        );
    }
}
