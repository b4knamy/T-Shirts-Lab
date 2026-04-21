<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // reset rate limiter counters before each test
    }

    public function test_login_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ghost@example.com',
                'password' => 'WrongPass123',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'WrongPass123',
        ])->assertStatus(429);
    }

    public function test_register_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'email' => 'invalid-email',
                'password' => 'Secret@123',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/register', [
            'email' => 'invalid-email',
            'password' => 'Secret@123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ])->assertStatus(429);
    }

    public function test_login_rate_limit_resets_after_decay_window(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ghost@example.com',
                'password' => 'WrongPass123',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'WrongPass123',
        ])->assertStatus(429);

        $this->travel(61)->seconds();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'WrongPass123',
        ])->assertStatus(401);
    }
}
