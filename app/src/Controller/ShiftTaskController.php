<?php

namespace App\Controller;

use App\Entity\ShiftTask;
use App\Entity\ShiftTaskItem;
use App\Entity\ShiftTaskSection;
use App\Repository\ShiftTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/shift-tasks')]
class ShiftTaskController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShiftTaskRepository $shiftTaskRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $tasks = $this->shiftTaskRepository->findBy([], ['id' => 'DESC']);

        return $this->json(array_map(
            fn (ShiftTask $task) => $this->serializeTask($task),
            $tasks
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $task = $this->shiftTaskRepository->find($id);

        if (!$task) {
            return $this->json(['message' => 'Задание не найдено.'], 404);
        }

        return $this->json($this->serializeTask($task));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        if ($error = $this->validatePayload($data)) {
            return $this->json(['message' => $error], 422);
        }

        $task = new ShiftTask();
        $this->fillTaskFromPayload($task, $data);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json($this->serializeTask($task), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $task = $this->shiftTaskRepository->find($id);

        if (!$task) {
            return $this->json(['message' => 'Задание не найдено.'], 404);
        }

        $data = $this->decodeJson($request);

        if ($error = $this->validatePayload($data)) {
            return $this->json(['message' => $error], 422);
        }

        $this->fillTaskFromPayload($task, $data);
        $task->touch();

        $this->entityManager->flush();

        return $this->json($this->serializeTask($task));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $task = $this->shiftTaskRepository->find($id);

        if (!$task) {
            return $this->json(['message' => 'Задание не найдено.'], 404);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(['message' => 'Задание удалено.']);
    }

    private function fillTaskFromPayload(ShiftTask $task, array $data): void
    {
        $task
            ->setDate(new \DateTimeImmutable($data['date']))
            ->setTitle(trim((string) ($data['title'] ?? '')))
            ->setWorkshop(trim((string) ($data['workshop'] ?? '')))
            ->setStatus((string) ($data['status'] ?? 'draft'));

        $task->clearSections();

        foreach (($data['sections'] ?? []) as $sectionIndex => $sectionData) {
            $section = new ShiftTaskSection();
            $section
                ->setName(trim((string) ($sectionData['name'] ?? '')))
                ->setSortOrder($sectionIndex);

            foreach (($sectionData['items'] ?? []) as $itemIndex => $itemData) {
                $item = new ShiftTaskItem();
                $item
                    ->setMark(trim((string) ($itemData['mark'] ?? '')))
                    ->setFirstShiftPlan($this->nullableInt($itemData['firstShiftPlan'] ?? null))
                    ->setFirstShiftFact($this->nullableInt($itemData['firstShiftFact'] ?? null))
                    ->setSecondShiftPlan($this->nullableInt($itemData['secondShiftPlan'] ?? null))
                    ->setSecondShiftFact($this->nullableInt($itemData['secondShiftFact'] ?? null))
                    ->setNote($this->nullableString($itemData['note'] ?? null))
                    ->setSortOrder($itemIndex);

                $section->addItem($item);
            }

            $task->addSection($section);
        }
    }

    private function validatePayload(array $data): ?string
    {
        if (empty($data['date'])) {
            return 'Укажите дату задания.';
        }

        try {
            new \DateTimeImmutable($data['date']);
        } catch (\Throwable) {
            return 'Некорректная дата задания.';
        }

        if (empty(trim((string) ($data['workshop'] ?? '')))) {
            return 'Укажите цех.';
        }

        if (empty($data['sections']) || !is_array($data['sections'])) {
            return 'Добавьте хотя бы один участок.';
        }

        foreach ($data['sections'] as $section) {
            if (empty(trim((string) ($section['name'] ?? '')))) {
                return 'Укажите название каждого участка.';
            }

            if (empty($section['items']) || !is_array($section['items'])) {
                return 'В каждом участке должна быть хотя бы одна строка.';
            }

            foreach ($section['items'] as $item) {
                if (empty(trim((string) ($item['mark'] ?? '')))) {
                    return 'Заполните марку/деталь во всех строках.';
                }
            }
        }

        return null;
    }

    private function serializeTask(ShiftTask $task): array
    {
        return [
            'id' => $task->getId(),
            'date' => $task->getDate()?->format('Y-m-d'),
            'title' => $task->getTitle(),
            'workshop' => $task->getWorkshop(),
            'status' => $task->getStatus(),
            'sections' => array_map(
                fn (ShiftTaskSection $section) => $this->serializeSection($section),
                $task->getSections()->toArray()
            ),
        ];
    }

    private function serializeSection(ShiftTaskSection $section): array
    {
        return [
            'id' => $section->getId(),
            'name' => $section->getName(),
            'items' => array_map(
                fn (ShiftTaskItem $item) => $this->serializeItem($item),
                $section->getItems()->toArray()
            ),
        ];
    }

    private function serializeItem(ShiftTaskItem $item): array
    {
        return [
            'id' => $item->getId(),
            'mark' => $item->getMark(),
            'firstShiftPlan' => $item->getFirstShiftPlan(),
            'firstShiftFact' => $item->getFirstShiftFact(),
            'secondShiftPlan' => $item->getSecondShiftPlan(),
            'secondShiftFact' => $item->getSecondShiftFact(),
            'note' => $item->getNote(),
        ];
    }

    private function decodeJson(Request $request): array
    {
        $content = $request->getContent();

        if (!$content) {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
