<?php

namespace App\Security;

use App\Entity\User;

final class WorkshopByRoleResolver
{
    private const WORKSHOP_BY_ROLE = [
        'ROLE_CRO' => 'Цех раскроя и обработки',
        'ROLE_SSC' => 'Сборочно-сварочный цех',
        'ROLE_CPO' => 'Цех покраски и отгрузки',
    ];

    public function resolve(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        foreach (self::WORKSHOP_BY_ROLE as $role => $workshop) {
            if ($user->hasRole($role)) {
                return $workshop;
            }
        }

        return null;
    }
}
