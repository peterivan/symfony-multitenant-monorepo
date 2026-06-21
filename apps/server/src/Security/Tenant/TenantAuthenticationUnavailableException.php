<?php

declare(strict_types=1);

namespace App\Security\Tenant;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Fail-closed tenant authentication failure (ADR-007).
 *
 * Raised when tenant authentication cannot proceed because tenant context is
 * missing, unresolved, ambiguous, or ineligible, or because existing tenant
 * authentication state no longer matches the resolved tenant. Extending
 * {@see AuthenticationException} ensures the firewall treats it as an
 * authentication denial (and discards restored state on use), never as a server
 * error and never as a reason to try another store.
 */
final class TenantAuthenticationUnavailableException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Tenant authentication is unavailable for the current tenant context.';
    }
}
