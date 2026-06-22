<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\DocumentType;

final class MonthlyProductionPlanAccess
{
    public const PARTICIPANT_ROLES = [
        User::ROLE_PTO_HEAD,
        User::ROLE_PTO_DEPUTY_HEAD,
        User::ROLE_PTO_ENGINEER,
        User::ROLE_PO_HEAD,
        User::ROLE_PRODUCTION_HEAD,
        User::ROLE_OMTS_HEAD,
        User::ROLE_ADMIN,
    ];

    public function canViewPlans(?User $user): bool
    {
        return $this->hasAnyRole($user, self::PARTICIPANT_ROLES);
    }

    public function canCreatePlan(?User $user): bool
    {
        return $this->canViewPlans($user);
    }

    public function canEditPlan(?User $user): bool
    {
        return $this->canViewPlans($user);
    }

    public function canViewDocument(?User $user, DocumentType $type): bool
    {
        return $this->hasAnyRole($user, match ($type) {
            DocumentType::PRODUCTION_PLAN => [
                User::ROLE_PTO_HEAD,
                User::ROLE_PTO_DEPUTY_HEAD,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::PRODUCTION_SCHEDULE => [
                User::ROLE_PO_HEAD,
                User::ROLE_PTO_DEPUTY_HEAD,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::MATERIAL_REQUEST => [
                User::ROLE_PRODUCTION_HEAD,
                User::ROLE_OMTS_HEAD,
                User::ROLE_ADMIN,
            ],
            default => [],
        });
    }

    public function canUploadDocument(?User $user, DocumentType $type): bool
    {
        return $this->hasAnyRole($user, match ($type) {
            DocumentType::PRODUCTION_PLAN => [
                User::ROLE_PTO_HEAD,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::PRODUCTION_SCHEDULE => [
                User::ROLE_PO_HEAD,
                User::ROLE_PTO_DEPUTY_HEAD,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::MATERIAL_REQUEST => [
                User::ROLE_PRODUCTION_HEAD,
                User::ROLE_OMTS_HEAD,
                User::ROLE_ADMIN,
            ],
            default => [],
        });
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
