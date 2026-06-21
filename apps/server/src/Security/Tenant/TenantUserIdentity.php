<?php

declare(strict_types=1);

namespace App\Security\Tenant;

use App\Tenant\Entity\TenantUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security adapter for a tenant-user identity (ADR-007).
 *
 * Wraps a tenant-owned {@see TenantUser} together with the slug of the tenant for
 * which it authenticated. The bound slug is what makes tenant authentication state
 * scoped to ONE resolved tenant: it is compared against the currently resolved
 * tenant on every use and rejected on mismatch. It carries only `ROLE_TENANT_USER`
 * and never a platform role, so tenant state can never grant Back Office access.
 */
final class TenantUserIdentity implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly TenantUser $user,
        private readonly string $tenantSlug,
    ) {}

    public function getTenantUser(): TenantUser
    {
        return $this->user;
    }

    /**
     * Slug of the tenant this authentication state is bound to.
     */
    public function getTenantSlug(): string
    {
        return $this->tenantSlug;
    }

    public function getUserIdentifier(): string
    {
        $email = $this->user->getEmail();
        \assert('' !== $email);

        return $email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_TENANT_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->user->getPasswordHash();
    }

    public function eraseCredentials(): void
    {
        // No transient plaintext credentials are held on this adapter.
    }

    /**
     * Stable tenant-user reference for audit attribution; meaningful only together
     * with the bound tenant.
     */
    public function auditReference(): string
    {
        return $this->user->auditReference();
    }
}
