<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CorsSecurityTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_allowed_origin_receives_cors_headers(): void
    {
        $response = $this
            ->withHeader('Origin', 'http://localhost:3000')
            ->getJson('/api/v1/ready');

        $response
            ->assertSuccessful()
            ->assertHeader(
                'Access-Control-Allow-Origin',
                'http://localhost:3000'
            );
    }

    public function test_disallowed_origin_does_not_receive_cors_allow_origin_header(): void
    {
        $response = $this
            ->withHeader('Origin', 'https://evil.example.com')
            ->getJson('/api/v1/ready');

        $this->assertNotSame(
            'https://evil.example.com',
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }
}
