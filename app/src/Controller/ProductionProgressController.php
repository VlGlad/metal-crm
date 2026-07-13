<?php

namespace App\Controller;

use App\Entity\OtkInspection;
use App\Entity\ShiftTask;
use App\Entity\ShiftTaskItem;
use App\Entity\ShiftTaskSection;
use App\Entity\User;
use App\Repository\OtkInspectionRepository;
use App\Repository\ShiftTaskItemRepository;
use App\Repository\ShiftTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/production-progress')]
final class ProductionProgressController extends AbstractController
{
    private const MASTER_ROLES = [
        User::ROLE_MASTER,
        User::ROLE_CRO,
        User::ROLE_SSC,
        User::ROLE_CPO,
        User::ROLE_ADMIN,
    ];

    private const OTK_ROLES = [
        User::ROLE_CONTROLLER_OTK,
        User::ROLE_OTK_HEAD,
        User::ROLE_OTK_ENGINEER,
        User::ROLE_ADMIN,
    ];

    public function __construct(
        private readonly ShiftTaskRepository $shiftTasks,
        private readonly ShiftTaskItemRepository $shiftTaskItems,
        private readonly OtkInspectionRepository $inspections,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->canAccess($user)) {
            return $this->json(['message' => 'Нет доступа к производственному прогрессу.'], 403);
        }

        $items = [];
        $tasks = $this->shiftTasks->findBy([], ['date' => 'DESC', 'id' => 'DESC']);

        foreach ($tasks as $task) {
            foreach ($task->getSections() as $section) {
                foreach ($section->getItems() as $item) {
                    $inspection = $this->inspections->findOneBy(['shiftTaskItem' => $item], ['id' => 'DESC']);
                    $items[] = $this->serializeProgressItem($task, $section, $item, $inspection);
                }
            }
        }

