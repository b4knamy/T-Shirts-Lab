<?php

namespace App\Services;

use App\Logging\Loggers\AuthLogger;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use RuntimeException;

class AuthService
{
    public function __construct(private AuthLogger $authLogger) {}

    public function register(array $data): array
    {
        $user = User::create([
            ...$data,
            'password_hash' => Hash::make($data['password']),
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);

        $this->authLogger->userRegistered($user);

        return $this->issueTokens($user);
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->authLogger->loginFailed('User not found');

            throw new InvalidArgumentException('Invalid credentials', 401);
        }

        // Validate password using the configured hasher. Older records may have
        // hashes from a different algorithm, which BcryptHasher->check will
        // throw on. Catch that and fallback to PHP's password_verify. If the
        // fallback verifies, rehash with the current hasher and persist it so
        // future logins use the standard Laravel driver.
        try {
            $valid = Hash::check($password, $user->password_hash);
        } catch (RuntimeException $e) {
            // Example message: "This password does not use the Bcrypt algorithm."
            $valid = password_verify($password, $user->password_hash);

            if ($valid) {
                // Re-hash with the current hasher and save
                $user->update(['password_hash' => Hash::make($password)]);
            }
        }

        if (! $valid) {
            $this->authLogger->loginFailed('Invalid password', $user);

            throw new InvalidArgumentException('Invalid credentials', 401);
        }

        if (! $user->is_active) {
            $this->authLogger->loginFailed('Account disabled', $user);

            throw new InvalidArgumentException('Account is disabled', 403);
        }

        $this->authLogger->loginSuccess($user);

        return $this->issueTokens($user);
    }

    public function refresh(string $refreshToken): array
    {
        try {
            // Decode without touching the global JWTAuth token context.
            // getPayload() would validate expiry — refresh tokens have a
            // longer TTL set at issue time so this is fine.
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                throw new InvalidArgumentException('Invalid refresh token', 401);
            }

            $user = User::find($payload->get('sub'));

            if (! $user || $user->refresh_token !== $refreshToken) {
                throw new InvalidArgumentException('Invalid refresh token', 401);
            }

            $this->authLogger->refreshTokenSuccess($user);

            return $this->issueTokens($user);
        } catch (InvalidArgumentException $e) {

            $this->authLogger->refreshTokenFailed($e->getMessage());
            throw $e;
        } catch (JWTException) {

            $this->authLogger->refreshTokenFailed('Invalid JWT');
            throw new InvalidArgumentException('Invalid refresh token', 401);
        }
    }

    public function logout(User $user): void
    {
        try {
            $user->update(['refresh_token' => null]);
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException) {
            // Token already invalid, just clear the refresh token
            $user->update(['refresh_token' => null]);
        }

        $this->authLogger->logout($user);
    }

    public function sendPasswordResetLink(string $email): void
    {
        // Always respond the same way regardless of whether email exists (prevents enumeration)
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->authLogger->passwordResetRequested($email, found: false);
            return;
        }

        $token = Str::random(64);
        $expiresInMinutes = config('auth.passwords.users.expire', 60);

        DB::table('password_reset_tokens')->upsert(
            [
                'email'      => $email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ],
            uniqueBy: ['email'],
            update: ['token', 'created_at'],
        );

        $resetUrl = rtrim(config('app.frontend_url'), '/') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        Mail::to($user->email)->send(new ResetPasswordMail(
            resetUrl: $resetUrl,
            firstName: $user->first_name,
            expiresInMinutes: $expiresInMinutes,
        ));

        $this->authLogger->passwordResetRequested($email, found: true);
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            throw new InvalidArgumentException('Invalid or expired reset token.', 422);
        }

        $expiresInMinutes = config('auth.passwords.users.expire', 60);

        if (abs(now()->diffInMinutes(Carbon::parse($record->created_at))) > $expiresInMinutes) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new InvalidArgumentException('Invalid or expired reset token.', 422);
        }

        if (! Hash::check($token, $record->token)) {
            throw new InvalidArgumentException('Invalid or expired reset token.', 422);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new InvalidArgumentException('Invalid or expired reset token.', 422);
        }

        $user->update([
            'password_hash'  => Hash::make($newPassword),
            'refresh_token'  => null,   // invalidate all active sessions
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $this->authLogger->passwordReset($user);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password_hash)) {
            throw new InvalidArgumentException('Current password is incorrect.', 422);
        }

        $user->update([
            'password_hash' => Hash::make($newPassword),
            'refresh_token' => null,   // invalidate all active sessions
        ]);

        $this->authLogger->passwordChanged($user);
    }

    private function issueTokens(User $user): array
    {
        // Access token — use the configured TTL (jwt.ttl, e.g. 60 min)
        JWTAuth::factory()->setTTL(config('jwt.ttl'));
        $accessToken = JWTAuth::fromUser($user);

        // Refresh token — longer TTL, marked with a custom claim so we can
        // distinguish it from access tokens on the refresh endpoint.
        JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
        $refreshToken = JWTAuth::claims(['type' => 'refresh'])->fromUser($user);

        // Reset factory TTL back to default so nothing else is affected
        JWTAuth::factory()->setTTL(config('jwt.ttl'));

        $user->update(['refresh_token' => $refreshToken]);

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }
}
