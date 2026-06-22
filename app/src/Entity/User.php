<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_MASTER = 'ROLE_MASTER';
    public const ROLE_OPERATOR = 'ROLE_OPERATOR';
    public const ROLE_CONTROLLER_OTK = 'ROLE_CONTROLLER_OTK';
    public const ROLE_CRO = 'ROLE_CRO';
    public const ROLE_SSC = 'ROLE_SSC';
    public const ROLE_CPO = 'ROLE_CPO';
    public const ROLE_MANAGER = 'ROLE_MANAGER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public const ROLE_GENERAL_DIRECTOR = 'ROLE_GENERAL_DIRECTOR';
    public const ROLE_PRODUCTION_HEAD = 'ROLE_PRODUCTION_HEAD';
    public const ROLE_DEPARTMENT_HEAD = 'ROLE_DEPARTMENT_HEAD';
    public const ROLE_PTO_HEAD = 'ROLE_PTO_HEAD';
    public const ROLE_PTO_DEPUTY_HEAD = 'ROLE_PTO_DEPUTY_HEAD';
    public const ROLE_PTO_ENGINEER = 'ROLE_PTO_ENGINEER';
    public const ROLE_PO_HEAD = 'ROLE_PO_HEAD';
    public const ROLE_PO_LEAD_ENGINEER = 'ROLE_PO_LEAD_ENGINEER';
    public const ROLE_PO_ENGINEER = 'ROLE_PO_ENGINEER';
    public const ROLE_LEAD_DESIGN_ENGINEER = 'ROLE_LEAD_DESIGN_ENGINEER';
    public const ROLE_DESIGN_ENGINEER = 'ROLE_DESIGN_ENGINEER';
    public const ROLE_OMTS_HEAD = 'ROLE_OMTS_HEAD';
    public const ROLE_OMTS_DEPUTY_HEAD = 'ROLE_OMTS_DEPUTY_HEAD';
    public const ROLE_WAREHOUSE_MANAGER = 'ROLE_WAREHOUSE_MANAGER';
    public const ROLE_METAL_WAREHOUSE_MANAGER = 'ROLE_METAL_WAREHOUSE_MANAGER';
    public const ROLE_SALES_HEAD = 'ROLE_SALES_HEAD';
    public const ROLE_SALES_MANAGER = 'ROLE_SALES_MANAGER';
    public const ROLE_SALES_LOGISTICIAN = 'ROLE_SALES_LOGISTICIAN';
    public const ROLE_ECONOMIST = 'ROLE_ECONOMIST';
    public const ROLE_CHIEF_ACCOUNTANT = 'ROLE_CHIEF_ACCOUNTANT';
    public const ROLE_SALES_ACCOUNTANT = 'ROLE_SALES_ACCOUNTANT';
    public const ROLE_CHIEF_TECHNOLOGIST = 'ROLE_CHIEF_TECHNOLOGIST';
    public const ROLE_CHIEF_WELDER = 'ROLE_CHIEF_WELDER';
    public const ROLE_OTK_HEAD = 'ROLE_OTK_HEAD';
    public const ROLE_OTK_ENGINEER = 'ROLE_OTK_ENGINEER';
    public const ROLE_OTK_INCOMING_INSPECTION_ENGINEER = 'ROLE_OTK_INCOMING_INSPECTION_ENGINEER';
    public const ROLE_CZL_HEAD = 'ROLE_CZL_HEAD';
    public const ROLE_CZL_ENGINEER = 'ROLE_CZL_ENGINEER';
    public const ROLE_CZL_FLAW_DETECTOR = 'ROLE_CZL_FLAW_DETECTOR';
    public const ROLE_OSMK_HEAD = 'ROLE_OSMK_HEAD';
    public const ROLE_CRO_HEAD = 'ROLE_CRO_HEAD';
    public const ROLE_CRO_DEPUTY_HEAD = 'ROLE_CRO_DEPUTY_HEAD';
    public const ROLE_CRO_TECHNICIAN = 'ROLE_CRO_TECHNICIAN';
    public const ROLE_SSC_HEAD = 'ROLE_SSC_HEAD';
    public const ROLE_SSC_DEPUTY_HEAD = 'ROLE_SSC_DEPUTY_HEAD';
    public const ROLE_SSC_SENIOR_MASTER = 'ROLE_SSC_SENIOR_MASTER';
    public const ROLE_CPO_HEAD = 'ROLE_CPO_HEAD';
    public const ROLE_KITTING_MASTER = 'ROLE_KITTING_MASTER';
    public const ROLE_RESPONSIBLE_EXECUTOR = 'ROLE_RESPONSIBLE_EXECUTOR';
    public const ROLE_LEGAL_COUNSEL = 'ROLE_LEGAL_COUNSEL';

    public const ASSIGNABLE_ROLES = [
        self::ROLE_MASTER,
        self::ROLE_OPERATOR,
        self::ROLE_CONTROLLER_OTK,
        self::ROLE_CRO,
        self::ROLE_SSC,
        self::ROLE_CPO,
        self::ROLE_MANAGER,
        self::ROLE_GENERAL_DIRECTOR,
        self::ROLE_PRODUCTION_HEAD,
        self::ROLE_DEPARTMENT_HEAD,
        self::ROLE_PTO_HEAD,
        self::ROLE_PTO_DEPUTY_HEAD,
        self::ROLE_PTO_ENGINEER,
        self::ROLE_PO_HEAD,
        self::ROLE_PO_LEAD_ENGINEER,
        self::ROLE_PO_ENGINEER,
        self::ROLE_LEAD_DESIGN_ENGINEER,
        self::ROLE_DESIGN_ENGINEER,
        self::ROLE_OMTS_HEAD,
        self::ROLE_OMTS_DEPUTY_HEAD,
        self::ROLE_WAREHOUSE_MANAGER,
        self::ROLE_METAL_WAREHOUSE_MANAGER,
        self::ROLE_SALES_HEAD,
        self::ROLE_SALES_MANAGER,
        self::ROLE_SALES_LOGISTICIAN,
        self::ROLE_ECONOMIST,
        self::ROLE_CHIEF_ACCOUNTANT,
        self::ROLE_SALES_ACCOUNTANT,
        self::ROLE_CHIEF_TECHNOLOGIST,
        self::ROLE_CHIEF_WELDER,
        self::ROLE_OTK_HEAD,
        self::ROLE_OTK_ENGINEER,
        self::ROLE_OTK_INCOMING_INSPECTION_ENGINEER,
        self::ROLE_CZL_HEAD,
        self::ROLE_CZL_ENGINEER,
        self::ROLE_CZL_FLAW_DETECTOR,
        self::ROLE_OSMK_HEAD,
        self::ROLE_CRO_HEAD,
        self::ROLE_CRO_DEPUTY_HEAD,
        self::ROLE_CRO_TECHNICIAN,
        self::ROLE_SSC_HEAD,
        self::ROLE_SSC_DEPUTY_HEAD,
        self::ROLE_SSC_SENIOR_MASTER,
        self::ROLE_CPO_HEAD,
        self::ROLE_KITTING_MASTER,
        self::ROLE_RESPONSIBLE_EXECUTOR,
        self::ROLE_LEGAL_COUNSEL,
        self::ROLE_ADMIN,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $fullName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = trim($fullName);

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $position = $position !== null ? trim($position) : null;
        $this->position = $position !== '' ? $position : null;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $department = $department !== null ? trim($department) : null;
        $this->department = $department !== '' ? $department : null;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique(array_filter(
            $roles,
            fn (string $role) => in_array($role, self::ASSIGNABLE_ROLES, true)
        )));

        return $this;
    }

    public function addRole(string $role): self
    {
        $roles = $this->roles;
        $roles[] = $role;

        return $this->setRoles($roles);
    }

    public function removeRole(string $role): self
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            fn (string $existingRole) => $existingRole !== $role
        ));

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Сюда кладём уже захешированный пароль.
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Если потом появятся plainPassword или временные секреты — очищать здесь.
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
