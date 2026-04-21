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
        ]);
    }

    public function loginFailed(string|null $reason, User|null $user = null)
    {
        $this->warning('auth.login_failed', [
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

    public function staffCreated(User $creator, User $newUser): void
    {
        $this->info('auth.staff_created', [
            'created_by' => $creator->id,
            'new_user_id' => $newUser->id,
            'email' => $newUser->email,
            'role' => $newUser->role,
        ]);
    }

    public function userUpdatedByAdmin(User $admin, User $target, array $changedKeys, ?string $roleChanged): void
    {
        $this->info('auth.user_updated_by_admin', [
            'updated_by' => $admin->id,
            'target_id' => $target->id,
            'target_email' => $target->email,
            'changes' => $changedKeys,
            'role_changed' => $roleChanged,
        ]);
    }
}
