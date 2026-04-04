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
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            $user    = User::find($payload->get('sub'));

            if (!$user || $user->refresh_token !== $refreshToken) {
                throw new \InvalidArgumentException('Invalid refresh token', 401);
            }

            return $this->issueTokens($user);
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
        $accessToken = JWTAuth::fromUser($user);

        // The jwt-auth manager does not expose setTTL directly.
        // setTTL is available on the Payload Factory, so set it there
        // before generating the refresh token.
        JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));

        $refreshToken = JWTAuth::claims(['type' => 'refresh'])
            ->fromUser($user);

        $user->update(['refresh_token' => $refreshToken]);

        return [
            'user'          => $user,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }
}
