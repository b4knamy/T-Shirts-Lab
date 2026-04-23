<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();
    Cache::flush();
    Mail::fake();
  }

  /* ── Success ───────────────────────────────────────────────────── */

  public function test_sends_reset_link_for_existing_email(): void
  {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
      'email' => 'user@example.com',
    ]);

    $response->assertOk()
      ->assertJsonPath('success', true);

    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'user@example.com']);

    Mail::assertSent(\App\Mail\ResetPasswordMail::class, function ($mail) use ($user) {
      return $mail->hasTo($user->email);
    });
  }

  public function test_returns_200_for_non_existing_email_to_prevent_enumeration(): void
  {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
      'email' => 'nobody@example.com',
    ]);

    // Must still return 200 — not 404
    $response->assertOk()
      ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@example.com']);
    Mail::assertNothingSent();
  }

  public function test_overwrites_previous_token_when_requested_again(): void
  {
    User::factory()->create(['email' => 'user@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'user@example.com']);
    $first = DB::table('password_reset_tokens')->where('email', 'user@example.com')->value('token');

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'user@example.com']);
    $second = DB::table('password_reset_tokens')->where('email', 'user@example.com')->value('token');

    $this->assertNotEquals($first, $second);
    $this->assertDatabaseCount('password_reset_tokens', 1);
  }

  /* ── Validation ────────────────────────────────────────────────── */

  public function test_requires_email(): void
  {
    $this->postJson('/api/v1/auth/forgot-password', [])
      ->assertStatus(422)
      ->assertJsonPath('success', false);
  }

  public function test_requires_valid_email_format(): void
  {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
      ->assertStatus(422);
  }
}
