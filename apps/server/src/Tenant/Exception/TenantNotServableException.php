<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

use App\Central\Entity\TenantLifecycleState;

/**
 * Raised when the resolved tenant exists but its lifecycle state does not permit
 * tenant-facing access (suspended, archived, deleted).
 */
final class TenantNotServableException extends TenantConnectivityException
{
    public function __construct(string $slug, TenantLifecycleState $state)
    {
        parent::__construct(\sprintf(
            'Tenant "%s" is not servable (lifecycle state: %s): refusing to open a tenant database (fail closed).',
            $slug,
            $state->value,
        ));
    }
}
