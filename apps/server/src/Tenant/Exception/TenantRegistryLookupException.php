<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Raised when the registry lookup itself fails (for example the central database
 * is unreachable). Treated as fail-closed, never as "use a default".
 */
final class TenantRegistryLookupException extends TenantConnectivityException
{
    public function __construct(string $slug, \Throwable $previous)
    {
        parent::__construct(
            \sprintf(
                'Registry lookup for tenant "%s" failed: refusing to open a tenant database (fail closed).',
                $slug,
            ),
            0,
            $previous,
        );
    }
}
