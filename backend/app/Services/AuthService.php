<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'email'             => $data['email'],
            'password_hash'     => Hash::make($data['password']),
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'phone'             => $data['phone'] ?? null,
            'role'              => 'CUSTOMER',
            'is_active'         => true,
        ]);

        return $this->issueTokens($user);
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \InvalidArgumentException('Invalid credentials', 401);
        }

        // Validate password using the configured hasher. Older records may have
        // hashes from a different algorithm, which BcryptHasher->check will
        // throw on. Catch that and fallback to PHP's password_verify. If the
        // fallback verifies, rehash with the current hasher and persist it so
        // future logins use the standard Laravel driver.
        try {
            $valid = Hash::check($password, $user->password_hash);
        } catch (\RuntimeException $e) {
            // Example message: "This password does not use the Bcrypt algorithm."
            $valid = password_verify($password, $user->password_hash);

            if ($valid) {
                // Re-hash with the current hasher and save
                $user->update(['password_hash' => Hash::make($password)]);
            }
        }

        if (!$valid) {
            throw new \InvalidArgumentException('Invalid credentials', 401);
        }

        if (!$user->is_active) {
            throw new \InvalidArgumentException('Account is disabled', 403);
        }

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
                throw new \InvalidArgumentException('Invalid refresh token', 401);
            }

            $user = User::find($payload->get('sub'));

            if (!$user || $user->refresh_token !== $refreshToken) {
                throw new \InvalidArgumentException('Invalid refresh token', 401);
            }

            return $this->issueTokens($user);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (JWTException) {
            throw new \InvalidArgumentException('Invalid refresh token', 401);
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
            'user'          => $user,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }
}
