<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/users/me';

    private function makeUser(string $password = 'OldPass@123'): User
    {
        return User::factory()->create([
            'password_hash' => Hash::make($password),
            'is_active' => true,
        ]);
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

    public function test_deletes_account_with_correct_current_password(): void
    {
        $user = $this->makeUser();
        $user->addresses()->create([
            'street' => 'Rua Teste',
            'number' => '1',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
        ]);

        ['access_token' => $accessToken] = $this->loginTokens($user);

        $this->deleteJson($this->endpoint, [
            'current_password' => 'OldPass@123',
        ], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $user->id]);
    }

    public function test_old_access_token_becomes_invalid_after_account_deletion(): void
    {
        $user = $this->makeUser();
        ['access_token' => $accessToken] = $this->loginTokens($user);

        $this->deleteJson($this->endpoint, [
            'current_password' => 'OldPass@123',
        ], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertOk();

        $this->getJson('/api/v1/users/me', [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertStatus(401);
    }

    public function test_old_refresh_token_becomes_invalid_after_account_deletion(): void
    {
        $user = $this->makeUser();
        ['access_token' => $accessToken, 'refresh_token' => $refreshToken] = $this->loginTokens($user);

        $this->deleteJson($this->endpoint, [
            'current_password' => 'OldPass@123',
        ], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertOk();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid refresh token',
            ]);
    }

    public function test_rejects_wrong_current_password(): void
    {
        $user = $this->makeUser();
        ['access_token' => $accessToken] = $this->loginTokens($user);

        $this->deleteJson($this->endpoint, [
            'current_password' => 'WrongPass@123',
        ], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_requires_current_password_field(): void
    {
        $user = $this->makeUser();
        ['access_token' => $accessToken] = $this->loginTokens($user);

        $this->deleteJson($this->endpoint, [], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error')
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_requires_authentication(): void
    {
        $this->deleteJson($this->endpoint, [
            'current_password' => 'OldPass@123',
        ])->assertStatus(401);
    }
}
