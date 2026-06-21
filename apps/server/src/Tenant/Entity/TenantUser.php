<?php

declare(strict_types=1);

namespace App\Tenant\Entity;

use App\Tenant\Repository\TenantUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Tenant-user identity (ADR-006).
 *
 * A tenant-owned identity living exclusively in a tenant database and mapped only
 * to the `tenant` EntityManager. It is only meaningful and only reachable inside a
 * resolved tenant context (tenant-database-connectivity); there is no central or
 * cross-tenant path to it.
 *
 * Existence in one tenant implies nothing in another: each tenant database holds
 * its own tenant_user table, so the same email in tenant A and tenant B is two
 * independent identities. Email is unique only within a single tenant store — the
 * per-database unique constraint never spans tenants, and there is no shared
 * person/account identifier linking identities across tenants or to the platform
 * boundary.
 *
 * By design this entity carries tenant-facing attribution only: no Back Office or
 * platform-operator fields. Name, email, profile, and account-recovery state are
 * tenant-local concerns changed entirely within the tenant boundary. Credentials/
 * authentication are out of scope here and land with ADR-007.
 */
#[ORM\Entity(repositoryClass: TenantUserRepository::class)]
#[ORM\Table(name: 'tenant_user')]
#[ORM\UniqueConstraint(name: 'uniq_tenant_user_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class TenantUser
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private readonly Uuid $id;

    /**
     * Unique only within this single tenant's database (ADR-006).
     */
    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $displayName;

    /**
     * Tenant-local account-recovery state. Owned and changed entirely within the
     * tenant boundary; no central mirror, no cross-tenant coordination.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountRecoveryToken = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $accountRecoveryRequestedAt = null;

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

    public function getAccountRecoveryToken(): ?string
    {
        return $this->accountRecoveryToken;
    }

    public function getAccountRecoveryRequestedAt(): ?\DateTimeImmutable
    {
        return $this->accountRecoveryRequestedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Tenant-local profile change.
     */
    public function changeEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Tenant-local profile change.
     */
    public function rename(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Record a tenant-local account-recovery request. Models recovery state only;
     * the recovery flow itself is out of scope (ADR-007).
     */
    public function requestAccountRecovery(#[\SensitiveParameter] string $token): void
    {
        $this->accountRecoveryToken = $token;
        $this->accountRecoveryRequestedAt = new \DateTimeImmutable();
    }

    public function clearAccountRecovery(): void
    {
        $this->accountRecoveryToken = null;
        $this->accountRecoveryRequestedAt = null;
    }

    /**
     * Stable reference for audit attribution within the tenant-user boundary.
     * Meaningful only together with the selected tenant context.
     */
    public function auditReference(): string
    {
        return 'tenant-user:' . $this->id->toRfc4122();
    }
}
