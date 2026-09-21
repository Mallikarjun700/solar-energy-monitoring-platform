<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SensitiveLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->get('/api/v1/test-error', function () {
            throw new \RuntimeException('Intentional test exception.');
        });
    }

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
