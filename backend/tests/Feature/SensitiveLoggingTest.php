<?php

namespace Tests\Feature;

use Tests\TestCase;

class SensitiveLoggingTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_api_errors_do_not_expose_sensitive_information(): void
    {
        $response = $this->getJson('/api/v1/test-error');

        $response
            ->assertStatus(500)
            ->assertJsonMissing([
                'password' => 'secret-password',
            ])
            ->assertJsonMissing([
                'token' => 'secret-token',
            ])
            ->assertJsonMissing([
                'authorization' => 'Bearer secret-token',
            ]);

        $this->assertStringNotContainsString(
            'secret-password',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'secret-token',
            $response->getContent()
        );
    }
}
