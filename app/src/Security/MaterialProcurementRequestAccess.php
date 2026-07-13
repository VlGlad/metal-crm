<?php

namespace App\Security;

use App\Entity\User;

final class MaterialProcurementRequestAccess
{
    public const PARTICIPANT_ROLES = [
        User::ROLE_PO_HEAD,
        User::ROLE_DEPARTMENT_HEAD,
        User::ROLE_OMTS_HEAD,
        User::ROLE_OMTS_DEPUTY_HEAD,
        User::ROLE_WAREHOUSE_MANAGER,
        User::ROLE_METAL_WAREHOUSE_MANAGER,
        User::ROLE_ADMIN,
    ];

    public function canAccess(?User $user): bool
    {
        return $this->hasAnyRole($user, self::PARTICIPANT_ROLES);
    }

    public function canCreate(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            User::ROLE_PO_HEAD,
            User::ROLE_DEPARTMENT_HEAD,
            User::ROLE_ADMIN,
        ]);
    }

    public function canEdit(?User $user): bool
    {
        return $this->canCreate($user);
    }

    public function canSubmit(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            User::ROLE_PO_HEAD,
            User::ROLE_DEPARTMENT_HEAD,
            User::ROLE_ADMIN,
        ]);
    }

    public function canAccept(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            User::ROLE_OMTS_HEAD,
            User::ROLE_OMTS_DEPUTY_HEAD,
            User::ROLE_ADMIN,
        ]);
    }

    public function canMarkPurchased(?User $user): bool
    {
        return $this->canAccept($user);
    }

    public function canMarkReceived(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_METAL_WAREHOUSE_MANAGER,
            User::ROLE_OMTS_HEAD,
            User::ROLE_ADMIN,
        ]);
    }

    private function hasAnyRole(?User $user, array $roles): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}

