<?php

declare(strict_types=1);

namespace App\Security\Platform;

use App\Central\Repository\PlatformOperatorRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User provider for the platform firewall (ADR-007).
 *
 * Bound EXCLUSIVELY to the central platform-owned identity store. It never reads a
 * tenant identity store, and failed lookups are final for the platform boundary —
 * there is no fallback into tenant authentication. A matching email in a tenant is
 * never consulted because this provider only ever queries central storage.
 *
 * @implements UserProviderInterface<PlatformOperatorUser>
 */
final class PlatformUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly PlatformOperatorRepository $operators,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $operator = $this->operators->findOneByEmail($identifier);

        if (null === $operator) {
            throw new UserNotFoundException();
        }

        return new PlatformOperatorUser($operator);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof PlatformOperatorUser) {
            throw new UnsupportedUserException(\sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return PlatformOperatorUser::class === $class || is_subclass_of($class, PlatformOperatorUser::class);
    }
}
