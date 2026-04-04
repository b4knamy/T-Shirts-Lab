<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/auth/register';

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_register_with_valid_data(): void
  {
    $payload = [
      'email'      => 'newuser@example.com',
      'password'   => 'Secret@123',
      'first_name' => 'John',
      'last_name'  => 'Doe',
      'phone'      => '(11) 99999-0000',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(201)
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
        'data'    => [
          'user' => [
            'email'      => 'newuser@example.com',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'role'       => 'CUSTOMER',
            'is_active'  => true,
          ],
        ],
      ]);

    $this->assertDatabaseHas('users', [
      'email'      => 'newuser@example.com',
      'first_name' => 'John',
      'last_name'  => 'Doe',
      'role'       => 'CUSTOMER',
      'is_active'  => true,
    ]);
  }

  public function test_register_without_optional_phone(): void
  {
    $payload = [
      'email'      => 'nophone@example.com',
      'password'   => 'Secret@123',
      'first_name' => 'Jane',
      'last_name'  => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(201)
      ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', [
      'email' => 'nophone@example.com',
      'phone' => null,
    ]);
  }

  public function test_register_returns_valid_jwt_tokens(): void
  {
    $payload = [
      'email'      => 'jwt@example.com',
      'password'   => 'Secret@123',
      'first_name' => 'JWT',
      'last_name'  => 'User',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $data = $response->json('data');

    $this->assertNotEmpty($data['access_token']);
    $this->assertNotEmpty($data['refresh_token']);

    // refresh_token should be saved in DB (hidden attribute, access via getRawOriginal)
    $user = User::where('email', 'jwt@example.com')->first();
    $this->assertEquals($data['refresh_token'], $user->getRawOriginal('refresh_token'));
  }

  public function test_registered_user_has_customer_role(): void
  {
    $payload = [
      'email'      => 'rolecheck@example.com',
      'password'   => 'Secret@123',
      'first_name' => 'Role',
      'last_name'  => 'Check',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertJson([
      'data' => ['user' => ['role' => 'CUSTOMER']],
    ]);
  }

  /* ── Validation: missing fields ──────────────────────────────── */

  public function test_register_fails_without_email(): void
  {
    $payload = [
      'password'   => 'Secret@123',
      'first_name' => 'John',
      'last_name'  => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJson(['success' => false])
      ->assertJsonValidationErrors(['email']);
  }

  public function test_register_fails_without_password(): void
  {
    $payload = [
      'email'      => 'nopass@example.com',
      'first_name' => 'John',
      'last_name'  => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['password']);
  }

  public function test_register_fails_without_first_name(): void
  {
    $payload = [
      'email'    => 'nofn@example.com',
      'password' => 'Secret@123',
      'last_name' => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['first_name']);
  }

  public function test_register_fails_without_last_name(): void
  {
    $payload = [
      'email'      => 'noln@example.com',
      'password'   => 'Secret@123',
      'first_name' => 'John',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['last_name']);
  }

  /* ── Validation: invalid data ────────────────────────────────── */

  public function test_register_fails_with_invalid_email(): void
  {
    $payload = [
      'email'      => 'not-an-email',
      'password'   => 'Secret@123',
      'first_name' => 'John',
      'last_name'  => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['email']);
  }

  public function test_register_fails_with_short_password(): void
  {
    $payload = [
      'email'      => 'short@example.com',
      'password'   => '1234567',  // 7 chars, min is 8
      'first_name' => 'John',
      'last_name'  => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['password']);
  }

  public function test_register_fails_with_duplicate_email(): void
  {
    User::factory()->create(['email' => 'existing@example.com']);

    $payload = [
      'email'      => 'existing@example.com',
      'password'   => 'Secret@123',
      'first_name' => 'John',
      'last_name'  => 'Doe',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['email']);
  }

  public function test_register_fails_with_empty_body(): void
  {
    $response = $this->postJson($this->endpoint, []);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['email', 'password', 'first_name', 'last_name']);
  }
}
