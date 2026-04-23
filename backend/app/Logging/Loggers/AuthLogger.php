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

    public function loginFailed(?string $reason, ?User $user = null)
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
            'reason' => $reason,
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

    public function passwordResetRequested(string $email, bool $found): void
    {
        $this->info('auth.password_reset_requested', [
            'email' => $email,
            'user_found' => $found,
        ]);
    }

    public function passwordReset(User $user): void
    {
        $this->info('auth.password_reset', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function passwordChanged(User $user): void
    {
        $this->info('auth.password_changed', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
