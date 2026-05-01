<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/test')]
final class ApiController extends AbstractController
{
    #[Route('/hello', name: 'api_hello', methods: ['GET'])]
    public function hello(): JsonResponse
    {
        return $this->json([
            'message' => 'Привет с Symfony API',
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    public function contact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return $this->json([
            'success' => true,
            'message' => 'Данные получены Symfony API',
            'received' => $data,
        ]);
    }
}