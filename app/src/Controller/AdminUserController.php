<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
final class AdminUserController extends AbstractController
{
    private const ROLE_LABELS = [
        User::ROLE_MASTER => 'Мастер',
        User::ROLE_OPERATOR => 'Оператор',
        User::ROLE_CONTROLLER_OTK => 'Контролер ОТК',
        User::ROLE_CRO => 'Цех раскроя и обработки',
        User::ROLE_SSC => 'Сборочно-сварочный цех',
        User::ROLE_CPO => 'Цех покраски и отгрузки',
        User::ROLE_MANAGER => 'Руководитель',
        User::ROLE_GENERAL_DIRECTOR => 'Генеральный директор',
        User::ROLE_PRODUCTION_HEAD => 'Начальник производства',
        User::ROLE_DEPARTMENT_HEAD => 'Начальник подразделения',
        User::ROLE_PTO_HEAD => 'Начальник ПТО',
        User::ROLE_PTO_DEPUTY_HEAD => 'Заместитель начальника ПТО',
        User::ROLE_PTO_ENGINEER => 'Инженер ПТО',
        User::ROLE_PO_HEAD => 'Начальник ПО',
        User::ROLE_PO_LEAD_ENGINEER => 'Ведущий инженер ПО',
        User::ROLE_PO_ENGINEER => 'Инженер ПО',
        User::ROLE_LEAD_DESIGN_ENGINEER => 'Ведущий инженер-конструктор',
        User::ROLE_DESIGN_ENGINEER => 'Инженер-конструктор',
        User::ROLE_OMTS_HEAD => 'Начальник ОМТС',
        User::ROLE_OMTS_DEPUTY_HEAD => 'Заместитель начальника ОМТС',
        User::ROLE_WAREHOUSE_MANAGER => 'Заведующий складом',
        User::ROLE_METAL_WAREHOUSE_MANAGER => 'Заведующий складом металла',
        User::ROLE_SALES_HEAD => 'Начальник отдела продаж',
        User::ROLE_SALES_MANAGER => 'Менеджер по продажам',
        User::ROLE_SALES_LOGISTICIAN => 'Менеджер по продажам (логист)',
        User::ROLE_ECONOMIST => 'Экономист',
        User::ROLE_CHIEF_ACCOUNTANT => 'Главный бухгалтер',
        User::ROLE_SALES_ACCOUNTANT => 'Бухгалтер по реализации',
        User::ROLE_CHIEF_TECHNOLOGIST => 'Главный технолог',
        User::ROLE_CHIEF_WELDER => 'Главный сварщик',
        User::ROLE_OTK_HEAD => 'Начальник ОТК',
        User::ROLE_OTK_ENGINEER => 'Инженер ОТК',
        User::ROLE_OTK_INCOMING_INSPECTION_ENGINEER => 'Инженер входного контроля ОТК',
        User::ROLE_CZL_HEAD => 'Начальник ЦЗЛ',
        User::ROLE_CZL_ENGINEER => 'Инженер ЦЗЛ',
        User::ROLE_CZL_FLAW_DETECTOR => 'Дефектоскопист ЦЗЛ',
        User::ROLE_OSMK_HEAD => 'Начальник ОСМК',
        User::ROLE_CRO_HEAD => 'Начальник ЦРО',
        User::ROLE_CRO_DEPUTY_HEAD => 'Заместитель начальника ЦРО',
        User::ROLE_CRO_TECHNICIAN => 'Техник ЦРО',
        User::ROLE_SSC_HEAD => 'Начальник ССЦ',
        User::ROLE_SSC_DEPUTY_HEAD => 'Заместитель начальника ССЦ',
        User::ROLE_SSC_SENIOR_MASTER => 'Старший мастер ССЦ',
        User::ROLE_CPO_HEAD => 'Начальник ЦПО',
        User::ROLE_KITTING_MASTER => 'Мастер по комплектации',
        User::ROLE_RESPONSIBLE_EXECUTOR => 'Ответственный исполнитель',
        User::ROLE_LEGAL_COUNSEL => 'Юрисконсульт',
        User::ROLE_ADMIN => 'Администратор',
    ];

