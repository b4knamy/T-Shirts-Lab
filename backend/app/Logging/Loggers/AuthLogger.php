<?php

namespace App\Logging\Loggers;

use App\Models\User;

class AuthLogger extends BaseLogger
{
    protected function channel(): string
    {
        return 'auth';
    }

    public function userRegistered(User $user)
    {
        $this->info('auth.registered', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function loginFailed(string|null $reason, string|null $email, User|null $user = null)
    {
        $this->warning('auth.login_failed', [
            'email' => $email ?? null,
            'reason' => $reason ?? null,
            'user_id' => $user?->id ?? null,
        ]);
    }

    public function loginSuccess(User $user)
    {
        $this->info('auth.login_success', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function refreshTokenFailed(string $reason)
    {
        $this->warning('auth.refresh_token_failed', [
            'reason' => $reason
        ]);
    }

    public function refreshTokenSuccess(User $user)
    {
        $this->info('auth.refresh_token_success', ['user_id' => $user->id]);
    }

    public function logout(User $user)
    {
        $this->info('auth.logout', ['user_id' => $user->id]);
    }
}
