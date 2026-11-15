<?php

namespace App\Service;

use App\Entity\ProductionOrderFile;
use App\Entity\TaskAssignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ProductionOrderAccess;

final class TaskAssignmentExecutors
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ProductionOrderAccess $orderAccess,
    ) {
    }

    /** @return list<User>|string */
    public function resolve(array $data, TaskAssignment $assignment, User $responsible, ?ProductionOrderFile $source): array|string
    {
        if (!$responsible->isActive() && $responsible !== $assignment->getResponsible()) {
            return $this->sentence('Нельзя назначить неактивного сотрудника: '.$responsible->getFullName());
        }

        $current = $this->coExecutorUsers($assignment);

        if (!array_key_exists('coExecutorIds', $data)) {
            $coExecutors = array_values(array_filter($current, fn (User $user) => $user !== $responsible));
        } elseif (!is_array($data['coExecutorIds'])) {
            return 'Некорректный список доп. исполнителей.';
        } else {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', $data['coExecutorIds']),
                fn (int $id) => $id > 0 && $id !== $responsible->getId()
            )));
            $coExecutors = $ids ? $this->users->findBy(['id' => $ids], ['fullName' => 'ASC']) : [];

            if (count($coExecutors) !== count($ids)) {
                return 'Доп. исполнитель не найден.';
            }
        }

        foreach ($coExecutors as $user) {
            if (!$user->isActive() && !isset($current[$user->getId()])) {
                return $this->sentence('Нельзя назначить неактивного сотрудника: '.$user->getFullName());
            }

            if ($source && !$this->orderAccess->canViewDocument($user, $source->getDocumentType())) {
                return $this->sentence('Нет доступа к файлу заказа у сотрудника: '.$user->getFullName());
            }
        }

        return $coExecutors;
    }

    /** @return array<int, string> */
    public function coExecutorNames(TaskAssignment $assignment): array
    {
        return array_map(fn (User $user) => $user->getDisplayLabel(), $this->coExecutorUsers($assignment));
    }

    public function summary(TaskAssignment $assignment): string
    {
        $names = $this->coExecutorNames($assignment);

        return $this->sentence('Главный исполнитель: '.($assignment->getResponsible()?->getDisplayLabel() ?: '—'))
            .($names ? ' '.$this->sentence('Доп. исполнители: '.implode(', ', $names)) : '');
    }

    /** @param array<int, string> $oldCoExecutorNames */
    public function changes(?User $oldResponsible, array $oldCoExecutorNames, TaskAssignment $assignment): string
    {
        $parts = [];
        $names = $this->coExecutorNames($assignment);

        if ($oldResponsible?->getId() !== $assignment->getResponsible()?->getId()) {
            $parts[] = $this->sentence(sprintf(
                'Главный исполнитель: %s → %s',
                $oldResponsible?->getDisplayLabel() ?: '—',
                $assignment->getResponsible()?->getDisplayLabel() ?: '—'
            ));
        }

        if ($added = array_diff_key($names, $oldCoExecutorNames)) {
            $parts[] = $this->sentence('Добавлены доп. исполнители: '.implode(', ', $added));
        }

        if ($removed = array_diff_key($oldCoExecutorNames, $names)) {
            $parts[] = $this->sentence('Убраны доп. исполнители: '.implode(', ', $removed));
        }

        return implode(' ', $parts);
    }

    private function sentence(string $text): string
    {
        return rtrim($text, '.').'.';
    }

    /** @return array<int, User> */
    private function coExecutorUsers(TaskAssignment $assignment): array
    {
        $users = [];

        foreach ($assignment->getCoExecutors() as $coExecutor) {
            if ($user = $coExecutor->getUser()) {
                $users[$user->getId()] = $user;
            }
        }

        return $users;
    }
}
