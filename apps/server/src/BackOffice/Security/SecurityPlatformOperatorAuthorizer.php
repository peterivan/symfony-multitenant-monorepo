<?php

declare(strict_types=1);

namespace App\BackOffice\Security;

use App\Security\Platform\PlatformOperatorUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security-backed platform-operator authorizer (ADR-007).
 *
 * Fills the {@see PlatformOperatorAuthorizer} seam with the real platform
 * authentication state from the platform firewall: the current actor is an
 * authorized platform operator only when the security token holds a
 * {@see PlatformOperatorUser} (authenticated against the central platform-owned
 * identity store). It consults nothing tenant-local, so tenant authentication
 * state can never satisfy it. It still fails closed: no authenticated operator
 * means no access.
 */
final class SecurityPlatformOperatorAuthorizer implements PlatformOperatorAuthorizer
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function isPlatformOperator(): bool
    {
        return $this->currentUser() instanceof PlatformOperatorUser;
    }

    public function currentOperatorReference(): ?string
    {
        $user = $this->currentUser();

        return $user instanceof PlatformOperatorUser ? $user->auditReference() : null;
    }

    private function currentUser(): ?UserInterface
    {
        return $this->tokenStorage->getToken()?->getUser();
    }
}
