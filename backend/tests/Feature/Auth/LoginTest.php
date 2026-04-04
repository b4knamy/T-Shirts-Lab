<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/login';

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email'         => 'user@example.com',
            'password_hash' => Hash::make('Secret@123'),
            'is_active'     => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'user@example.com',
            'password' => 'Secret@123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'email', 'first_name', 'last_name', 'role', 'is_active'],
                    'access_token',
                    'refresh_token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data'    => [
                    'user' => ['email' => 'user@example.com'],
                ],
            ]);
    }

    public function test_login_returns_jwt_tokens(): void
    {
        User::factory()->create([
            'email'         => 'jwt@example.com',
            'password_hash' => Hash::make('Secret@123'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'jwt@example.com',
            'password' => 'Secret@123',
        ]);

        $data = $response->json('data');

        $this->assertNotEmpty($data['access_token']);
        $this->assertNotEmpty($data['refresh_token']);

        // Refresh token should be stored in DB (hidden attribute, access via getRawOriginal)
        $user = User::where('email', 'jwt@example.com')->first();
        $this->assertEquals($data['refresh_token'], $user->getRawOriginal('refresh_token'));
    }

    public function test_login_as_admin(): void
    {
        User::factory()->admin()->create([
            'email'         => 'admin@example.com',
            'password_hash' => Hash::make('Admin@123'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'admin@example.com',
            'password' => 'Admin@123',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => ['user' => ['role' => 'ADMIN']],
            ]);
    }

    public function test_login_as_super_admin(): void
    {
        User::factory()->superAdmin()->create([
            'email'         => 'super@example.com',
            'password_hash' => Hash::make('Super@123'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'super@example.com',
            'password' => 'Super@123',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => ['user' => ['role' => 'SUPER_ADMIN']],
            ]);
    }

    public function test_login_as_moderator(): void
    {
        User::factory()->moderator()->create([
            'email'         => 'mod@example.com',
            'password_hash' => Hash::make('Mod@123'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'mod@example.com',
            'password' => 'Mod@123',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => ['user' => ['role' => 'MODERATOR']],
            ]);
    }

    /* ── Invalid credentials ─────────────────────────────────────── */

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'         => 'user@example.com',
            'password_hash' => Hash::make('Secret@123'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email'    => 'ghost@example.com',
            'password' => 'Secret@123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /* ── Disabled account ────────────────────────────────────────── */

    public function test_login_fails_when_account_is_disabled(): void
    {
        User::factory()->inactive()->create([
            'email'         => 'disabled@example.com',
            'password_hash' => Hash::make('Secret@123'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email'    => 'disabled@example.com',
            'password' => 'Secret@123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Account is disabled',
            ]);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_login_fails_without_email(): void
    {
        $response = $this->postJson($this->endpoint, [
            'password' => 'Secret@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_without_password(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email'    => 'not-an-email',
            'password' => 'Secret@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_empty_body(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
