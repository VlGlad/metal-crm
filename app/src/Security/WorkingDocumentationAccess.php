<?php

namespace App\Security;

use App\Entity\User;

final class WorkingDocumentationAccess
{
    public const PARTICIPANT_ROLES = [
        User::ROLE_LEAD_DESIGN_ENGINEER,
        User::ROLE_DESIGN_ENGINEER,
        User::ROLE_ADMIN,
    ];

    public function canView(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach (self::PARTICIPANT_ROLES as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function canEdit(?User $user): bool
    {
        return $this->canView($user);
    }
}
