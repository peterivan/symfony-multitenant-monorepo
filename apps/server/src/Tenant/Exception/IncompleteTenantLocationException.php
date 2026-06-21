<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Raised when a registry entry lacks the database-location metadata required to
 * construct a tenant connection. The system never guesses a connection target.
 */
final class IncompleteTenantLocationException extends TenantConnectivityException
{
    public function __construct(string $slug, string $reason)
    {
        parent::__construct(\sprintf(
            'Tenant "%s" has incomplete database-location metadata (%s): refusing to open a tenant database (fail closed).',
            $slug,
            $reason,
        ));
    }
}
