<?php

declare(strict_types=1);

namespace App\Tenant\Credentials;

/**
 * Resolved tenant database credentials. Held only transiently while a tenant
 * connection is constructed; never persisted in the registry.
 */
final class TenantDatabaseCredentials
{
    public function __construct(
        public readonly string $user,
        #[\SensitiveParameter]
        public readonly string $password,
    ) {}
}
