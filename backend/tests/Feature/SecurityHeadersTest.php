<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_api_response_contains_security_headers(): void
    {
        $response = $this->getJson('/api/v1/ready');

        $response
            ->assertSuccessful()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader(
                'Content-Security-Policy',
                "frame-ancestors 'none'"
            );
    }

    public function test_api_error_response_contains_security_headers(): void
    {
        $response = $this->getJson('/api/v1/test-error');

        $response
            ->assertStatus(500)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader(
                'Content-Security-Policy',
                "frame-ancestors 'none'"
            );
    }
}
