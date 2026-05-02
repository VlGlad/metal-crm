<?php

namespace App\Controller;

use App\Entity\OtkInspection;
use App\Repository\OtkInspectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/otk-inspections')]
class OtkInspectionController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(OtkInspectionRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['date' => 'DESC', 'id' => 'DESC']);

        return $this->json(array_map(
            fn (OtkInspection $inspection) => $this->serializeInspection($inspection),
            $items
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(OtkInspection $inspection): JsonResponse
    {
        return $this->json($this->serializeInspection($inspection));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->getJsonData($request);

        $inspection = new OtkInspection();
        $this->fillInspection($inspection, $data);

        $error = $this->validateInspection($inspection);

        if ($error) {
            return $this->json(['message' => $error], 422);
        }

        $em->persist($inspection);
        $em->flush();

        return $this->json($this->serializeInspection($inspection), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(
        OtkInspection $inspection,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $this->getJsonData($request);

        $this->fillInspection($inspection, $data);
        $inspection->touch();

        $error = $this->validateInspection($inspection);

        if ($error) {
            return $this->json(['message' => $error], 422);
        }

        $em->flush();

        return $this->json($this->serializeInspection($inspection));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(OtkInspection $inspection, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($inspection);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/sign-executor', methods: ['POST'])]
    public function signExecutor(
        OtkInspection $inspection,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $this->getJsonData($request);
        $name = trim((string)($data['executorName'] ?? ''));

        if ($name === '') {
            return $this->json(['message' => 'Укажите исполнителя.'], 422);
        }

        $inspection->setExecutorName($name);
        $inspection->setExecutorSignedAt(new \DateTimeImmutable());
        $inspection->touch();

        $em->flush();

        return $this->json($this->serializeInspection($inspection));
    }

    #[Route('/{id}/sign-controller', methods: ['POST'])]
    public function signController(
        OtkInspection $inspection,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $this->getJsonData($request);
        $name = trim((string)($data['controllerName'] ?? ''));

        if ($name === '') {
            return $this->json(['message' => 'Укажите контролера ОТК.'], 422);
        }

        $inspection->setControllerName($name);
        $inspection->setControllerSignedAt(new \DateTimeImmutable());
        $inspection->touch();

        if ($inspection->getRejectedQuantity() > 0) {
            $inspection->setStatus('rejected');
        } else {
            $inspection->setStatus('accepted');
        }

        $em->flush();

        return $this->json($this->serializeInspection($inspection));
    }

    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function fillInspection(OtkInspection $inspection, array $data): void
    {
        if (array_key_exists('date', $data)) {
            $inspection->setDate(new \DateTimeImmutable($data['date']));
        }

        if (array_key_exists('name', $data)) {
            $inspection->setName(trim((string)$data['name']));
        }

        if (array_key_exists('project', $data)) {
            $inspection->setProject(trim((string)$data['project']));
        }

        if (array_key_exists('presentedQuantity', $data)) {
            $inspection->setPresentedQuantity((int)$data['presentedQuantity']);
        }

        if (array_key_exists('acceptedQuantity', $data)) {
            $inspection->setAcceptedQuantity((int)$data['acceptedQuantity']);
        }

        if (array_key_exists('rejectedQuantity', $data)) {
            $inspection->setRejectedQuantity((int)$data['rejectedQuantity']);
        }

        if (array_key_exists('nonconformityDescription', $data)) {
            $value = trim((string)$data['nonconformityDescription']);
            $inspection->setNonconformityDescription($value !== '' ? $value : null);
        }

        if (array_key_exists('nonconformityActNumber', $data)) {
            $value = trim((string)$data['nonconformityActNumber']);
            $inspection->setNonconformityActNumber($value !== '' ? $value : null);
        }

        if (array_key_exists('executorName', $data)) {
            $value = trim((string)$data['executorName']);
            $inspection->setExecutorName($value !== '' ? $value : null);
        }

        if (array_key_exists('controllerName', $data)) {
            $value = trim((string)$data['controllerName']);
            $inspection->setControllerName($value !== '' ? $value : null);
        }

        if (array_key_exists('status', $data)) {
            $inspection->setStatus(trim((string)$data['status']));
        }
    }

    private function validateInspection(OtkInspection $inspection): ?string
    {
        if (!$inspection->getDate()) {
            return 'Укажите дату.';
        }

        if (trim($inspection->getName()) === '') {
            return 'Укажите наименование.';
        }

        if (trim($inspection->getProject()) === '') {
            return 'Укажите проект.';
        }

        if ($inspection->getPresentedQuantity() < 0) {
            return 'Количество предъявленных изделий не может быть меньше нуля.';
        }

        if ($inspection->getAcceptedQuantity() < 0) {
            return 'Количество принятых изделий не может быть меньше нуля.';
        }

        if ($inspection->getRejectedQuantity() < 0) {
            return 'Количество забракованных изделий не может быть меньше нуля.';
        }

        if (
            $inspection->getAcceptedQuantity() + $inspection->getRejectedQuantity()
            > $inspection->getPresentedQuantity()
        ) {
            return 'Сумма принятых и забракованных изделий не может превышать количество предъявленных.';
        }

        if (
            $inspection->getRejectedQuantity() > 0
            && !$inspection->getNonconformityDescription()
            && !$inspection->getNonconformityActNumber()
        ) {
            return 'При наличии брака укажите описание несоответствия или номер акта.';
        }

        return null;
    }

    private function serializeInspection(OtkInspection $inspection): array
    {
        return [
            'id' => $inspection->getId(),
            'date' => $inspection->getDate()?->format('Y-m-d'),
            'name' => $inspection->getName(),
            'project' => $inspection->getProject(),
            'presentedQuantity' => $inspection->getPresentedQuantity(),
            'acceptedQuantity' => $inspection->getAcceptedQuantity(),
            'rejectedQuantity' => $inspection->getRejectedQuantity(),
            'nonconformityDescription' => $inspection->getNonconformityDescription(),
            'nonconformityActNumber' => $inspection->getNonconformityActNumber(),
            'executorName' => $inspection->getExecutorName(),
            'controllerName' => $inspection->getControllerName(),
            'executorSignedAt' => $inspection->getExecutorSignedAt()?->format(DATE_ATOM),
            'controllerSignedAt' => $inspection->getControllerSignedAt()?->format(DATE_ATOM),
            'status' => $inspection->getStatus(),
            'createdAt' => $inspection->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $inspection->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}