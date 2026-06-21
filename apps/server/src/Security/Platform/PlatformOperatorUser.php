<?php

declare(strict_types=1);

namespace App\Security\Platform;

use App\Central\Entity\PlatformOperator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security adapter for a platform-operator identity (ADR-007).
 *
 * Wraps the central-owned {@see PlatformOperator} for the platform firewall. It
 * exists only on the platform boundary: it carries the `ROLE_PLATFORM_OPERATOR`
 * role and never any tenant role, so platform authentication state can never grant
 * tenant-user identity or ordinary tenant-facing access.
 */
final class PlatformOperatorUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly PlatformOperator $operator,
    ) {}

    public function getOperator(): PlatformOperator
    {
        return $this->operator;
    }

    public function getUserIdentifier(): string
    {
        $email = $this->operator->getEmail();
        \assert('' !== $email);

        return $email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_PLATFORM_OPERATOR'];
    }

    public function getPassword(): ?string
    {
        return $this->operator->getPasswordHash();
    }

    public function eraseCredentials(): void
    {
        // No transient plaintext credentials are held on this adapter.
    }

    /**
     * Stable platform-operator reference for audit attribution.
     */
    public function auditReference(): string
    {
        return $this->operator->auditReference();
    }
}
