<?php

namespace App\Controller\TaskAssignment;

use App\Entity\TaskAssignment;
use App\Entity\TaskAssignmentEvent;
use App\Entity\User;
use App\Security\ProductionOrderAccess;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CompleteTaskAssignmentPartController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductionOrderAccess $orderAccess,
    ) {
    }

    #[Route('/api/task-assignments/{id}/complete-part', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $assignment = $this->entityManager->getRepository(TaskAssignment::class)->find($id);

        if (!$assignment instanceof TaskAssignment) {
            return $this->json(['message' => 'Поручение не найдено.'], 404);
        }

        $user = $this->getUser();
        $coExecutor = $user instanceof User ? $assignment->findCoExecutor($user) : null;
        $source = $assignment->getDocumentWorkflow()?->getProductionOrderFile();

        if (!$coExecutor || ($source && !$this->orderAccess->canViewDocument($user, $source->getDocumentType()))) {
            return $this->json(['message' => 'Поручение не найдено.'], 404);
        }

        if (!$coExecutor->canComplete()) {
            return $this->json(['message' => 'Свою часть можно отметить один раз, пока поручение назначено или в работе.'], 422);
        }

        $data = json_decode($request->getContent(), true);
        $comment = trim((string) (is_array($data) ? ($data['comment'] ?? '') : ''));

        $coExecutor->complete();
        $assignment->touch()->addEvent((new TaskAssignmentEvent())
            ->setEventType('co_executor_done')
            ->setComment($comment !== '' ? $comment : 'Дополнительный исполнитель выполнил свою часть.')
            ->setCreatedBy($user));
        $this->entityManager->flush();

        return $this->json([
            'id' => $assignment->getId(),
            'completedAt' => $coExecutor->getCompletedAt()?->format(DATE_ATOM),
        ]);
    }
}
