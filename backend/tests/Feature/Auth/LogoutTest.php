<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/logout';

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_logout_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);

        // Login first
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Secret@123',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        $response = $this->postJson($this->endpoint, [], [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $response->assertNoContent();

        // Refresh token should be cleared from DB
        $user->refresh();
        $this->assertNull($user->refresh_token);
    }

    public function test_logout_invalidates_access_token(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Secret@123',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Logout
        $this->postJson($this->endpoint, [], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertNoContent();

        // Trying to use the old token should fail
        $response = $this->getJson('/api/v1/users/me', [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $response->assertStatus(401);
    }

    /* ── Unauthenticated ─────────────────────────────────────────── */

    public function test_logout_fails_without_token(): void
    {
        $response = $this->postJson($this->endpoint);

        $response->assertStatus(401)
            ->assertJsonFragment([
                'message' => 'Token not provided',
            ]);
    }

    public function test_logout_fails_with_invalid_token(): void
    {
        $response = $this->postJson($this->endpoint, [], [
            'Authorization' => 'Bearer invalid.token.here',
        ]);

        $response->assertStatus(401);
    }
}
