<?php

declare(strict_types=1);

namespace App\Central\Entity;

use App\Central\Repository\PlatformOperatorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Platform-operator identity (ADR-006).
 *
 * A central-owned identity living exclusively in the central database and scoped
 * to the platform / Back Office boundary (ADR-002). It is mapped only to the
 * `central` EntityManager and is never reachable from a tenant store.
 *
 * By deliberate design this entity carries platform attribution only: it has no
 * tenant-user fields, no tenant-access fields, and no identifier that links it to
 * any tenant-user identity. A matching email in a tenant is a data coincidence,
 * never a "same person" link. Email is unique only within central operator
 * storage — there is no cross-store or global uniqueness, which would imply the
 * platform-level "same person" ADR-006 forbids.
 *
 * Credentials/authentication are out of scope here and land with ADR-007.
 */
#[ORM\Entity(repositoryClass: PlatformOperatorRepository::class)]
#[ORM\Table(name: 'platform_operator')]
#[ORM\UniqueConstraint(name: 'uniq_platform_operator_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class PlatformOperator
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private readonly Uuid $id;

    /**
     * Unique within central platform-operator storage only.
     */
    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $displayName;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $email, string $displayName)
    {
        $this->id = Uuid::v7();
        $this->email = $email;
        $this->displayName = $displayName;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeEmail(string $email): void
    {
        $this->email = $email;
    }

    public function rename(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Stable reference for audit attribution within the platform-operator boundary.
     */
    public function auditReference(): string
    {
        return 'operator:' . $this->id->toRfc4122();
    }
}
