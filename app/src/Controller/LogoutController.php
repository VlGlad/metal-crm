<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutController extends AbstractController
{
    #[Route('/api/logout', methods: ['POST'])]
    public function logout(
        Request $request,
        ApiTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $authorization = (string) $request->headers->get('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return $this->json(['message' => 'Bearer-токен не передан.'], 400);
        }

        $apiToken = $tokenRepository->findValidToken(trim($matches[1]));
        $currentUser = $this->getUser();

        if (
            $apiToken
            && $currentUser instanceof User
            && $apiToken->getUser()->getId() === $currentUser->getId()
        ) {
            $apiToken->revoke();
            $entityManager->flush();
        }

        return new JsonResponse(null, 204);
    }
}
