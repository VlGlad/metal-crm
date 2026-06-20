<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LlmQueryController extends AbstractController
{
    #[Route('/api/llm/query', methods: ['POST'])]
    public function __invoke(Request $request, ManagerRegistry $registry): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $sql = trim($payload['sql'] ?? '');

        if (!preg_match('/^\s*select\b/i', $sql)) {
            return new JsonResponse(['error' => 'Only SELECT is allowed'], 400);
        }

        if (preg_match('/;|insert|update|delete|drop|alter|create|truncate|grant|revoke/i', $sql)) {
            return new JsonResponse(['error' => 'Forbidden SQL keyword'], 400);
        }

        if (!preg_match('/\blimit\b/i', $sql)) {
            $sql .= ' LIMIT 100';
        }

        $conn = $registry->getConnection('llm');

        $conn->executeStatement('SET statement_timeout = 3000');

        $rows = $conn->fetchAllAssociative($sql);

        return new JsonResponse([
            'sql' => $sql,
            'rows' => $rows,
        ]);
    }
}
