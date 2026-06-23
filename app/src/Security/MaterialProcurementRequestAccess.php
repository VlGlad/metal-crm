<?php

namespace App\Security;

use App\Entity\User;

final class MaterialProcurementRequestAccess
{
    public const PARTICIPANT_ROLES = [
        User::ROLE_PO_HEAD,
        User::ROLE_DEPARTMENT_HEAD,
        User::ROLE_ADMIN,
    ];

    public function canAccess(?User $user): bool
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
}
