<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Raised when a tenant connection is requested but no tenant context is resolved.
 */
final class MissingTenantContextException extends TenantConnectivityException
{
    public function __construct()
    {
        parent::__construct('No resolved tenant context: refusing to open a tenant database (fail closed).');
    }
}
