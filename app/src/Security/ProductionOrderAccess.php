<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\DocumentType;

final class ProductionOrderAccess
{
    public const PARTICIPANT_ROLES = [
        User::ROLE_PTO_HEAD,
        User::ROLE_PTO_DEPUTY_HEAD,
        User::ROLE_PTO_ENGINEER,
        User::ROLE_CHIEF_WELDER,
        User::ROLE_CHIEF_TECHNOLOGIST,
        User::ROLE_PO_HEAD,
        User::ROLE_GENERAL_DIRECTOR,
        User::ROLE_ECONOMIST,
        User::ROLE_SALES_HEAD,
        User::ROLE_SALES_MANAGER,
        User::ROLE_RESPONSIBLE_EXECUTOR,
        User::ROLE_LEAD_DESIGN_ENGINEER,
        User::ROLE_ADMIN,
    ];

    public function canViewOrders(?User $user): bool
    {
        return $this->hasAnyRole($user, self::PARTICIPANT_ROLES);
    }

    public function canCreateOrder(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            User::ROLE_PTO_HEAD,
            User::ROLE_RESPONSIBLE_EXECUTOR,
            User::ROLE_ADMIN,
        ]);
    }

    public function canEditOrder(?User $user): bool
    {
        return $this->canCreateOrder($user);
    }

    public function canIssueOrder(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            User::ROLE_GENERAL_DIRECTOR,
            User::ROLE_ADMIN,
        ]);
    }

    public function canViewDocument(?User $user, DocumentType $type): bool
    {
        return $this->hasAnyRole($user, match ($type) {
            DocumentType::KM_PROJECT => [
                User::ROLE_PTO_HEAD,
                User::ROLE_PTO_DEPUTY_HEAD,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_CHIEF_WELDER,
                User::ROLE_CHIEF_TECHNOLOGIST,
                User::ROLE_PO_HEAD,
                User::ROLE_RESPONSIBLE_EXECUTOR,
                User::ROLE_LEAD_DESIGN_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::ORDER_CALCULATION => [
                User::ROLE_GENERAL_DIRECTOR,
                User::ROLE_ECONOMIST,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::SPECIFICATION_AND_CONTRACTS => [
                User::ROLE_SALES_HEAD,
                User::ROLE_SALES_MANAGER,
                User::ROLE_ADMIN,
            ],
            default => [],
        });
    }

    public function canUploadDocument(?User $user, DocumentType $type): bool
    {
        return $this->hasAnyRole($user, match ($type) {
            DocumentType::KM_PROJECT => [
                User::ROLE_PTO_HEAD,
                User::ROLE_ADMIN,
            ],
            DocumentType::ORDER_CALCULATION => [
                User::ROLE_ECONOMIST,
                User::ROLE_PTO_ENGINEER,
                User::ROLE_ADMIN,
            ],
            DocumentType::SPECIFICATION_AND_CONTRACTS => [
                User::ROLE_SALES_HEAD,
                User::ROLE_SALES_MANAGER,
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
