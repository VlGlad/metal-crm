<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\WorkshopByRoleResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MeController extends AbstractController
{
    #[Route('/api/me', methods: ['GET'])]
    public function me(WorkshopByRoleResolver $workshopResolver): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'email' => $user?->getUserIdentifier(),
            'roles' => $user?->getRoles(),
            'workshop' => $workshopResolver->resolve($user instanceof User ? $user : null),
        ]);
    }
}
