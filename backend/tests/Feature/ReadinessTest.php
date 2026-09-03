<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_is_ready_when_database_and_queue_are_available(): void
    {
        $response = $this->getJson('/api/v1/ready');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                    'queue' => 'ok',
                ],
            ]);
    }
}
