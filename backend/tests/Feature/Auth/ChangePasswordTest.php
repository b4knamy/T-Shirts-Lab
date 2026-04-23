<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/users/me/password';

    private function makeUser(string $password = 'OldPass@123'): User
    {
        return User::factory()->create([
            'password_hash' => Hash::make($password),
            'is_active' => true,
        ]);
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_changes_password_with_correct_current_password(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $response = $this->postJson($this->endpoint, [
            'current_password'      => 'OldPass@123',
            'password'              => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully. Please log in again.',
            ]);
    }

    public function test_new_password_hash_is_updated_in_database(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $this->postJson($this->endpoint, [
            'current_password'      => 'OldPass@123',
            'password'              => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ], ['Authorization' => "Bearer {$token}"]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass@456', $user->password_hash));
    }

    public function test_refresh_token_is_invalidated_after_password_change(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $this->postJson($this->endpoint, [
            'current_password'      => 'OldPass@123',
            'password'              => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ], ['Authorization' => "Bearer {$token}"]);

        $user->refresh();
        $this->assertNull($user->refresh_token);
    }

    /* ── Validation errors ───────────────────────────────────────── */

    public function test_rejects_wrong_current_password(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $response = $this->postJson($this->endpoint, [
            'current_password'      => 'WrongPass@999',
            'password'              => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ]);
    }

    public function test_rejects_mismatched_password_confirmation(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $response = $this->postJson($this->endpoint, [
            'current_password'      => 'OldPass@123',
            'password'              => 'NewPass@456',
            'password_confirmation' => 'Different@999',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_rejects_password_shorter_than_8_characters(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $response = $this->postJson($this->endpoint, [
            'current_password'      => 'OldPass@123',
            'password'              => 'Short1',
            'password_confirmation' => 'Short1',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_rejects_missing_current_password_field(): void
    {
        $user = $this->makeUser();
        $token = $this->loginAs($user);

        $response = $this->postJson($this->endpoint, [
            'password'              => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /* ── Auth guard ──────────────────────────────────────────────── */

    public function test_requires_authentication(): void
    {
        $response = $this->postJson($this->endpoint, [
            'current_password'      => 'OldPass@123',
            'password'              => 'NewPass@456',
            'password_confirmation' => 'NewPass@456',
        ]);

        $response->assertStatus(401);
    }

    /* ── Helper ──────────────────────────────────────────────────── */

    private function loginAs(User $user): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'OldPass@123',
        ]);

        return $response->json('data.access_token');
    }
}