        return $this->json([
            'items' => $items,
            'permissions' => [
                'canEditMaster' => $this->hasAnyRole($user, self::MASTER_ROLES),
                'canEditOtk' => $this->hasAnyRole($user, self::OTK_ROLES),
            ],
        ]);
    }

    #[Route('/items/{id}/otk', methods: ['POST'])]
    public function saveOtk(int $id, Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->hasAnyRole($user, self::OTK_ROLES)) {
            return $this->json(['message' => 'Нет права редактировать блок ОТК.'], 403);
        }

        $item = $this->shiftTaskItems->find($id);

        if (!$item) {
            return $this->json(['message' => 'Строка задания не найдена.'], 404);
        }

        $section = $item->getSection();
        $task = $section?->getShiftTask();

        if (!$section || !$task) {
            return $this->json(['message' => 'Некорректная строка задания.'], 422);
        }

        $data = $this->decodeJson($request);
        $inspection = $this->inspections->findOneBy(['shiftTaskItem' => $item], ['id' => 'DESC']) ?? new OtkInspection();
        $presentedQuantity = max(0, (int) ($data['presentedQuantity'] ?? $this->factQuantity($item)));
        $acceptedQuantity = max(0, (int) ($data['acceptedQuantity'] ?? 0));
        $rejectedQuantity = max(0, (int) ($data['rejectedQuantity'] ?? 0));

        if ($acceptedQuantity + $rejectedQuantity > $presentedQuantity) {
            return $this->json(['message' => 'Сумма принятых и забракованных изделий не может превышать предъявленное количество.'], 422);
        }

        $inspection
            ->setShiftTaskItem($item)
            ->setDate($task->getDate() ?? new \DateTimeImmutable())
            ->setName($item->getMark())
            ->setProject($task->getTitle())
            ->setPresentedQuantity($presentedQuantity)
            ->setAcceptedQuantity($acceptedQuantity)
            ->setRejectedQuantity($rejectedQuantity)
            ->setNonconformityDescription($this->nullableString($data['nonconformityDescription'] ?? null))
            ->setNonconformityActNumber($this->nullableString($data['nonconformityActNumber'] ?? null))
            ->setControllerName($this->nullableString($data['controllerName'] ?? $user?->getFullName()))
            ->setControllerUser($user);

        $status = trim((string) ($data['status'] ?? ''));

        if ($status === '') {
            $status = $rejectedQuantity > 0 ? 'rejected' : ($acceptedQuantity > 0 ? 'accepted' : 'draft');
        }

        $inspection->setStatus($status);

        if (in_array($status, ['accepted', 'rejected'], true)) {
            $inspection->setControllerSignedAt(new \DateTimeImmutable());
        }

        $inspection->touch();
        $this->entityManager->persist($inspection);
        $this->entityManager->flush();

        return $this->json($this->serializeProgressItem($task, $section, $item, $inspection));
    }

    private function serializeProgressItem(
        ShiftTask $task,
        ShiftTaskSection $section,
        ShiftTaskItem $item,
        ?OtkInspection $inspection
    ): array {
        $plan = $this->planQuantity($item);
        $fact = $this->factQuantity($item);

        return [
            'id' => $item->getId(),
            'task' => [
                'id' => $task->getId(),
                'date' => $task->getDate()?->format('Y-m-d'),
                'title' => $task->getTitle(),
                'workshop' => $task->getWorkshop(),
                'status' => $task->getStatus(),
            ],
            'section' => [
                'id' => $section->getId(),
                'name' => $section->getName(),
            ],
            'master' => [
                'mark' => $item->getMark(),
                'firstShiftPlan' => $item->getFirstShiftPlan(),
                'firstShiftFact' => $item->getFirstShiftFact(),
                'secondShiftPlan' => $item->getSecondShiftPlan(),
                'secondShiftFact' => $item->getSecondShiftFact(),
                'planQuantity' => $plan,
                'factQuantity' => $fact,
                'note' => $item->getNote(),
                'status' => $this->masterStatus($plan, $fact),
            ],
            'otk' => $inspection ? $this->serializeInspection($inspection) : null,
            'readiness' => $this->readinessStatus($fact, $inspection),
        ];
    }

    private function serializeInspection(OtkInspection $inspection): array
    {
        return [
            'id' => $inspection->getId(),
            'date' => $inspection->getDate()?->format('Y-m-d'),
            'presentedQuantity' => $inspection->getPresentedQuantity(),
            'acceptedQuantity' => $inspection->getAcceptedQuantity(),
            'rejectedQuantity' => $inspection->getRejectedQuantity(),
            'nonconformityDescription' => $inspection->getNonconformityDescription(),
            'nonconformityActNumber' => $inspection->getNonconformityActNumber(),
            'controllerName' => $inspection->getControllerName(),
            'controllerSignedAt' => $inspection->getControllerSignedAt()?->format(DATE_ATOM),
            'status' => $inspection->getStatus(),
            'updatedAt' => $inspection->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function planQuantity(ShiftTaskItem $item): int
    {
        return (int) $item->getFirstShiftPlan() + (int) $item->getSecondShiftPlan();
    }

    private function factQuantity(ShiftTaskItem $item): int
    {
        return (int) $item->getFirstShiftFact() + (int) $item->getSecondShiftFact();
    }

    private function masterStatus(int $plan, int $fact): string
    {
        if ($fact <= 0) {
            return 'not_started';
        }

        if ($plan > 0 && $fact >= $plan) {
            return 'done';
        }

        return 'in_progress';
    }

    private function readinessStatus(int $fact, ?OtkInspection $inspection): string
    {
        if ($fact <= 0) {
            return 'in_work';
        }

        if (!$inspection || $inspection->getStatus() === 'draft') {
            return 'waiting_otk';
        }

        if ($inspection->getStatus() === 'accepted' && $inspection->getRejectedQuantity() === 0) {
            return 'ready';
        }

        if ($inspection->getStatus() === 'rejected' || $inspection->getRejectedQuantity() > 0) {
            return 'rework_required';
        }

        return 'waiting_otk';
    }

    private function canAccess(?User $user): bool
    {
        return $this->hasAnyRole($user, array_values(array_unique(array_merge(self::MASTER_ROLES, self::OTK_ROLES))));
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

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    private function decodeJson(Request $request): array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }
}
