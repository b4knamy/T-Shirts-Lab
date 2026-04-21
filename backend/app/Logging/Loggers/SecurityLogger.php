<?php

namespace App\Logging\Loggers;

use App\Models\User;

class SecurityLogger extends BaseLogger
{
    protected function channel(): string
    {
        return 'security';
    }

    public function unauthorizedAdminCreation(User $actor, string $targetRole): void
    {
        $this->warning('security.unauthorized_admin_creation', [
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'target_role' => $targetRole,
        ]);
    }

    public function superAdminModificationAttempt(User $actor, User $target): void
    {
        $this->warning('security.super_admin_modification_attempt', [
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'target_id' => $target->id,
        ]);
    }

    public function unauthorizedAdminModification(User $actor, User $target): void
    {
        $this->warning('security.unauthorized_admin_modification', [
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'target_id' => $target->id,
        ]);
    }

    public function unauthorizedAdminPromotion(User $actor, User $target): void
    {
        $this->warning('security.unauthorized_admin_promotion', [
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'target_id' => $target->id,
        ]);
    }

    public function webhookInvalidSignature(string $ip, string $error): void
    {
        $this->error('security.webhook_invalid_signature', [
            'ip' => $ip,
            'error' => $error,
        ]);
    }
}
