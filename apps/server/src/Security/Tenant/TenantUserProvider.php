<?php

declare(strict_types=1);

namespace App\Security\Tenant;

use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Entity\TenantUser;
use App\Tenant\Repository\TenantUserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User provider for the tenant firewall (ADR-007).
 *
 * Tenant-context-first and fail-closed: it resolves the servable tenant through
 * {@see TenantAuthenticationGate} BEFORE any tenant store access, and reads the
 * tenant identity store selected by the resolved tenant context via the chunk-2
 * routing layer ({@see TenantEntityManagerProvider}) — never a default/stale
 * tenant, the central store, or a shared source. Failed lookups are final for the
 * tenant boundary; there is no fallback into platform authentication.
 *
 * On refresh (existing state used on a later request) it re-checks tenant
 * eligibility and rejects state whose bound tenant does not match the currently
 * resolved tenant — so a tenant session can never be replayed against another
 * tenant or outlive its tenant's eligibility.
 *
 * @implements UserProviderInterface<TenantUserIdentity>
 */
final class TenantUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly TenantAuthenticationGate $gate,
        private readonly TenantEntityManagerProvider $tenantEntityManagers,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $slug = $this->gate->requireServableTenantSlug();

        $user = $this->tenantUsers()->findOneByEmail($identifier);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        return new TenantUserIdentity($user, $slug);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof TenantUserIdentity) {
            throw new UnsupportedUserException(\sprintf('Unsupported user class "%s".', $user::class));
        }

        // Re-check eligibility on use; rejects state bound to a now-ineligible tenant.
        $slug = $this->gate->requireServableTenantSlug();

        // Reject state bound to a different tenant than the one currently resolved,
        // before any tenant store is touched.
        if ($user->getTenantSlug() !== $slug) {
            throw new TenantAuthenticationUnavailableException(
                'Tenant authentication state does not match the resolved tenant context.',
            );
        }

        $fresh = $this->tenantUsers()->findOneByEmail($user->getUserIdentifier());

        if (null === $fresh) {
            throw new UserNotFoundException();
        }

        return new TenantUserIdentity($fresh, $slug);
    }

    public function supportsClass(string $class): bool
    {
        return TenantUserIdentity::class === $class || is_subclass_of($class, TenantUserIdentity::class);
    }

    /**
     * The tenant-user repository bound to the RUNTIME tenant EntityManager selected
     * by the resolved tenant context — not the static template `tenant` manager.
     */
    private function tenantUsers(): TenantUserRepository
    {
        $repository = $this->tenantEntityManagers->getEntityManager()->getRepository(TenantUser::class);
        \assert($repository instanceof TenantUserRepository);

        return $repository;
    }
}
