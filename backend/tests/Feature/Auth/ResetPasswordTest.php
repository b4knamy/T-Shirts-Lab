<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
    }

    private function seedToken(string $email, string $plainToken, int $minutesAgo = 0): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($plainToken),
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    /* ── Success ───────────────────────────────────────────────────── */

    public function test_resets_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        $this->seedToken('user@example.com', 'valid-token-abc');

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewPass@456', $user->password_hash));

        // Token was consumed
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'user@example.com']);
    }

    public function test_invalidates_all_sessions_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'refresh_token' => 'some-existing-refresh-token',
        ]);

        $this->seedToken('user@example.com', 'valid-token-abc');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ])->assertOk();

        $this->assertNull($user->fresh()->refresh_token);
    }

    public function test_old_access_token_becomes_invalid_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        ['access_token' => $accessToken] = $this->loginTokens($user);
        $this->seedToken('user@example.com', 'valid-token-abc');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ])->assertOk();

        $this->getJson('/api/v1/users/me', [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertStatus(401);
    }

    public function test_old_refresh_token_becomes_invalid_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        ['refresh_token' => $refreshToken] = $this->loginTokens($user);
        $this->seedToken('user@example.com', 'valid-token-abc');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ])->assertOk();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid refresh token',
            ]);
    }

    /* ── Failures ──────────────────────────────────────────────────── */

    public function test_fails_with_wrong_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        ['refresh_token' => $refreshToken] = $this->loginTokens($user);
        $this->seedToken('user@example.com', 'correct-token');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'wrong-token',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ])->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('OldPass@123', $user->password_hash));
        $this->assertSame($refreshToken, $user->refresh_token);
    }

    public function test_fails_with_expired_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        ['refresh_token' => $refreshToken] = $this->loginTokens($user);
        $this->seedToken('user@example.com', 'valid-token-abc', minutesAgo: 61);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ])->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ]);

        // Expired token is cleaned up
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'user@example.com']);
        $user->refresh();
        $this->assertTrue(Hash::check('OldPass@123', $user->password_hash));
        $this->assertSame($refreshToken, $user->refresh_token);
    }

    public function test_fails_with_nonexistent_email(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'ghost@example.com',
            'token' => 'any-token',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ])->assertStatus(422);
    }

    /* ── Validation ────────────────────────────────────────────────── */

    public function test_requires_all_fields(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'token',
            'password' => 'NewPass@456',
            'password_confirmation' => 'DifferentPass@456',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error')
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'token',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error')
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_token_cannot_be_reused_after_successful_reset(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        $this->seedToken('user@example.com', 'valid-token-abc');

        $payload = [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();

        $this->postJson('/api/v1/auth/reset-password', $payload)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ]);
    }

    public function test_reset_token_is_valid_at_exact_expiry_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-04-23 12:00:00'));

        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('OldPass@123'),
        ]);

        $expiresInMinutes = config('auth.passwords.users.expire', 60);
        $this->seedToken('user@example.com', 'valid-token-abc', $expiresInMinutes);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'valid-token-abc',
            'password' => 'Boundary@456',
            'password_confirmation' => 'Boundary@456',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('Boundary@456', $user->password_hash));
    }

    private function loginTokens(User $user): array
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'OldPass@123',
        ]);

        return [
            'access_token' => $response->json('data.access_token'),
            'refresh_token' => $response->json('data.refresh_token'),
        ];
    }
}
