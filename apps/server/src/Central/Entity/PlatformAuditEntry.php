<?php

declare(strict_types=1);

namespace App\Central\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Immutable audit record for a platform (Back Office) operation (ADR-002).
 *
 * Stored in the CENTRAL database — audit is platform-owned metadata, never
 * tenant-owned data. Each entry attributes the platform operator and, when the
 * operation is tenant-scoped, the selected tenant. Entries are append-only: there
 * are no mutators, so an audited operation cannot later rewrite its own trail.
 */
#[ORM\Entity]
#[ORM\Table(name: 'platform_audit_entry')]
#[ORM\Index(name: 'idx_platform_audit_occurred_at', columns: ['occurred_at'])]
#[ORM\Index(name: 'idx_platform_audit_tenant_slug', columns: ['tenant_slug'])]
class PlatformAuditEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private readonly Uuid $id;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private readonly \DateTimeImmutable $occurredAt;

    #[ORM\Column(enumType: AuditCategory::class)]
    private readonly AuditCategory $category;

    #[ORM\Column(length: 191)]
    private readonly string $operation;

    #[ORM\Column(enumType: AuditOutcome::class)]
    private readonly AuditOutcome $outcome;

    /**
     * Platform-operator attribution. Nullable only because operator authentication
     * lands in a later change (ADR-007); the column is populated once it does.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private readonly ?string $operatorReference;

    /**
     * Selected tenant for tenant-scoped operations; null for central-only or
     * cross-tenant operations that do not target a single tenant.
     */
    #[ORM\Column(length: 63, nullable: true)]
    private readonly ?string $tenantSlug;

    /**
     * Operation-specific scope details (affected ids, parameters, counts, ...).
     *
     * @var array<string, scalar|array<array-key, scalar|null>|null>
     */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private readonly array $scope;

    /**
     * @param array<string, scalar|array<array-key, scalar|null>|null> $scope
     */
    public function __construct(
        AuditCategory $category,
        string $operation,
        AuditOutcome $outcome,
        AuditAttribution $attribution,
        array $scope,
    ) {
        $this->id = Uuid::v7();
        $this->occurredAt = new \DateTimeImmutable();
        $this->category = $category;
        $this->operation = $operation;
        $this->outcome = $outcome;
        $this->operatorReference = $attribution->operatorReference;
        $this->tenantSlug = $attribution->tenantSlug;
        $this->scope = $scope;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getCategory(): AuditCategory
    {
        return $this->category;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getOutcome(): AuditOutcome
    {
        return $this->outcome;
    }

    public function getOperatorReference(): ?string
    {
        return $this->operatorReference;
    }

    public function getTenantSlug(): ?string
    {
        return $this->tenantSlug;
    }

    /**
     * @return array<string, scalar|array<array-key, scalar|null>|null>
     */
    public function getScope(): array
    {
        return $this->scope;
    }
}