    #[Route('', methods: ['GET'])]
    public function index(UserRepository $repository): JsonResponse
    {
        return $this->json([
            'users' => array_map(
                fn (User $user) => $this->serializeUser($user),
                $repository->findBy([], ['fullName' => 'ASC', 'email' => 'ASC'])
            ),
            'availableRoles' => array_map(
                fn (string $role) => [
                    'value' => $role,
                    'label' => self::ROLE_LABELS[$role] ?? $role,
                ],
                User::ASSIGNABLE_ROLES
            ),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $this->decodeJson($request);

        if ($error = $this->validatePayload($data, true)) {
            return $this->json(['message' => $error], 422);
        }

        $email = mb_strtolower(trim((string) $data['email']));

        if ($repository->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'Пользователь с таким email уже существует.'], 409);
        }

        $user = new User();
        $this->fillUser($user, $data);
        $user->setIsActive((bool) ($data['isActive'] ?? true));
        $user->setPassword($passwordHasher->hashPassword($user, (string) $data['password']));

        $entityManager->persist($user);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json(['message' => 'Пользователь с таким email уже существует.'], 409);
        }

        return $this->json($this->serializeUser($user), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(
        User $user,
        Request $request,
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $this->decodeJson($request);

        if ($error = $this->validatePayload($data, false)) {
            return $this->json(['message' => $error], 422);
        }

        $email = mb_strtolower(trim((string) $data['email']));
        $existingUser = $repository->findOneBy(['email' => $email]);

        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return $this->json(['message' => 'Пользователь с таким email уже существует.'], 409);
        }

        $currentUser = $this->getUser();
        $roles = $this->normalizeRoles($data['roles'] ?? []);
        $isActive = (bool) ($data['isActive'] ?? true);

        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            if (!in_array(User::ROLE_ADMIN, $roles, true)) {
                return $this->json(['message' => 'Нельзя снять роль администратора у своей учетной записи.'], 422);
            }

            if (!$isActive) {
                return $this->json(['message' => 'Нельзя отключить свою учетную запись.'], 422);
            }
        }

        $this->fillUser($user, $data);
        $user->setIsActive($isActive)->touch();

        $password = (string) ($data['password'] ?? '');

        if ($password !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json(['message' => 'Пользователь с таким email уже существует.'], 409);
        }

        return $this->json($this->serializeUser($user));
    }

    private function fillUser(User $user, array $data): void
    {
        $user
            ->setEmail((string) $data['email'])
            ->setFullName((string) $data['fullName'])
            ->setPosition($this->nullableString($data['position'] ?? null))
            ->setDepartment($this->nullableString($data['department'] ?? null))
            ->setRoles($this->normalizeRoles($data['roles'] ?? []));
    }

    private function validatePayload(array $data, bool $passwordRequired): ?string
    {
        $email = trim((string) ($data['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Укажите корректный email.';
        }

        if (trim((string) ($data['fullName'] ?? '')) === '') {
            return 'Укажите ФИО пользователя.';
        }

        if (mb_strlen($email) > 180 || mb_strlen((string) $data['fullName']) > 255) {
            return 'Email или ФИО превышает допустимую длину.';
        }

        if (mb_strlen((string) ($data['position'] ?? '')) > 255) {
            return 'Должность превышает допустимую длину.';
        }

        if (mb_strlen((string) ($data['department'] ?? '')) > 100) {
            return 'Подразделение превышает допустимую длину.';
        }

        if (array_key_exists('isActive', $data) && !is_bool($data['isActive'])) {
            return 'Передан некорректный статус пользователя.';
        }

        if (!is_array($data['roles'] ?? null)) {
            return 'Передан некорректный список ролей.';
        }

        foreach ($data['roles'] as $role) {
            if (!is_string($role) || !in_array($role, User::ASSIGNABLE_ROLES, true)) {
                return 'Передана неизвестная роль.';
            }
        }

        $password = (string) ($data['password'] ?? '');

        if ($passwordRequired && $password === '') {
            return 'Укажите временный пароль.';
        }

        if ($password !== '' && mb_strlen($password) < 8) {
            return 'Пароль должен содержать не менее 8 символов.';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
        return array_values(array_unique(array_filter(
            $roles,
            fn (mixed $role) => is_string($role) && in_array($role, User::ASSIGNABLE_ROLES, true)
        )));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
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

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'position' => $user->getPosition(),
            'department' => $user->getDepartment(),
            'roles' => array_values(array_intersect($user->getRoles(), User::ASSIGNABLE_ROLES)),
            'isActive' => $user->isActive(),
            'lastLoginAt' => $user->getLastLoginAt()?->format(DATE_ATOM),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
