<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ApiTokenController extends AbstractController
{
    #[Route('/api/token', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required.'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$user->isActive() || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials.'], 401);
        }

        $plainToken = bin2hex(random_bytes(32));

        $apiToken = new ApiToken();
        $apiToken
            ->setUser($user)
            ->setTokenHash(hash('sha256', $plainToken))
            ->setExpiresAt(new \DateTimeImmutable('+30 days'));

        $em->persist($apiToken);
        $em->flush();

        return $this->json([
            'token' => $plainToken,
            'type' => 'Bearer',
            'expires_in' => 30 * 24 * 60 * 60,
        ]);
    }
}
