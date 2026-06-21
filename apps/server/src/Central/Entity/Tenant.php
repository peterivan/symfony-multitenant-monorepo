<?php

declare(strict_types=1);

namespace App\Central\Entity;

use App\Central\Repository\TenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Central-owned tenant registry entry (ADR-001).
 *
 * Lives exclusively in the central database and holds registry, routing, and
 * provisioning metadata only — never tenant-owned business data. Mapped solely to
 * the `central` EntityManager.
 */
#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenant')]
#[ORM\UniqueConstraint(name: 'uniq_tenant_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Tenant
{
    /**
     * Globally unique, immutable tenant identifier. Assigned once at creation and
     * never updated (no setter).
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private readonly Uuid $id;

    #[ORM\Column(length: 63, unique: true)]
    private string $slug;

    #[ORM\Embedded(class: TenantDatabaseLocation::class, columnPrefix: 'database_')]
    private TenantDatabaseLocation $databaseLocation;

    #[ORM\Column(enumType: TenantLifecycleState::class, options: ['default' => TenantLifecycleState::Active->value])]
    private TenantLifecycleState $lifecycleState = TenantLifecycleState::Active;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $slug, TenantDatabaseLocation $databaseLocation)
    {
        $this->id = Uuid::v7();
        $this->slug = $slug;
        $this->databaseLocation = $databaseLocation;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDatabaseLocation(): TenantDatabaseLocation
    {
        return $this->databaseLocation;
    }

    public function getLifecycleState(): TenantLifecycleState
    {
        return $this->lifecycleState;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateDatabaseLocation(TenantDatabaseLocation $databaseLocation): void
    {
        $this->databaseLocation = $databaseLocation;
    }

    public function changeLifecycleState(TenantLifecycleState $lifecycleState): void
    {
        $this->lifecycleState = $lifecycleState;
    }
}
