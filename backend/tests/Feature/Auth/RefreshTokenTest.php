<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/auth/refresh';

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_refresh_with_valid_token(): void
  {
    $user = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    // Login first to get a valid refresh token
    $loginResponse = $this->postJson('/api/v1/auth/login', [
      'email'    => $user->email,
      'password' => 'Secret@123',
    ]);

    $oldRefreshToken = $loginResponse->json('data.refresh_token');

    $response = $this->postJson($this->endpoint, [
      'refresh_token' => $oldRefreshToken,
    ]);

    $response->assertOk()
      ->assertJsonStructure([
        'success',
        'message',
        'data' => ['access_token', 'refresh_token'],
      ])
      ->assertJson([
        'success' => true,
        'message' => 'Token refreshed',
      ]);

    // New tokens should be different from old ones
    $newRefreshToken = $response->json('data.refresh_token');
    $this->assertNotEquals($oldRefreshToken, $newRefreshToken);

    // DB should be updated with the new refresh token
    $user->refresh();
    $this->assertEquals($newRefreshToken, $user->refresh_token);
  }

  public function test_refresh_returns_new_access_token(): void
  {
    $user = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
      'email'    => $user->email,
      'password' => 'Secret@123',
    ]);

    $oldAccessToken  = $loginResponse->json('data.access_token');
    $oldRefreshToken = $loginResponse->json('data.refresh_token');

    $response = $this->postJson($this->endpoint, [
      'refresh_token' => $oldRefreshToken,
    ]);

    $newAccessToken = $response->json('data.access_token');
    $this->assertNotEquals($oldAccessToken, $newAccessToken);
  }

  /* ── Invalid tokens ──────────────────────────────────────────── */

  public function test_refresh_fails_with_invalid_token(): void
  {
    $response = $this->postJson($this->endpoint, [
      'refresh_token' => 'totally.invalid.token',
    ]);

    $response->assertStatus(401)
      ->assertJson([
        'success' => false,
        'message' => 'Invalid refresh token',
      ]);
  }

  public function test_refresh_fails_with_mismatched_token(): void
  {
    $user = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    // Login to get a valid token
    $loginResponse = $this->postJson('/api/v1/auth/login', [
      'email'    => $user->email,
      'password' => 'Secret@123',
    ]);

    $validRefreshToken = $loginResponse->json('data.refresh_token');

    // Tamper the stored token so DB doesn't match
    $user->update(['refresh_token' => 'tampered_value']);

    $response = $this->postJson($this->endpoint, [
      'refresh_token' => $validRefreshToken,
    ]);

    $response->assertStatus(401)
      ->assertJson([
        'success' => false,
        'message' => 'Invalid refresh token',
      ]);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_refresh_fails_without_token(): void
  {
    $response = $this->postJson($this->endpoint, []);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['refresh_token']);
  }
}
