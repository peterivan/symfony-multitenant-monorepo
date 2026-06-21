<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Raised when a tenant-scoped async message is consumed without explicit tenant
 * routing metadata (a TenantStamp). Fails closed: the handler never runs under an
 * implicit or default tenant (ADR-001).
 */
final class MissingTenantRoutingMetadataException extends TenantConnectivityException
{
    public function __construct(string $messageClass)
    {
        parent::__construct(\sprintf(
            'Tenant-scoped message "%s" carries no explicit tenant routing metadata: refusing to handle it (fail closed).',
            $messageClass,
        ));
    }
}
