<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function authAs(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /* ── GET /users/me ───────────────────────────────────────────── */

    public function test_get_profile_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $response = $this->getJson('/api/v1/users/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'email', 'first_name', 'last_name', 'role', 'is_active', 'addresses'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_get_profile_includes_addresses(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create([
            'street' => 'Rua X',
            'number' => '100',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
        ]);

        $token = $this->authAs($user);

        $response = $this->getJson('/api/v1/users/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();

        $addresses = $response->json('data.addresses');
        $this->assertCount(1, $addresses);
        $this->assertEquals('Rua X', $addresses[0]['street']);
    }

    public function test_get_profile_fails_without_auth(): void
    {
        $response = $this->getJson('/api/v1/users/me');

        $response->assertStatus(401);
    }

    /* ── PATCH /users/me ─────────────────────────────────────────── */

    public function test_update_profile_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Old']);
        $token = $this->authAs($user);

        $response = $this->patchJson('/api/v1/users/me', [
            'first_name' => 'New',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['first_name' => 'New'],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'New',
        ]);
    }

    public function test_update_profile_last_name(): void
    {
        $user = User::factory()->create(['last_name' => 'Old']);
        $token = $this->authAs($user);

        $response = $this->patchJson('/api/v1/users/me', [
            'last_name' => 'Updated',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => ['last_name' => 'Updated'],
            ]);
    }

    public function test_update_profile_phone(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $response = $this->patchJson('/api/v1/users/me', [
            'phone' => '(21) 99999-1111',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '(21) 99999-1111',
        ]);
    }

    public function test_update_profile_multiple_fields(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $response = $this->patchJson('/api/v1/users/me', [
            'first_name' => 'Multi',
            'last_name' => 'Update',
            'phone' => '(11) 12345-6789',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'first_name' => 'Multi',
                    'last_name' => 'Update',
                ],
            ]);
    }

    public function test_update_profile_with_empty_body_succeeds(): void
    {
        $user = User::factory()->create(['first_name' => 'Same']);
        $token = $this->authAs($user);

        $response = $this->patchJson('/api/v1/users/me', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        // Nothing changes, but request should not fail
        $response->assertOk()
            ->assertJson(['data' => ['first_name' => 'Same']]);
    }

    public function test_update_profile_cannot_change_role(): void
    {
        $user = User::factory()->create(['role' => 'CUSTOMER']);
        $token = $this->authAs($user);

        $this->patchJson('/api/v1/users/me', [
            'role' => 'ADMIN',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $user->refresh();
        $this->assertEquals('CUSTOMER', $user->role);
    }

    public function test_update_profile_cannot_change_email(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);
        $token = $this->authAs($user);

        $this->patchJson('/api/v1/users/me', [
            'email' => 'hacked@example.com',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $user->refresh();
        $this->assertEquals('original@example.com', $user->email);
    }

    public function test_update_profile_fails_without_auth(): void
    {
        $response = $this->patchJson('/api/v1/users/me', [
            'first_name' => 'Hacker',
        ]);

        $response->assertStatus(401);
    }
}
