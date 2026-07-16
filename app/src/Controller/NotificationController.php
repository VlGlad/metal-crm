<?php

namespace App\Controller;

use App\Entity\DocumentApprovalStep;
use App\Entity\DocumentWorkflow;
use App\Entity\TaskAssignment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
final class NotificationController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    #[Route('/counts', methods: ['GET'])]
    public function counts(): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->json(['documentWorkflows' => 0, 'taskAssignments' => 0]);
        }

        return $this->json([
            'documentWorkflows' => $this->pendingDocumentCount($user),
            'taskAssignments' => $this->newAssignmentCount($user),
        ]);
    }

    private function pendingDocumentCount(User $user): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT workflow.id)')
            ->from(DocumentApprovalStep::class, 'step')
            ->join('step.workflow', 'workflow')
            ->where('step.approver = :user')
            ->andWhere('step.status = :stepStatus')
            ->andWhere('workflow.status = :workflowStatus')
            ->setParameter('user', $user)
            ->setParameter('stepStatus', DocumentApprovalStep::STATUS_PENDING)
            ->setParameter('workflowStatus', DocumentWorkflow::STATUS_IN_APPROVAL)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function newAssignmentCount(User $user): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(task.id)')
            ->from(TaskAssignment::class, 'task')
            ->where('task.responsible = :user')
            ->andWhere('task.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', TaskAssignment::STATUS_ASSIGNED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }
}
