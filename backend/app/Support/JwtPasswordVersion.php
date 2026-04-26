<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class JwtPasswordVersion
{
    public static function matches(Request $request, User $user): bool
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return false;
        }

        $segments = explode('.', $token);

        if (! isset($segments[1])) {
            return false;
        }

        $decodedPayload = self::base64UrlDecode($segments[1]);

        if ($decodedPayload === false) {
            return false;
        }

        $payload = json_decode($decodedPayload, true);

        if (! is_array($payload)) {
            return false;
        }

        $tokenPasswordVersion = $payload['pwdv'] ?? null;

        return is_string($tokenPasswordVersion)
            && hash_equals($user->passwordTokenVersion(), $tokenPasswordVersion);
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
