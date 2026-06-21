<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Raised when the resolved tenant identifier has no entry in the tenant registry.
 */
final class TenantNotFoundException extends TenantConnectivityException
{
    public function __construct(string $slug)
    {
        parent::__construct(\sprintf(
            'Tenant "%s" is not registered: refusing to open a tenant database (fail closed).',
            $slug,
        ));
    }
}
