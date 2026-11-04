<?php

namespace App\Controller\ProductionOrder;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ProductionOrderAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrderResponsibleCandidatesController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ProductionOrderAccess $access,
    ) {
    }

    #[Route('/api/orders/responsible-candidates', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;
        if (!$this->access->canEditOrder($user)) {
            return $this->json(['message' => 'Нет права назначать ответственного.'], 403);
        }

        $candidates = array_filter(
            $this->users->findBy(['isActive' => true], ['fullName' => 'ASC']),
            fn (User $candidate) => $this->access->canBeResponsible($candidate)
        );

        return $this->json([
            'users' => array_values(array_map(
                fn (User $candidate) => [
                    'id' => $candidate->getId(),
                    'name' => $candidate->getFullName(),
                    'email' => $candidate->getEmail(),
                ],
                $candidates
            )),
        ]);
    }
}
