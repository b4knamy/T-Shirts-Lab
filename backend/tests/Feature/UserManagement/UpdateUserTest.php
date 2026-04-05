<?php

namespace Tests\Feature\UserManagement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    private function authAs(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    private function endpoint(string $id): string
    {
        return "/api/v1/users/{$id}";
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  SUPER_ADMIN permissions
       * ══════════════════════════════════════════════════════════════ */

    public function test_super_admin_can_change_admin_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $token = $this->authAs($superAdmin);

        $response = $this->patchJson($this->endpoint($admin->id), [
            'role' => 'MODERATOR',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['role' => 'MODERATOR'],
            ]);

        $admin->refresh();
        $this->assertEquals('MODERATOR', $admin->role);
    }

    public function test_super_admin_can_promote_to_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $mod = User::factory()->moderator()->create();
        $token = $this->authAs($superAdmin);

        $response = $this->patchJson($this->endpoint($mod->id), [
            'role' => 'ADMIN',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['role' => 'ADMIN']]);
    }

    public function test_super_admin_can_demote_admin_to_customer(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $token = $this->authAs($superAdmin);

        $response = $this->patchJson($this->endpoint($admin->id), [
            'role' => 'CUSTOMER',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['role' => 'CUSTOMER']]);
    }

    public function test_super_admin_can_deactivate_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $token = $this->authAs($superAdmin);

        $response = $this->patchJson($this->endpoint($admin->id), [
            'is_active' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['is_active' => false]]);
    }

    public function test_super_admin_can_reactivate_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->inactive()->create();
        $token = $this->authAs($superAdmin);

        $response = $this->patchJson($this->endpoint($user->id), [
            'is_active' => true,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['is_active' => true]]);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  ADMIN permissions
       * ══════════════════════════════════════════════════════════════ */

    public function test_admin_can_change_moderator_role(): void
    {
        $admin = User::factory()->admin()->create();
        $mod = User::factory()->moderator()->create();
        $token = $this->authAs($admin);

        $response = $this->patchJson($this->endpoint($mod->id), [
            'role' => 'CUSTOMER',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['role' => 'CUSTOMER']]);
    }

    public function test_admin_can_deactivate_moderator(): void
    {
        $admin = User::factory()->admin()->create();
        $mod = User::factory()->moderator()->create();
        $token = $this->authAs($admin);

        $response = $this->patchJson($this->endpoint($mod->id), [
            'is_active' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['is_active' => false]]);
    }

    public function test_admin_can_deactivate_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $token = $this->authAs($admin);

        $response = $this->patchJson($this->endpoint($customer->id), [
            'is_active' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['is_active' => false]]);
    }

    public function test_admin_cannot_modify_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $token = $this->authAs($admin1);

        $response = $this->patchJson($this->endpoint($admin2->id), [
            'role' => 'MODERATOR',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only Super Admins can modify Admin users',
            ]);
    }

    public function test_admin_cannot_promote_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $mod = User::factory()->moderator()->create();
        $token = $this->authAs($admin);

        $response = $this->patchJson($this->endpoint($mod->id), [
            'role' => 'ADMIN',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only Super Admins can promote users to Admin',
            ]);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  Protected accounts
       * ══════════════════════════════════════════════════════════════ */

    public function test_cannot_modify_super_admin(): void
    {
        $superAdmin1 = User::factory()->superAdmin()->create();
        $superAdmin2 = User::factory()->superAdmin()->create();
        $token = $this->authAs($superAdmin1);

        $response = $this->patchJson($this->endpoint($superAdmin2->id), [
            'role' => 'ADMIN',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot modify Super Admin accounts',
            ]);
    }

    public function test_cannot_modify_self(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $this->authAs($admin);

        $response = $this->patchJson($this->endpoint($admin->id), [
            'role' => 'MODERATOR',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You cannot modify your own account here',
            ]);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  Access control
       * ══════════════════════════════════════════════════════════════ */

    public function test_customer_cannot_update_users(): void
    {
        $customer = User::factory()->create();
        $target = User::factory()->create();
        $token = $this->authAs($customer);

        $response = $this->patchJson($this->endpoint($target->id), [
            'is_active' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_update_users(): void
    {
        $user = User::factory()->create();

        $response = $this->patchJson($this->endpoint($user->id), [
            'is_active' => false,
        ]);

        $response->assertStatus(401);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  Validation
       * ══════════════════════════════════════════════════════════════ */

    public function test_update_fails_with_invalid_role(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->create();
        $token = $this->authAs($admin);

        $response = $this->patchJson($this->endpoint($target->id), [
            'role' => 'SUPER_ADMIN',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_update_fails_with_nonexistent_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->authAs($admin);

        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->patchJson($this->endpoint($fakeId), [
            'is_active' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(404);
    }

    public function test_update_role_and_active_together(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->moderator()->create(['is_active' => true]);
        $token = $this->authAs($superAdmin);

        $response = $this->patchJson($this->endpoint($user->id), [
            'role' => 'CUSTOMER',
            'is_active' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'role' => 'CUSTOMER',
                    'is_active' => false,
                ],
            ]);
    }
}
